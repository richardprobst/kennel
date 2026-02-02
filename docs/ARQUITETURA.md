# ARQUITETURA.md - Arquitetura do Sistema

## Plugin WordPress para Gestão de Canil (SaaS Multi-tenant)

**Data:** 02/02/2026  
**Versão:** 1.0

---

## 1. Visão Arquitetural

### 1.1 Princípios Fundamentais

1. **Multi-tenant por Usuário**: Cada usuário WordPress = 1 Tenant/Canil
2. **Isolamento de Dados**: Nenhum dado vazando entre tenants
3. **Core Extensível**: Funcionalidades básicas + hooks para add-ons
4. **Separação de Camadas**: Domain, Infrastructure, REST, AdminUI
5. **Segurança por Design**: Validação, sanitização, capabilities
6. **API-First**: UI consome REST API pública

### 1.2 Diagrama de Alto Nível

```
┌─────────────────────────────────────────────────────────────────┐
│                         WordPress                                │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │                    Plugin Core (canil-core)                  │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │ │
│  │  │  REST API   │  │  Admin UI   │  │     Migrations      │  │ │
│  │  │ /canil/v1/* │  │   (React)   │  │  (DB Schema)        │  │ │
│  │  └──────┬──────┘  └──────┬──────┘  └─────────────────────┘  │ │
│  │         │                │                                    │ │
│  │  ┌──────┴────────────────┴──────┐                            │ │
│  │  │         Domain Layer         │◄──── Business Rules         │ │
│  │  │   (Entities, Services, DTOs) │                            │ │
│  │  └──────────────┬───────────────┘                            │ │
│  │                 │                                             │ │
│  │  ┌──────────────┴───────────────┐                            │ │
│  │  │     Infrastructure Layer     │◄──── Data Access           │ │
│  │  │ (Repositories, Queries, Maps)│                            │ │
│  │  └──────────────┬───────────────┘                            │ │
│  │                 │                                             │ │
│  │  ┌──────────────┴───────────────┐                            │ │
│  │  │      MySQL/MariaDB           │◄──── tenant_id isolation   │ │
│  │  │   (Custom Tables + WP Meta)  │                            │ │
│  │  └──────────────────────────────┘                            │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                              │                                    │
│              ┌───────────────┼───────────────┐                   │
│              │               │               │                   │
│  ┌───────────┴───┐  ┌───────┴──────┐  ┌────┴────────┐          │
│  │ Add-on        │  │ Add-on       │  │ Add-on      │          │
│  │ Financeiro    │  │ CRM          │  │ Site Público│          │
│  └───────────────┘  └──────────────┘  └─────────────┘          │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Estrutura de Diretórios

```
/
├── plugin-core/                    # Plugin principal
│   ├── canil-core.php              # Bootstrap do plugin
│   ├── composer.json               # Dependências PHP
│   ├── package.json                # Dependências JS/Build
│   ├── includes/
│   │   ├── Domain/                 # Camada de Domínio
│   │   │   ├── Entities/           # Entidades de negócio
│   │   │   │   ├── Dog.php
│   │   │   │   ├── Litter.php
│   │   │   │   ├── Puppy.php
│   │   │   │   ├── Person.php
│   │   │   │   └── Event.php
│   │   │   ├── Services/           # Regras de negócio
│   │   │   │   ├── DogService.php
│   │   │   │   ├── LitterService.php
│   │   │   │   ├── ReproductionService.php
│   │   │   │   └── HealthService.php
│   │   │   ├── DTOs/               # Data Transfer Objects
│   │   │   │   ├── DogDTO.php
│   │   │   │   └── LitterDTO.php
│   │   │   └── Exceptions/         # Exceções de domínio
│   │   │       └── DomainException.php
│   │   │
│   │   ├── Infrastructure/         # Camada de Infraestrutura
│   │   │   ├── Repositories/       # Acesso a dados
│   │   │   │   ├── BaseRepository.php
│   │   │   │   ├── DogRepository.php
│   │   │   │   ├── LitterRepository.php
│   │   │   │   ├── PuppyRepository.php
│   │   │   │   ├── PersonRepository.php
│   │   │   │   └── EventRepository.php
│   │   │   ├── Queries/            # Query builders
│   │   │   │   └── TenantQuery.php
│   │   │   └── Mappers/            # Entity-DB mappers
│   │   │       └── DogMapper.php
│   │   │
│   │   ├── Rest/                   # Camada REST API
│   │   │   ├── Controllers/        # Endpoints
│   │   │   │   ├── DogsController.php
│   │   │   │   ├── LittersController.php
│   │   │   │   ├── PuppiesController.php
│   │   │   │   ├── PeopleController.php
│   │   │   │   └── EventsController.php
│   │   │   ├── Schemas/            # JSON Schemas
│   │   │   │   ├── DogSchema.php
│   │   │   │   └── LitterSchema.php
│   │   │   └── Middleware/         # Middleware
│   │   │       └── TenantMiddleware.php
│   │   │
│   │   ├── AdminUI/                # Integração Admin WP
│   │   │   ├── AdminMenu.php       # Menus
│   │   │   ├── AdminAssets.php     # Enqueue scripts/styles
│   │   │   └── AdminRoutes.php     # SPA routes
│   │   │
│   │   ├── Core/                   # Núcleo do plugin
│   │   │   ├── Activator.php       # Ativação
│   │   │   ├── Deactivator.php     # Desativação
│   │   │   ├── Capabilities.php    # Roles/Caps
│   │   │   ├── Hooks.php           # Actions/Filters
│   │   │   └── Container.php       # DI Container (simples)
│   │   │
│   │   └── Helpers/                # Utilitários
│   │       ├── Sanitizer.php
│   │       ├── Validator.php
│   │       └── DateHelper.php
│   │
│   ├── migrations/                 # Migrações de DB
│   │   ├── 001_create_dogs_table.php
│   │   ├── 002_create_litters_table.php
│   │   └── ...
│   │
│   ├── tests/                      # Testes automatizados
│   │   ├── Unit/
│   │   │   └── Domain/
│   │   ├── Integration/
│   │   │   ├── REST/
│   │   │   └── Repository/
│   │   └── bootstrap.php
│   │
│   ├── assets-admin/               # Build do React (gerado)
│   │   ├── js/
│   │   └── css/
│   │
│   └── assets-admin-src/           # Fonte React
│       ├── src/
│       │   ├── components/
│       │   ├── pages/
│       │   ├── hooks/
│       │   ├── store/
│       │   └── App.jsx
│       ├── package.json
│       └── webpack.config.js
│
├── addons/                         # Add-ons (plugins separados)
│   ├── canil-financeiro/
│   ├── canil-crm/
│   ├── canil-genetica/
│   └── canil-site-publico/
│
├── docs/                           # Documentação
│   ├── PRD.md
│   ├── ARQUITETURA.md
│   ├── ROADMAP.md
│   ├── API.md
│   ├── DB.md
│   └── UX.md
│
├── .github/
│   ├── copilot-instructions.md
│   ├── workflows/
│   │   ├── ci.yml
│   │   └── deploy.yml
│   ├── PULL_REQUEST_TEMPLATE.md
│   └── ISSUE_TEMPLATE/
│       ├── feature.md
│       └── bug.md
│
├── AGENTS.md                       # Instruções para AI agents
├── Guia.md                         # Guia original
└── README.md                       # Readme do projeto
```

---

## 3. Camadas da Arquitetura

### 3.1 Domain Layer (Domínio)

**Responsabilidade**: Regras de negócio puras, independentes de WP quando possível.

#### Entidades Principais

```php
// Dog.php
class Dog {
    private int $id;
    private int $tenantId;
    private string $name;
    private ?string $registrationNumber;
    private ?string $chipNumber;
    private string $breed;
    private string $color;
    private DateTimeImmutable $birthDate;
    private string $sex; // 'male' | 'female'
    private string $status; // 'active' | 'breeding' | 'retired' | 'sold' | 'deceased'
    private ?int $sireId; // pai
    private ?int $damId;  // mãe
    private array $photos;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
}

// Litter.php
class Litter {
    private int $id;
    private int $tenantId;
    private int $damId;     // matriz
    private int $sireId;    // reprodutor
    private string $status; // 'planned' | 'confirmed' | 'pregnant' | 'born' | 'closed'
    private ?DateTimeImmutable $matingDate;
    private ?DateTimeImmutable $expectedBirthDate;
    private ?DateTimeImmutable $actualBirthDate;
    private ?string $notes;
}

// Puppy.php
class Puppy {
    private int $id;
    private int $tenantId;
    private int $litterId;
    private string $identifier; // ex: "A1", "Macho 1"
    private string $sex;
    private string $color;
    private ?float $birthWeight;
    private string $status; // 'available' | 'reserved' | 'sold' | 'retained' | 'deceased'
    private ?int $buyerId; // Person.id
    private ?string $chipNumber;
    private array $photos;
}

// Person.php
class Person {
    private int $id;
    private int $tenantId;
    private string $name;
    private ?string $email;
    private ?string $phone;
    private ?string $address;
    private string $type; // 'interested' | 'buyer' | 'veterinarian' | 'partner'
    private ?string $notes;
}

// Event.php
class Event {
    private int $id;
    private int $tenantId;
    private string $entityType; // 'dog' | 'litter' | 'puppy'
    private int $entityId;
    private string $eventType; // 'vaccine' | 'deworming' | 'heat' | 'mating' | 'weighing' | ...
    private DateTimeImmutable $eventDate;
    private array $payload; // dados específicos do evento em JSON
    private ?string $notes;
}
```

#### Services

```php
// ReproductionService.php
class ReproductionService {
    public function startHeat(Dog $dam, DateTimeImmutable $date): Event;
    public function recordMating(Litter $litter, DateTimeImmutable $date, array $details): Event;
    public function confirmPregnancy(Litter $litter, DateTimeImmutable $date): Event;
    public function recordBirth(Litter $litter, array $puppies, DateTimeImmutable $date): array;
    public function calculateExpectedBirthDate(DateTimeImmutable $matingDate): DateTimeImmutable;
}
```

### 3.2 Infrastructure Layer (Infraestrutura)

**Responsabilidade**: Persistência, acesso a dados, sempre com filtro de tenant.

#### Base Repository Pattern

```php
abstract class BaseRepository {
    protected wpdb $wpdb;
    protected string $table;
    
    protected function getTenantId(): int {
        $tenantId = get_current_user_id();
        if ($tenantId === 0) {
            throw new UnauthorizedException('User not authenticated');
        }
        return $tenantId;
    }
    
    protected function applyTenantFilter(string $query, array $params = []): array {
        // SEMPRE adiciona tenant_id às queries
        return [$query . ' AND tenant_id = %d', array_merge($params, [$this->getTenantId()])];
    }
}
```

#### Repository Exemplo

```php
class DogRepository extends BaseRepository {
    public function findById(int $id): ?Dog {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d AND tenant_id = %d",
            $id,
            $this->getTenantId()
        );
        $row = $this->wpdb->get_row($query, ARRAY_A);
        return $row ? DogMapper::fromArray($row) : null;
    }
    
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): PaginatedResult {
        $tenantId = $this->getTenantId();
        // Query com filtros, sempre incluindo tenant_id
    }
    
    public function save(Dog $dog): Dog {
        // Força tenant_id do servidor
        $data = DogMapper::toArray($dog);
        $data['tenant_id'] = $this->getTenantId();
        // INSERT ou UPDATE
    }
}
```

### 3.3 REST Layer (API)

**Responsabilidade**: Endpoints HTTP, validação de request, autenticação.

#### Controller Base

```php
abstract class BaseController {
    protected string $namespace = 'canil/v1';
    
    abstract public function registerRoutes(): void;
    
    protected function checkPermission(string $capability): bool {
        return current_user_can($capability);
    }
    
    protected function requireAuth(WP_REST_Request $request): void {
        if (!is_user_logged_in()) {
            throw new UnauthorizedException('Authentication required');
        }
    }
}
```

#### Controller Exemplo

```php
class DogsController extends BaseController {
    public function registerRoutes(): void {
        register_rest_route($this->namespace, '/dogs', [
            [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [$this, 'list'],
                'permission_callback' => fn() => $this->checkPermission('manage_dogs'),
                'args' => $this->getListArgs(),
            ],
            [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create'],
                'permission_callback' => fn() => $this->checkPermission('manage_dogs'),
                'args' => DogSchema::getCreateArgs(),
            ],
        ]);
        
        register_rest_route($this->namespace, '/dogs/(?P<id>\d+)', [
            [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [$this, 'get'],
                'permission_callback' => fn() => $this->checkPermission('manage_dogs'),
            ],
            [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update'],
                'permission_callback' => fn() => $this->checkPermission('manage_dogs'),
            ],
            [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete'],
                'permission_callback' => fn() => $this->checkPermission('manage_dogs'),
            ],
        ]);
    }
}
```

### 3.4 Admin UI Layer

**Responsabilidade**: Interface do usuário no wp-admin usando React.

#### Estrutura React

```
assets-admin-src/
├── src/
│   ├── App.jsx                 # Router principal
│   ├── index.js                # Entry point
│   │
│   ├── components/             # Componentes reutilizáveis
│   │   ├── common/
│   │   │   ├── DataTable.jsx
│   │   │   ├── FormField.jsx
│   │   │   ├── Modal.jsx
│   │   │   └── Pagination.jsx
│   │   ├── dogs/
│   │   │   ├── DogCard.jsx
│   │   │   ├── DogForm.jsx
│   │   │   └── DogList.jsx
│   │   └── litters/
│   │       └── ...
│   │
│   ├── pages/                  # Páginas/Rotas
│   │   ├── Dashboard.jsx
│   │   ├── Dogs/
│   │   │   ├── DogListPage.jsx
│   │   │   ├── DogCreatePage.jsx
│   │   │   └── DogEditPage.jsx
│   │   └── Litters/
│   │       └── ...
│   │
│   ├── hooks/                  # Custom hooks
│   │   ├── useDogs.js
│   │   ├── useLitters.js
│   │   └── useApi.js
│   │
│   ├── store/                  # Estado global (opcional)
│   │   └── index.js
│   │
│   └── utils/                  # Utilitários
│       ├── api.js
│       └── validation.js
│
└── package.json
```

---

## 4. Modelo de Dados

Ver documento [DB.md](./DB.md) para detalhamento completo das tabelas.

### 4.1 Diagrama ER Simplificado

```
┌─────────────────┐       ┌─────────────────┐
│     wp_users    │       │  canil_dogs     │
│  (WordPress)    │◄──────│  tenant_id (FK) │
│                 │   1:N │  sire_id (self) │
│                 │       │  dam_id (self)  │
└─────────────────┘       └────────┬────────┘
                                   │
                           ┌───────┴───────┐
                           │               │
               ┌───────────┴───┐   ┌───────┴───────────┐
               │ canil_litters │   │   canil_events    │
               │  dam_id (FK)  │   │   entity_type     │
               │  sire_id (FK) │   │   entity_id       │
               └───────┬───────┘   └───────────────────┘
                       │
               ┌───────┴───────┐
               │ canil_puppies │
               │  litter_id    │
               │  buyer_id (FK)│
               └───────┬───────┘
                       │
               ┌───────┴───────┐
               │ canil_people  │
               └───────────────┘
```

### 4.2 Convenção de Tabelas

- Prefixo: `{$wpdb->prefix}canil_`
- Todas com `tenant_id` indexado
- Timestamps: `created_at`, `updated_at`
- Soft delete: `deleted_at` (opcional)

---

## 5. Segurança

### 5.1 Multi-tenant Enforcement

```php
// CORRETO: tenant sempre do servidor
$tenantId = get_current_user_id();

// ERRADO: nunca aceitar do request
$tenantId = $request->get_param('tenant_id'); // PROIBIDO!
```

### 5.2 Capabilities

```php
// Capabilities definidas na ativação
$caps = [
    'manage_kennel'   => 'Gerenciar configurações do canil',
    'manage_dogs'     => 'CRUD de cães',
    'manage_litters'  => 'CRUD de ninhadas',
    'manage_puppies'  => 'CRUD de filhotes',
    'manage_people'   => 'CRUD de pessoas',
    'view_reports'    => 'Visualizar relatórios',
    'manage_settings' => 'Alterar configurações',
];

// Role padrão
$role = add_role('kennel_owner', 'Proprietário de Canil', $caps);
```

### 5.3 Validação e Sanitização

```php
// Entrada
$name = sanitize_text_field($request->get_param('name'));
$email = sanitize_email($request->get_param('email'));
$id = absint($request->get_param('id'));
$date = DateHelper::parseISO($request->get_param('date'));

// Saída
esc_html($dog->getName());
esc_attr($dog->getStatus());
esc_url($photo->getUrl());
```

---

## 6. Extensibilidade

### 6.1 Hooks do Core

```php
// Actions
do_action('canil_core_before_save_dog', $dog);
do_action('canil_core_after_save_dog', $dog);
do_action('canil_core_before_save_litter', $litter);
do_action('canil_core_after_birth_recorded', $litter, $puppies);
do_action('canil_core_event_created', $event);

// Filters
$eventTypes = apply_filters('canil_core_event_types', $defaultTypes);
$dogStatuses = apply_filters('canil_core_dog_statuses', $defaultStatuses);
$exportColumns = apply_filters('canil_core_export_columns', $columns);
```

### 6.2 Add-on Pattern

```php
// canil-financeiro/canil-financeiro.php
add_action('canil_core_after_puppy_sold', function($puppy, $buyer) {
    // Criar registro financeiro
    FinanceiroService::createSale($puppy, $buyer);
});

// Adicionar tipo de evento
add_filter('canil_core_event_types', function($types) {
    $types['payment'] = 'Pagamento';
    $types['invoice'] = 'Fatura';
    return $types;
});
```

---

## 7. Performance

### 7.1 Índices Obrigatórios

```sql
-- Todas as tabelas
CREATE INDEX idx_tenant ON canil_dogs(tenant_id);
CREATE INDEX idx_tenant_status ON canil_dogs(tenant_id, status);

-- Específicos
CREATE INDEX idx_litter_dam ON canil_litters(dam_id);
CREATE INDEX idx_puppy_litter ON canil_puppies(litter_id);
CREATE INDEX idx_event_entity ON canil_events(entity_type, entity_id);
```

### 7.2 Paginação

```php
// Sempre paginar listagens
public function findAll(array $filters, int $page = 1, int $perPage = 20): PaginatedResult {
    $offset = ($page - 1) * $perPage;
    
    // Conta total (com cache se possível)
    $total = $this->count($filters);
    
    // Busca página
    $items = $this->wpdb->get_results(
        $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE tenant_id = %d LIMIT %d OFFSET %d",
            $this->getTenantId(),
            $perPage,
            $offset
        )
    );
    
    return new PaginatedResult($items, $total, $page, $perPage);
}
```

### 7.3 Caching

```php
// Cache de consultas frequentes usando Transients
$cacheKey = "canil_dogs_count_{$tenantId}";
$count = get_transient($cacheKey);

if ($count === false) {
    $count = $this->repository->count();
    set_transient($cacheKey, $count, HOUR_IN_SECONDS);
}

// Invalidar cache em mutações
delete_transient($cacheKey);
```

---

## 8. Testes

### 8.1 Estrutura

```
tests/
├── Unit/
│   ├── Domain/
│   │   ├── DogTest.php
│   │   └── ReproductionServiceTest.php
│   └── Helpers/
│       └── DateHelperTest.php
│
├── Integration/
│   ├── Repository/
│   │   ├── DogRepositoryTest.php
│   │   └── TenantIsolationTest.php    # OBRIGATÓRIO
│   └── REST/
│       ├── DogsControllerTest.php
│       └── AuthenticationTest.php
│
└── bootstrap.php
```

### 8.2 Teste de Isolamento (Obrigatório)

```php
class TenantIsolationTest extends WP_UnitTestCase {
    public function test_user_cannot_see_other_tenant_dogs(): void {
        // Setup: criar 2 usuários
        $user1 = $this->factory->user->create();
        $user2 = $this->factory->user->create();
        
        // User1 cria um cão
        wp_set_current_user($user1);
        $dog = $this->dogService->create(['name' => 'Rex']);
        
        // User2 não pode ver
        wp_set_current_user($user2);
        $result = $this->dogRepository->findById($dog->getId());
        
        $this->assertNull($result, 'User2 should not see User1 dog');
    }
}
```

---

## 9. Deploy e CI/CD

### 9.1 GitHub Actions

```yaml
# .github/workflows/ci.yml
name: CI

on: [push, pull_request]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: PHP Lint
        run: composer lint
      - name: JS Lint
        run: npm run lint

  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install deps
        run: composer install
      - name: Run tests
        run: composer test

  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: 'lts/*'
      - name: Install & Build
        run: |
          npm install
          npm run build
```

---

## 10. Decisões Arquiteturais

| Decisão | Alternativas | Justificativa |
|---------|--------------|---------------|
| Tabelas custom vs CPT | Custom Post Types | Melhor performance e controle de tenant |
| React vs Alpine.js | Vue, Svelte | Integração nativa com Gutenberg |
| REST vs GraphQL | GraphQL | Simplicidade e padrão WP |
| Single plugin vs Multi | Monorepo | Manutenibilidade e deploy conjunto |
| PHP 8.1+ | PHP 7.4 | Recursos modernos, tipagem |

---

*Documento gerado em: 02/02/2026*
