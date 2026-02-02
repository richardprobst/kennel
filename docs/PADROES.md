# PADROES.md - Guia de Padrões de Desenvolvimento

## Plugin WordPress para Gestão de Canil (SaaS Multi-tenant)

**Data:** 02/02/2026  
**Versão:** 1.0

Este documento estabelece os padrões de desenvolvimento obrigatórios para garantir código limpo, seguro, manutenível e seguindo princípios DRY (Don't Repeat Yourself).

---

## 1. Princípios Fundamentais

### 1.1 SOLID

| Princípio | Descrição | Aplicação no Projeto |
|-----------|-----------|----------------------|
| **S**ingle Responsibility | Uma classe = uma responsabilidade | Controllers apenas roteiam, Services contêm lógica |
| **O**pen/Closed | Aberto para extensão, fechado para modificação | Hooks/filters para extensibilidade |
| **L**iskov Substitution | Subtipos devem ser substituíveis | Interfaces bem definidas |
| **I**nterface Segregation | Interfaces pequenas e específicas | Contracts por funcionalidade |
| **D**ependency Inversion | Depender de abstrações | Injeção de dependência |

### 1.2 DRY (Don't Repeat Yourself)

> "Every piece of knowledge must have a single, unambiguous, authoritative representation within a system."

**Regras DRY:**

1. **Código duplicado = código errado** — Extrair para função/classe/trait
2. **Constantes em um só lugar** — Usar classes de constantes ou config
3. **Validações centralizadas** — Helpers e Schemas reutilizáveis
4. **Queries padrão** — Repository pattern com métodos base
5. **Componentes UI reutilizáveis** — Biblioteca de componentes comum

### 1.3 KISS (Keep It Simple, Stupid)

- Preferir soluções simples que funcionam
- Evitar over-engineering
- Código legível > código "esperto"
- Complexidade só quando necessária

### 1.4 YAGNI (You Aren't Gonna Need It)

- Não implementar funcionalidades "para o futuro"
- Adicionar complexidade apenas quando necessária
- Hooks e extensões sim, código especulativo não

---

## 2. Arquitetura DRY

### 2.1 Classes Base Reutilizáveis

#### BaseEntity (Trait)

```php
<?php
namespace CanilCore\Domain\Traits;

/**
 * Trait com comportamentos comuns a todas as entidades.
 * DRY: Evita repetir código de timestamps, tenant e serialização.
 */
trait HasTimestamps {
    protected DateTimeImmutable $createdAt;
    protected DateTimeImmutable $updatedAt;
    
    public function getCreatedAt(): DateTimeImmutable {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): DateTimeImmutable {
        return $this->updatedAt;
    }
    
    public function touch(): void {
        $this->updatedAt = new DateTimeImmutable();
    }
}

trait HasTenant {
    protected int $tenantId;
    
    public function getTenantId(): int {
        return $this->tenantId;
    }
    
    public function belongsToTenant(int $tenantId): bool {
        return $this->tenantId === $tenantId;
    }
}

trait HasStatus {
    protected string $status;
    protected static array $allowedStatuses = [];
    
    public function getStatus(): string {
        return $this->status;
    }
    
    public function setStatus(string $status): void {
        if (!in_array($status, static::$allowedStatuses, true)) {
            throw new InvalidArgumentException("Invalid status: {$status}");
        }
        $this->status = $status;
    }
    
    public function isStatus(string $status): bool {
        return $this->status === $status;
    }
}
```

#### BaseRepository

```php
<?php
namespace CanilCore\Infrastructure\Repositories;

/**
 * Repository base com operações CRUD comuns.
 * DRY: Toda lógica de tenant, paginação e soft-delete centralizada.
 */
abstract class BaseRepository {
    protected wpdb $wpdb;
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $softDeletes = true;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . $this->getTableName();
    }
    
    abstract protected function getTableName(): string;
    abstract protected function mapToEntity(array $row): object;
    abstract protected function mapToArray(object $entity): array;
    
    // ========================================
    // TENANT ISOLATION (OBRIGATÓRIO)
    // ========================================
    
    protected function getTenantId(): int {
        $tenantId = get_current_user_id();
        if ($tenantId === 0) {
            throw new UnauthorizedException('User not authenticated');
        }
        return $tenantId;
    }
    
    protected function tenantCondition(): string {
        return $this->wpdb->prepare('tenant_id = %d', $this->getTenantId());
    }
    
    protected function softDeleteCondition(): string {
        return $this->softDeletes ? 'AND deleted_at IS NULL' : '';
    }
    
    // ========================================
    // CRUD OPERATIONS (DRY)
    // ========================================
    
    public function findById(int $id): ?object {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} 
             WHERE {$this->primaryKey} = %d 
             AND {$this->tenantCondition()} 
             {$this->softDeleteCondition()}",
            $id
        );
        
        $row = $this->wpdb->get_row($sql, ARRAY_A);
        return $row ? $this->mapToEntity($row) : null;
    }
    
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): PaginatedResult {
        $where = [$this->tenantCondition()];
        $params = [];
        
        // Adicionar filtros dinamicamente
        foreach ($filters as $field => $value) {
            if ($value !== null && $this->isFilterable($field)) {
                $where[] = $this->buildFilter($field, $value, $params);
            }
        }
        
        if ($this->softDeletes) {
            $where[] = 'deleted_at IS NULL';
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        // Count total
        $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}";
        $total = (int) $this->wpdb->get_var($countSql);
        
        // Fetch page
        $sql = "SELECT * FROM {$this->table} 
                WHERE {$whereClause} 
                ORDER BY {$this->getDefaultOrder()} 
                LIMIT %d OFFSET %d";
        
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $perPage, $offset),
            ARRAY_A
        );
        
        $items = array_map([$this, 'mapToEntity'], $rows);
        
        return new PaginatedResult($items, $total, $page, $perPage);
    }
    
    public function save(object $entity): object {
        $data = $this->mapToArray($entity);
        $data['tenant_id'] = $this->getTenantId();
        $data['updated_at'] = current_time('mysql');
        
        if (empty($data[$this->primaryKey])) {
            // INSERT
            $data['created_at'] = current_time('mysql');
            unset($data[$this->primaryKey]);
            
            $this->wpdb->insert($this->table, $data);
            $data[$this->primaryKey] = $this->wpdb->insert_id;
        } else {
            // UPDATE - verificar tenant primeiro
            $existing = $this->findById($data[$this->primaryKey]);
            if (!$existing) {
                throw new NotFoundException('Entity not found');
            }
            
            $this->wpdb->update(
                $this->table,
                $data,
                [$this->primaryKey => $data[$this->primaryKey], 'tenant_id' => $this->getTenantId()]
            );
        }
        
        return $this->findById($data[$this->primaryKey]);
    }
    
    public function delete(int $id): bool {
        // Verificar tenant antes de deletar
        $existing = $this->findById($id);
        if (!$existing) {
            throw new NotFoundException('Entity not found');
        }
        
        if ($this->softDeletes) {
            return (bool) $this->wpdb->update(
                $this->table,
                ['deleted_at' => current_time('mysql')],
                [$this->primaryKey => $id, 'tenant_id' => $this->getTenantId()]
            );
        }
        
        return (bool) $this->wpdb->delete(
            $this->table,
            [$this->primaryKey => $id, 'tenant_id' => $this->getTenantId()]
        );
    }
    
    // ========================================
    // EXTENSIBILITY HOOKS
    // ========================================
    
    protected function isFilterable(string $field): bool {
        return in_array($field, $this->getFilterableFields(), true);
    }
    
    protected function getFilterableFields(): array {
        return ['status', 'search'];
    }
    
    protected function getDefaultOrder(): string {
        return 'created_at DESC';
    }
    
    protected function buildFilter(string $field, $value, array &$params): string {
        if ($field === 'search') {
            return $this->buildSearchFilter($value);
        }
        
        $params[] = $value;
        return "{$field} = %s";
    }
    
    protected function buildSearchFilter(string $search): string {
        $searchFields = $this->getSearchableFields();
        $conditions = [];
        
        foreach ($searchFields as $field) {
            $conditions[] = "{$field} LIKE '%" . esc_sql($search) . "%'";
        }
        
        return '(' . implode(' OR ', $conditions) . ')';
    }
    
    protected function getSearchableFields(): array {
        return ['name'];
    }
}
```

#### BaseController

```php
<?php
namespace CanilCore\Rest\Controllers;

/**
 * Controller base com operações REST comuns.
 * DRY: Toda lógica de autenticação, validação e resposta centralizada.
 */
abstract class BaseController {
    protected string $namespace = 'canil/v1';
    protected string $restBase;
    protected string $capability;
    
    abstract protected function getRepository();
    abstract protected function getSchema(): array;
    
    public function registerRoutes(): void {
        // LIST
        register_rest_route($this->namespace, "/{$this->restBase}", [
            [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [$this, 'list'],
                'permission_callback' => [$this, 'checkReadPermission'],
                'args' => $this->getListArgs(),
            ],
            [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create'],
                'permission_callback' => [$this, 'checkWritePermission'],
                'args' => $this->getCreateArgs(),
            ],
        ]);
        
        // SINGLE
        register_rest_route($this->namespace, "/{$this->restBase}/(?P<id>\d+)", [
            [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [$this, 'get'],
                'permission_callback' => [$this, 'checkReadPermission'],
            ],
            [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update'],
                'permission_callback' => [$this, 'checkWritePermission'],
                'args' => $this->getUpdateArgs(),
            ],
            [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete'],
                'permission_callback' => [$this, 'checkWritePermission'],
            ],
        ]);
    }
    
    // ========================================
    // PERMISSION CHECKS (DRY)
    // ========================================
    
    public function checkReadPermission(): bool {
        return is_user_logged_in() && current_user_can($this->capability);
    }
    
    public function checkWritePermission(): bool {
        return is_user_logged_in() && current_user_can($this->capability);
    }
    
    // ========================================
    // CRUD HANDLERS (DRY)
    // ========================================
    
    public function list(WP_REST_Request $request): WP_REST_Response {
        try {
            $filters = $this->extractFilters($request);
            $page = absint($request->get_param('page')) ?: 1;
            $perPage = min(absint($request->get_param('per_page')) ?: 20, 100);
            
            $result = $this->getRepository()->findAll($filters, $page, $perPage);
            
            return $this->paginatedResponse($result);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    public function get(WP_REST_Request $request): WP_REST_Response {
        try {
            $id = absint($request->get_param('id'));
            $entity = $this->getRepository()->findById($id);
            
            if (!$entity) {
                return $this->notFoundResponse();
            }
            
            return $this->successResponse($entity);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    public function create(WP_REST_Request $request): WP_REST_Response {
        try {
            $data = $this->sanitizeInput($request->get_json_params());
            $errors = $this->validate($data, 'create');
            
            if (!empty($errors)) {
                return $this->validationErrorResponse($errors);
            }
            
            $entity = $this->getRepository()->save($this->createEntity($data));
            
            do_action("canil_core_after_create_{$this->restBase}", $entity);
            
            return $this->createdResponse($entity);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    public function update(WP_REST_Request $request): WP_REST_Response {
        try {
            $id = absint($request->get_param('id'));
            $existing = $this->getRepository()->findById($id);
            
            if (!$existing) {
                return $this->notFoundResponse();
            }
            
            $data = $this->sanitizeInput($request->get_json_params());
            $errors = $this->validate($data, 'update');
            
            if (!empty($errors)) {
                return $this->validationErrorResponse($errors);
            }
            
            $entity = $this->updateEntity($existing, $data);
            $saved = $this->getRepository()->save($entity);
            
            do_action("canil_core_after_update_{$this->restBase}", $saved);
            
            return $this->successResponse($saved);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    public function delete(WP_REST_Request $request): WP_REST_Response {
        try {
            $id = absint($request->get_param('id'));
            
            do_action("canil_core_before_delete_{$this->restBase}", $id);
            
            $deleted = $this->getRepository()->delete($id);
            
            if (!$deleted) {
                return $this->notFoundResponse();
            }
            
            do_action("canil_core_after_delete_{$this->restBase}", $id);
            
            return new WP_REST_Response(null, 204);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    // ========================================
    // RESPONSE HELPERS (DRY)
    // ========================================
    
    protected function successResponse($data, int $status = 200): WP_REST_Response {
        return new WP_REST_Response([
            'data' => $this->serialize($data),
        ], $status);
    }
    
    protected function createdResponse($data): WP_REST_Response {
        return $this->successResponse($data, 201);
    }
    
    protected function paginatedResponse(PaginatedResult $result): WP_REST_Response {
        return new WP_REST_Response([
            'data' => array_map([$this, 'serialize'], $result->getItems()),
            'meta' => [
                'total' => $result->getTotal(),
                'page' => $result->getPage(),
                'per_page' => $result->getPerPage(),
                'total_pages' => $result->getTotalPages(),
            ],
        ], 200);
    }
    
    protected function notFoundResponse(): WP_REST_Response {
        return new WP_REST_Response([
            'code' => 'not_found',
            'message' => 'Resource not found',
        ], 404);
    }
    
    protected function validationErrorResponse(array $errors): WP_REST_Response {
        return new WP_REST_Response([
            'code' => 'validation_error',
            'message' => 'Validation failed',
            'data' => ['errors' => $errors],
        ], 400);
    }
    
    protected function errorResponse(Exception $e): WP_REST_Response {
        $status = 500;
        
        if ($e instanceof NotFoundException) {
            $status = 404;
        } elseif ($e instanceof ValidationException) {
            $status = 400;
        } elseif ($e instanceof UnauthorizedException) {
            $status = 401;
        } elseif ($e instanceof ForbiddenException) {
            $status = 403;
        }
        
        return new WP_REST_Response([
            'code' => 'error',
            'message' => $e->getMessage(),
        ], $status);
    }
    
    // ========================================
    // ABSTRACT METHODS (para implementação)
    // ========================================
    
    abstract protected function createEntity(array $data): object;
    abstract protected function updateEntity(object $entity, array $data): object;
    abstract protected function serialize(object $entity): array;
    abstract protected function sanitizeInput(array $data): array;
    abstract protected function validate(array $data, string $context): array;
    abstract protected function extractFilters(WP_REST_Request $request): array;
}
```

### 2.2 Helpers Centralizados

#### Sanitizer (Único ponto de sanitização)

```php
<?php
namespace CanilCore\Helpers;

/**
 * Sanitização centralizada.
 * DRY: Um único lugar para todas as regras de sanitização.
 */
class Sanitizer {
    public static function text(?string $value): string {
        return sanitize_text_field($value ?? '');
    }
    
    public static function email(?string $value): string {
        return sanitize_email($value ?? '');
    }
    
    public static function int($value): int {
        return absint($value);
    }
    
    public static function float($value): float {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    public static function date(?string $value): ?string {
        if (empty($value)) {
            return null;
        }
        
        try {
            $date = new DateTimeImmutable($value);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }
    
    public static function datetime(?string $value): ?string {
        if (empty($value)) {
            return null;
        }
        
        try {
            $date = new DateTimeImmutable($value);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }
    
    public static function enum(?string $value, array $allowed, ?string $default = null): ?string {
        if ($value === null || !in_array($value, $allowed, true)) {
            return $default;
        }
        return $value;
    }
    
    public static function html(?string $value): string {
        return wp_kses_post($value ?? '');
    }
    
    public static function url(?string $value): string {
        return esc_url_raw($value ?? '');
    }
    
    public static function array(array $value, callable $sanitizer): array {
        return array_map($sanitizer, $value);
    }
    
    /**
     * Sanitiza objeto baseado em schema.
     * DRY: Define regras uma vez, aplica em qualquer lugar.
     */
    public static function fromSchema(array $data, array $schema): array {
        $sanitized = [];
        
        foreach ($schema as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }
            
            $sanitized[$field] = match ($type) {
                'text', 'string' => self::text($data[$field]),
                'email' => self::email($data[$field]),
                'int', 'integer' => self::int($data[$field]),
                'float', 'decimal' => self::float($data[$field]),
                'date' => self::date($data[$field]),
                'datetime' => self::datetime($data[$field]),
                'html' => self::html($data[$field]),
                'url' => self::url($data[$field]),
                'bool', 'boolean' => (bool) $data[$field],
                default => $data[$field],
            };
        }
        
        return $sanitized;
    }
}
```

#### Validator (Único ponto de validação)

```php
<?php
namespace CanilCore\Helpers;

/**
 * Validação centralizada.
 * DRY: Regras de validação reutilizáveis.
 */
class Validator {
    private array $errors = [];
    private array $data;
    
    public function __construct(array $data) {
        $this->data = $data;
    }
    
    public static function make(array $data): self {
        return new self($data);
    }
    
    public function required(string $field, string $message = null): self {
        if (empty($this->data[$field])) {
            $this->errors[$field][] = $message ?? "{$field} is required";
        }
        return $this;
    }
    
    public function email(string $field, string $message = null): self {
        if (!empty($this->data[$field]) && !is_email($this->data[$field])) {
            $this->errors[$field][] = $message ?? "Invalid email format";
        }
        return $this;
    }
    
    public function date(string $field, string $message = null): self {
        if (!empty($this->data[$field])) {
            try {
                new DateTimeImmutable($this->data[$field]);
            } catch (Exception $e) {
                $this->errors[$field][] = $message ?? "Invalid date format";
            }
        }
        return $this;
    }
    
    public function in(string $field, array $allowed, string $message = null): self {
        if (!empty($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field][] = $message ?? "Invalid value for {$field}";
        }
        return $this;
    }
    
    public function min(string $field, int $min, string $message = null): self {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field][] = $message ?? "{$field} must be at least {$min} characters";
        }
        return $this;
    }
    
    public function max(string $field, int $max, string $message = null): self {
        if (!empty($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field][] = $message ?? "{$field} must not exceed {$max} characters";
        }
        return $this;
    }
    
    public function numeric(string $field, string $message = null): self {
        if (!empty($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field][] = $message ?? "{$field} must be numeric";
        }
        return $this;
    }
    
    public function positive(string $field, string $message = null): self {
        if (!empty($this->data[$field]) && (float) $this->data[$field] <= 0) {
            $this->errors[$field][] = $message ?? "{$field} must be positive";
        }
        return $this;
    }
    
    public function custom(string $field, callable $callback, string $message): self {
        if (!$callback($this->data[$field] ?? null, $this->data)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }
    
    public function fails(): bool {
        return !empty($this->errors);
    }
    
    public function passes(): bool {
        return empty($this->errors);
    }
    
    public function errors(): array {
        return $this->errors;
    }
}
```

### 2.3 Constantes Centralizadas

```php
<?php
namespace CanilCore\Constants;

/**
 * Constantes centralizadas.
 * DRY: Valores mágicos definidos em um só lugar.
 */
final class DogStatus {
    public const ACTIVE = 'active';
    public const BREEDING = 'breeding';
    public const RETIRED = 'retired';
    public const SOLD = 'sold';
    public const DECEASED = 'deceased';
    public const COOWNED = 'coowned';
    
    public static function all(): array {
        return [
            self::ACTIVE,
            self::BREEDING,
            self::RETIRED,
            self::SOLD,
            self::DECEASED,
            self::COOWNED,
        ];
    }
    
    public static function labels(): array {
        return [
            self::ACTIVE => __('Ativo', 'canil-core'),
            self::BREEDING => __('Reprodução', 'canil-core'),
            self::RETIRED => __('Aposentado', 'canil-core'),
            self::SOLD => __('Vendido', 'canil-core'),
            self::DECEASED => __('Falecido', 'canil-core'),
            self::COOWNED => __('Co-propriedade', 'canil-core'),
        ];
    }
    
    public static function isValid(string $status): bool {
        return in_array($status, self::all(), true);
    }
}

final class LitterStatus {
    public const PLANNED = 'planned';
    public const CONFIRMED = 'confirmed';
    public const PREGNANT = 'pregnant';
    public const BORN = 'born';
    public const WEANED = 'weaned';
    public const CLOSED = 'closed';
    public const CANCELLED = 'cancelled';
    
    public static function all(): array {
        return [
            self::PLANNED,
            self::CONFIRMED,
            self::PREGNANT,
            self::BORN,
            self::WEANED,
            self::CLOSED,
            self::CANCELLED,
        ];
    }
}

final class PuppyStatus {
    public const AVAILABLE = 'available';
    public const RESERVED = 'reserved';
    public const SOLD = 'sold';
    public const RETAINED = 'retained';
    public const DECEASED = 'deceased';
    public const RETURNED = 'returned';
    
    public static function all(): array {
        return [
            self::AVAILABLE,
            self::RESERVED,
            self::SOLD,
            self::RETAINED,
            self::DECEASED,
            self::RETURNED,
        ];
    }
}

final class EventType {
    // Saúde
    public const VACCINE = 'vaccine';
    public const DEWORMING = 'deworming';
    public const EXAM = 'exam';
    public const MEDICATION = 'medication';
    public const SURGERY = 'surgery';
    public const VET_VISIT = 'vet_visit';
    
    // Reprodução
    public const HEAT = 'heat';
    public const MATING = 'mating';
    public const PREGNANCY_TEST = 'pregnancy_test';
    public const BIRTH = 'birth';
    
    // Outros
    public const WEIGHING = 'weighing';
    public const GROOMING = 'grooming';
    public const TRAINING = 'training';
    public const SHOW = 'show';
    public const NOTE = 'note';
    
    public static function all(): array {
        return [
            self::VACCINE, self::DEWORMING, self::EXAM, self::MEDICATION,
            self::SURGERY, self::VET_VISIT, self::HEAT, self::MATING,
            self::PREGNANCY_TEST, self::BIRTH, self::WEIGHING, self::GROOMING,
            self::TRAINING, self::SHOW, self::NOTE,
        ];
    }
    
    public static function healthEvents(): array {
        return [self::VACCINE, self::DEWORMING, self::EXAM, self::MEDICATION, self::SURGERY, self::VET_VISIT];
    }
    
    public static function reproductionEvents(): array {
        return [self::HEAT, self::MATING, self::PREGNANCY_TEST, self::BIRTH];
    }
}

final class Capabilities {
    public const MANAGE_KENNEL = 'manage_kennel';
    public const MANAGE_DOGS = 'manage_dogs';
    public const MANAGE_LITTERS = 'manage_litters';
    public const MANAGE_PUPPIES = 'manage_puppies';
    public const MANAGE_PEOPLE = 'manage_people';
    public const VIEW_REPORTS = 'view_reports';
    public const MANAGE_SETTINGS = 'manage_settings';
    
    public static function all(): array {
        return [
            self::MANAGE_KENNEL,
            self::MANAGE_DOGS,
            self::MANAGE_LITTERS,
            self::MANAGE_PUPPIES,
            self::MANAGE_PEOPLE,
            self::VIEW_REPORTS,
            self::MANAGE_SETTINGS,
        ];
    }
}
```

---

## 3. Padrões de Código

### 3.1 PHP

#### Nomenclatura

```php
// Classes: PascalCase
class DogRepository {}
class ReproductionService {}

// Métodos e propriedades: camelCase
public function findById(int $id): ?Dog {}
private int $tenantId;

// Constantes: UPPER_SNAKE_CASE
public const MAX_DOGS_PER_PAGE = 100;

// Arquivos: PascalCase para classes
// Dog.php, DogRepository.php, DogSchema.php
```

#### Tipagem Estrita

```php
<?php
declare(strict_types=1);

namespace CanilCore\Domain\Entities;

// SEMPRE tipar parâmetros e retornos
public function findById(int $id): ?Dog {}
public function save(Dog $dog): Dog {}
public function findAll(array $filters = []): PaginatedResult {}

// Usar union types quando necessário
public function getValue(): string|int {}

// Nullable quando pode ser null
public function getChipNumber(): ?string {}
```

#### DocBlocks (quando necessário)

```php
/**
 * Registra evento de cobertura e cria ninhada.
 *
 * @param Dog $dam Matriz (fêmea)
 * @param Dog $sire Reprodutor (macho)
 * @param DateTimeImmutable $date Data da cobertura
 * @param array{type: string, attempts?: int} $details Detalhes da cobertura
 * 
 * @return Litter Ninhada criada
 * 
 * @throws InvalidArgumentException Se dam não for fêmea ou sire não for macho
 * @throws DomainException Se dam já estiver gestante
 */
public function recordMating(Dog $dam, Dog $sire, DateTimeImmutable $date, array $details): Litter {}
```

### 3.2 JavaScript/React

#### Nomenclatura

```javascript
// Componentes: PascalCase
function DogCard({ dog }) {}
function DogListPage() {}

// Hooks: camelCase com prefixo use
function useDogs() {}
function useApi() {}

// Funções e variáveis: camelCase
const fetchDogs = async () => {};
const isLoading = true;

// Constantes: UPPER_SNAKE_CASE
const API_NAMESPACE = '/canil/v1';
const MAX_RESULTS = 100;

// Arquivos de componentes: PascalCase.jsx
// DogCard.jsx, DogListPage.jsx

// Arquivos de hooks/utils: camelCase.js
// useDogs.js, api.js
```

#### Componentes DRY

```jsx
// ❌ ERRADO: Código duplicado
function DogCard({ dog }) {
  return (
    <div className="card">
      <img src={dog.photo} alt={dog.name} />
      <h3>{dog.name}</h3>
      <span className={`status status-${dog.status}`}>{dog.status}</span>
    </div>
  );
}

function PuppyCard({ puppy }) {
  return (
    <div className="card">
      <img src={puppy.photo} alt={puppy.name} />
      <h3>{puppy.name}</h3>
      <span className={`status status-${puppy.status}`}>{puppy.status}</span>
    </div>
  );
}

// ✅ CORRETO: Componente reutilizável
function EntityCard({ entity, type }) {
  return (
    <Card>
      <CardMedia src={entity.photo} alt={entity.name} />
      <CardTitle>{entity.name}</CardTitle>
      <StatusBadge status={entity.status} />
    </Card>
  );
}

// Uso
<EntityCard entity={dog} type="dog" />
<EntityCard entity={puppy} type="puppy" />
```

#### Custom Hooks DRY

```javascript
// Hook genérico para CRUD
function useEntity(endpoint) {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [pagination, setPagination] = useState({ page: 1, total: 0 });

  const fetchItems = useCallback(async (params = {}) => {
    setLoading(true);
    try {
      const response = await apiFetch({
        path: `${endpoint}?${new URLSearchParams(params)}`,
      });
      setItems(response.data);
      setPagination(response.meta);
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  }, [endpoint]);

  const createItem = useCallback(async (data) => {
    const response = await apiFetch({
      path: endpoint,
      method: 'POST',
      data,
    });
    setItems((prev) => [response.data, ...prev]);
    return response.data;
  }, [endpoint]);

  const updateItem = useCallback(async (id, data) => {
    const response = await apiFetch({
      path: `${endpoint}/${id}`,
      method: 'PUT',
      data,
    });
    setItems((prev) => prev.map((item) => (item.id === id ? response.data : item)));
    return response.data;
  }, [endpoint]);

  const deleteItem = useCallback(async (id) => {
    await apiFetch({
      path: `${endpoint}/${id}`,
      method: 'DELETE',
    });
    setItems((prev) => prev.filter((item) => item.id !== id));
  }, [endpoint]);

  return {
    items,
    loading,
    error,
    pagination,
    fetchItems,
    createItem,
    updateItem,
    deleteItem,
  };
}

// Uso específico
function useDogs() {
  return useEntity('/canil/v1/dogs');
}

function useLitters() {
  return useEntity('/canil/v1/litters');
}
```

---

## 4. Checklist de Code Review

### 4.1 DRY

- [ ] Não há código duplicado?
- [ ] Lógica comum foi extraída para funções/classes/traits?
- [ ] Constantes estão centralizadas?
- [ ] Validações usam helpers centralizados?
- [ ] Componentes são reutilizáveis?

### 4.2 Segurança

- [ ] tenant_id vem do servidor (nunca do request)?
- [ ] Permissões são verificadas em todas as ações?
- [ ] Inputs são sanitizados antes de usar?
- [ ] Outputs são escapados antes de exibir?
- [ ] Queries usam $wpdb->prepare()?

### 4.3 SOLID

- [ ] Classes têm responsabilidade única?
- [ ] Código é extensível sem modificar existente?
- [ ] Interfaces são pequenas e específicas?
- [ ] Dependências são injetadas?

### 4.4 Qualidade

- [ ] Código tem tipagem estrita?
- [ ] Nomenclatura segue padrões?
- [ ] DocBlocks necessários estão presentes?
- [ ] Testes cobrem o novo código?
- [ ] Não há magic numbers/strings?

---

## 5. Anti-Patterns a Evitar

### 5.1 Código Duplicado

```php
// ❌ ERRADO
class DogController {
    public function create(WP_REST_Request $request) {
        if (!is_user_logged_in()) {
            return new WP_Error('unauthorized', 'Not logged in', ['status' => 401]);
        }
        if (!current_user_can('manage_dogs')) {
            return new WP_Error('forbidden', 'No permission', ['status' => 403]);
        }
        // ... criar cão
    }
}

class LitterController {
    public function create(WP_REST_Request $request) {
        if (!is_user_logged_in()) {
            return new WP_Error('unauthorized', 'Not logged in', ['status' => 401]);
        }
        if (!current_user_can('manage_litters')) {
            return new WP_Error('forbidden', 'No permission', ['status' => 403]);
        }
        // ... criar ninhada
    }
}

// ✅ CORRETO: Usar permission_callback + BaseController
class DogsController extends BaseController {
    protected string $capability = 'manage_dogs';
    // Herda checkWritePermission() do BaseController
}
```

### 5.2 Magic Numbers/Strings

```php
// ❌ ERRADO
if ($dog->status === 'breeding') {}
if ($page > 100) { $page = 100; }

// ✅ CORRETO
if ($dog->status === DogStatus::BREEDING) {}
if ($page > self::MAX_PAGE_SIZE) { $page = self::MAX_PAGE_SIZE; }
```

### 5.3 God Classes

```php
// ❌ ERRADO: Uma classe faz tudo
class DogManager {
    public function createDog() {}
    public function updateDog() {}
    public function deleteDog() {}
    public function validateDog() {}
    public function sanitizeDog() {}
    public function sendDogEmail() {}
    public function exportDogToPdf() {}
    public function generateDogPedigree() {}
}

// ✅ CORRETO: Separar responsabilidades
class DogRepository { /* CRUD */ }
class DogValidator { /* Validação */ }
class DogService { /* Regras de negócio */ }
class DogExporter { /* Exportação */ }
class PedigreeService { /* Pedigree */ }
```

### 5.4 Tenant ID do Cliente

```php
// ❌ ERRADO: VULNERABILIDADE CRÍTICA!
$tenantId = $request->get_param('tenant_id');
$dogs = $repo->findByTenant($tenantId);

// ✅ CORRETO: Sempre do servidor
$tenantId = get_current_user_id();
$dogs = $repo->findAll(); // já filtra por tenant internamente
```

---

## 6. Performance

### 6.1 N+1 Queries

```php
// ❌ ERRADO: N+1 queries
$dogs = $this->dogRepo->findAll();
foreach ($dogs as $dog) {
    $dog->litters = $this->litterRepo->findByDog($dog->id); // Query por cão!
}

// ✅ CORRETO: Eager loading
$dogs = $this->dogRepo->findAllWithLitters();
// Ou
$dogs = $this->dogRepo->findAll();
$dogIds = array_column($dogs, 'id');
$litters = $this->litterRepo->findByDogIds($dogIds);
// Mapa e associa
```

### 6.2 Caching DRY

```php
// Trait reutilizável para cache
trait HasCache {
    protected function remember(string $key, int $ttl, callable $callback) {
        $value = get_transient($key);
        
        if ($value === false) {
            $value = $callback();
            set_transient($key, $value, $ttl);
        }
        
        return $value;
    }
    
    protected function forget(string $key): void {
        delete_transient($key);
    }
    
    protected function cacheKey(string $prefix): string {
        return "{$prefix}_{$this->getTenantId()}";
    }
}
```

---

## 7. Testes

### 7.1 DRY em Testes

```php
// Trait com helpers de teste
trait TestHelpers {
    protected function createTenantUser(): int {
        return $this->factory()->user->create(['role' => 'kennel_owner']);
    }
    
    protected function actingAs(int $userId): void {
        wp_set_current_user($userId);
    }
    
    protected function createDog(array $overrides = []): Dog {
        return $this->dogService->create(array_merge([
            'name' => 'Test Dog',
            'breed' => 'Golden Retriever',
            'birth_date' => '2020-01-01',
            'sex' => 'male',
        ], $overrides));
    }
    
    protected function assertTenantIsolation(callable $action): void {
        $user1 = $this->createTenantUser();
        $user2 = $this->createTenantUser();
        
        $this->actingAs($user1);
        $entity = $action();
        
        $this->actingAs($user2);
        $this->assertNull($this->findById($entity->getId()));
    }
}

// Uso
class DogTest extends WP_UnitTestCase {
    use TestHelpers;
    
    public function test_tenant_isolation(): void {
        $this->assertTenantIsolation(fn() => $this->createDog());
    }
}
```

---

*Documento gerado em: 02/02/2026*
