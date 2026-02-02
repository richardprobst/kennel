# SEGURANCA.md - Guia de Segurança

## Plugin WordPress para Gestão de Canil (SaaS Multi-tenant)

**Data:** 02/02/2026  
**Versão:** 1.0

Este documento detalha todos os requisitos de segurança, ameaças conhecidas e medidas de proteção obrigatórias para o sistema.

---

## 1. Princípios de Segurança

### 1.1 Defense in Depth (Defesa em Profundidade)

Múltiplas camadas de segurança:

```
┌─────────────────────────────────────────────────────────────────┐
│                    CAMADA 1: AUTENTICAÇÃO                        │
│        WordPress Authentication (cookies + nonces)               │
├─────────────────────────────────────────────────────────────────┤
│                    CAMADA 2: AUTORIZAÇÃO                         │
│              Capabilities (manage_dogs, etc.)                    │
├─────────────────────────────────────────────────────────────────┤
│                    CAMADA 3: ISOLAMENTO TENANT                   │
│               Toda query filtra por tenant_id                    │
├─────────────────────────────────────────────────────────────────┤
│                    CAMADA 4: VALIDAÇÃO                           │
│              Schema validation + type checking                   │
├─────────────────────────────────────────────────────────────────┤
│                    CAMADA 5: SANITIZAÇÃO                         │
│          sanitize_* na entrada, esc_* na saída                  │
├─────────────────────────────────────────────────────────────────┤
│                    CAMADA 6: AUDITORIA                           │
│              Log de operações críticas                           │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2 Princípio do Menor Privilégio

- Usuários só têm acesso ao necessário
- Capabilities específicas por funcionalidade
- Dados são sempre filtrados por tenant

### 1.3 Fail-Safe Defaults

- Negar acesso por padrão
- Exigir autenticação explícita
- Validar antes de qualquer operação

---

## 2. Multi-tenant Security (CRÍTICO)

### 2.1 Ameaça: Vazamento de Dados Entre Tenants

**Risco:** CRÍTICO  
**Impacto:** Exposição de dados de clientes para outros clientes

#### Vetores de Ataque

1. **Parameter Tampering**: Atacante manipula tenant_id na request
2. **IDOR**: Atacante acessa IDs de recursos de outro tenant
3. **Mass Assignment**: Atacante sobrescreve tenant_id no payload
4. **Filter Bypass**: Query não aplica filtro de tenant

#### Contramedidas OBRIGATÓRIAS

```php
// ========================================
// REGRA 1: NUNCA aceitar tenant_id do cliente
// ========================================

// ❌ VULNERÁVEL: tenant_id do request
public function list(WP_REST_Request $request) {
    $tenantId = $request->get_param('tenant_id'); // PROIBIDO!
    return $this->repo->findByTenant($tenantId);
}

// ✅ SEGURO: tenant_id do servidor
public function list(WP_REST_Request $request) {
    $tenantId = get_current_user_id(); // Sempre do servidor
    return $this->repo->findAll(); // Repository força tenant internamente
}

// ========================================
// REGRA 2: Filtro de tenant na camada de dados
// ========================================

abstract class BaseRepository {
    protected function getTenantId(): int {
        $tenantId = get_current_user_id();
        if ($tenantId === 0) {
            throw new UnauthorizedException('Not authenticated');
        }
        return $tenantId;
    }
    
    // TODA query DEVE usar este método
    public function findById(int $id): ?Entity {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table} 
             WHERE id = %d AND tenant_id = %d",  // Filtro obrigatório
            $id,
            $this->getTenantId()
        ));
    }
}

// ========================================
// REGRA 3: Verificar propriedade antes de mutação
// ========================================

public function update(int $id, array $data): Entity {
    // Primeiro: verificar se entidade pertence ao tenant
    $existing = $this->findById($id);
    
    if (!$existing) {
        // Não diferencia "não existe" de "não pertence ao tenant"
        // para evitar information disclosure
        throw new NotFoundException('Resource not found');
    }
    
    // Então: atualizar com tenant forçado
    $data['tenant_id'] = $this->getTenantId();
    return $this->doUpdate($id, $data);
}

// ========================================
// REGRA 4: Forçar tenant em inserções
// ========================================

public function create(array $data): Entity {
    // SEMPRE sobrescrever tenant_id, mesmo se vier no payload
    $data['tenant_id'] = $this->getTenantId();
    return $this->doInsert($data);
}
```

### 2.2 Teste de Isolamento Obrigatório

```php
/**
 * Este teste DEVE existir para CADA endpoint/funcionalidade.
 */
class TenantIsolationTest extends WP_UnitTestCase {
    
    public function test_user_cannot_access_other_tenant_dog(): void {
        // Arrange: criar 2 usuários
        $user1 = $this->factory()->user->create(['role' => 'kennel_owner']);
        $user2 = $this->factory()->user->create(['role' => 'kennel_owner']);
        
        // User1 cria um cão
        wp_set_current_user($user1);
        $dog = $this->dogService->create(['name' => 'Rex', ...]);
        
        // User2 tenta acessar
        wp_set_current_user($user2);
        
        // Assert: deve retornar null/not found
        $result = $this->dogRepository->findById($dog->getId());
        $this->assertNull($result, 'User2 should not see User1 dog');
    }
    
    public function test_user_cannot_update_other_tenant_dog(): void {
        $user1 = $this->factory()->user->create(['role' => 'kennel_owner']);
        $user2 = $this->factory()->user->create(['role' => 'kennel_owner']);
        
        wp_set_current_user($user1);
        $dog = $this->dogService->create(['name' => 'Rex', ...]);
        
        wp_set_current_user($user2);
        
        $this->expectException(NotFoundException::class);
        $this->dogService->update($dog->getId(), ['name' => 'Hacked!']);
    }
    
    public function test_user_cannot_delete_other_tenant_dog(): void {
        $user1 = $this->factory()->user->create(['role' => 'kennel_owner']);
        $user2 = $this->factory()->user->create(['role' => 'kennel_owner']);
        
        wp_set_current_user($user1);
        $dog = $this->dogService->create(['name' => 'Rex', ...]);
        
        wp_set_current_user($user2);
        
        $this->expectException(NotFoundException::class);
        $this->dogService->delete($dog->getId());
    }
    
    public function test_list_only_shows_tenant_data(): void {
        $user1 = $this->factory()->user->create(['role' => 'kennel_owner']);
        $user2 = $this->factory()->user->create(['role' => 'kennel_owner']);
        
        wp_set_current_user($user1);
        $this->dogService->create(['name' => 'User1 Dog 1', ...]);
        $this->dogService->create(['name' => 'User1 Dog 2', ...]);
        
        wp_set_current_user($user2);
        $this->dogService->create(['name' => 'User2 Dog 1', ...]);
        
        // User2 só vê seus próprios cães
        $result = $this->dogRepository->findAll();
        
        $this->assertCount(1, $result->getItems());
        $this->assertEquals('User2 Dog 1', $result->getItems()[0]->getName());
    }
    
    public function test_export_only_includes_tenant_data(): void {
        // Mesmo teste para exports CSV/PDF
    }
    
    public function test_search_only_returns_tenant_data(): void {
        // Mesmo teste para busca
    }
    
    public function test_reports_only_include_tenant_data(): void {
        // Mesmo teste para relatórios
    }
}
```

---

## 3. Autenticação e Autorização

### 3.1 Autenticação WordPress

```php
// REST API: usar permission_callback
register_rest_route('canil/v1', '/dogs', [
    'methods'  => 'GET',
    'callback' => [$this, 'list'],
    'permission_callback' => function() {
        // Verifica autenticação E autorização
        return is_user_logged_in() && current_user_can('manage_dogs');
    },
]);

// Nunca usar '__return_true' em produção!
// ❌ 'permission_callback' => '__return_true'
```

### 3.2 Capabilities

```php
class Capabilities {
    public const ALL = [
        'manage_kennel'   => 'Gerenciar configurações do canil',
        'manage_dogs'     => 'CRUD de cães',
        'manage_litters'  => 'CRUD de ninhadas',
        'manage_puppies'  => 'CRUD de filhotes',
        'manage_people'   => 'CRUD de pessoas',
        'view_reports'    => 'Visualizar relatórios',
        'manage_settings' => 'Alterar configurações',
    ];
    
    public static function register(): void {
        $role = get_role('kennel_owner');
        
        if (!$role) {
            $role = add_role('kennel_owner', 'Proprietário de Canil');
        }
        
        foreach (self::ALL as $cap => $description) {
            $role->add_cap($cap);
        }
    }
    
    public static function unregister(): void {
        $role = get_role('kennel_owner');
        if ($role) {
            foreach (array_keys(self::ALL) as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
}

// Verificar capability antes de QUALQUER ação
if (!current_user_can('manage_dogs')) {
    return new WP_Error('forbidden', 'Access denied', ['status' => 403]);
}
```

### 3.3 Nonces

```php
// PHP: Gerar nonce para o admin
add_action('admin_enqueue_scripts', function() {
    wp_localize_script('canil-admin', 'canilConfig', [
        'nonce' => wp_create_nonce('wp_rest'),
        'apiUrl' => rest_url('canil/v1'),
    ]);
});

// JavaScript: Enviar nonce em requisições
apiFetch({
    path: '/canil/v1/dogs',
    method: 'POST',
    data: { name: 'Rex' },
    // @wordpress/api-fetch adiciona nonce automaticamente
});

// Manual (se não usar apiFetch)
fetch('/wp-json/canil/v1/dogs', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': window.canilConfig.nonce,
    },
    body: JSON.stringify({ name: 'Rex' }),
});
```

---

## 4. Validação e Sanitização

### 4.1 NUNCA Confiar em Dados do Cliente

```php
// Regra de ouro: TODA entrada é potencialmente maliciosa

// ❌ VULNERÁVEL
$name = $request->get_param('name');
$this->wpdb->query("UPDATE dogs SET name = '{$name}'"); // SQL Injection!

// ✅ SEGURO
$name = sanitize_text_field($request->get_param('name'));
$this->wpdb->update(
    $this->table,
    ['name' => $name],
    ['id' => $id, 'tenant_id' => $this->getTenantId()],
    ['%s'],
    ['%d', '%d']
);
```

### 4.2 Sanitização por Tipo

```php
class Sanitizer {
    /**
     * Mapa de sanitização por tipo de campo.
     */
    private const SANITIZERS = [
        'id'       => 'absint',
        'text'     => 'sanitize_text_field',
        'email'    => 'sanitize_email',
        'url'      => 'esc_url_raw',
        'html'     => 'wp_kses_post',
        'textarea' => 'sanitize_textarea_field',
        'int'      => 'intval',
        'float'    => 'floatval',
        'bool'     => 'boolval',
    ];
    
    /**
     * Sanitiza dados baseado em schema.
     * 
     * @param array $data Dados a sanitizar
     * @param array $schema Schema com tipos por campo
     * @return array Dados sanitizados
     */
    public static function sanitize(array $data, array $schema): array {
        $sanitized = [];
        
        foreach ($schema as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }
            
            $value = $data[$field];
            
            if (is_array($type)) {
                // Tipo com opções: ['enum', ['male', 'female']]
                $sanitized[$field] = self::sanitizeEnum($value, $type[1]);
            } elseif (isset(self::SANITIZERS[$type])) {
                $sanitized[$field] = call_user_func(self::SANITIZERS[$type], $value);
            } else {
                // Tipo desconhecido: sanitiza como texto por segurança
                $sanitized[$field] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    private static function sanitizeEnum($value, array $allowed): ?string {
        return in_array($value, $allowed, true) ? $value : null;
    }
}

// Uso
$schema = [
    'name'       => 'text',
    'email'      => 'email',
    'birth_date' => 'text',  // Validado separadamente
    'sex'        => ['enum', ['male', 'female']],
    'status'     => ['enum', DogStatus::all()],
    'notes'      => 'textarea',
];

$sanitized = Sanitizer::sanitize($request->get_json_params(), $schema);
```

### 4.3 Escape na Saída

```php
// REGRA: Escapar TUDO antes de exibir

// HTML content
echo esc_html($dog->getName());

// Atributos HTML
echo '<input value="' . esc_attr($dog->getName()) . '">';

// URLs
echo '<a href="' . esc_url($dog->getProfileUrl()) . '">';

// JavaScript inline
echo '<script>var name = ' . wp_json_encode($dog->getName()) . ';</script>';

// HTML rich (com tags permitidas)
echo wp_kses_post($dog->getNotes());

// Em templates React, o JSX já escapa automaticamente
return <span>{dog.name}</span>; // Seguro

// MAS cuidado com dangerouslySetInnerHTML
// ❌ VULNERÁVEL se notes vier do banco sem sanitização
return <div dangerouslySetInnerHTML={{ __html: dog.notes }} />;

// ✅ SEGURO: sanitizar antes de salvar OU usar biblioteca
import DOMPurify from 'dompurify';
return <div dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(dog.notes) }} />;
```

---

## 5. SQL Injection Prevention

### 5.1 Usar SEMPRE $wpdb->prepare()

```php
// ❌ VULNERÁVEL
$id = $_GET['id'];
$sql = "SELECT * FROM {$table} WHERE id = {$id}";
$result = $wpdb->get_row($sql);

// ✅ SEGURO
$id = absint($_GET['id']);
$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE id = %d AND tenant_id = %d",
    $id,
    $this->getTenantId()
);
$result = $wpdb->get_row($sql);
```

### 5.2 Placeholders por Tipo

```php
// %d = inteiro
// %s = string
// %f = float

$wpdb->prepare(
    "INSERT INTO {$table} (name, age, price, tenant_id) VALUES (%s, %d, %f, %d)",
    $name,    // string
    $age,     // int
    $price,   // float
    $tenantId // int
);
```

### 5.3 LIKE com Wildcards

```php
// ❌ VULNERÁVEL a wildcards
$search = $request->get_param('search');
$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE name LIKE '%{$search}%'"
);

// ✅ SEGURO
$search = $request->get_param('search');
$like = '%' . $wpdb->esc_like($search) . '%';
$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE name LIKE %s AND tenant_id = %d",
    $like,
    $this->getTenantId()
);
```

### 5.4 IN Clause

```php
// ❌ VULNERÁVEL
$ids = implode(',', $request->get_param('ids'));
$sql = "SELECT * FROM {$table} WHERE id IN ({$ids})";

// ✅ SEGURO
$ids = array_map('absint', $request->get_param('ids'));
$placeholders = implode(',', array_fill(0, count($ids), '%d'));
$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE id IN ({$placeholders}) AND tenant_id = %d",
    ...[...$ids, $this->getTenantId()]
);
```

---

## 6. Cross-Site Scripting (XSS) Prevention

### 6.1 Tipos de XSS

| Tipo | Descrição | Mitigação |
|------|-----------|-----------|
| Stored XSS | Script salvo no banco | Sanitizar na entrada, escapar na saída |
| Reflected XSS | Script na URL/params | Escapar saída, CSP |
| DOM XSS | Manipulação de DOM | Evitar innerHTML, usar textContent |

### 6.2 Content Security Policy

```php
// Adicionar CSP headers
add_action('send_headers', function() {
    if (is_admin()) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
    }
});
```

### 6.3 Validação de Upload de Arquivos

```php
class FileUploader {
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    
    private const MAX_SIZE = 5 * 1024 * 1024; // 5MB
    
    public function upload(array $file): string {
        // 1. Verificar erros
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new UploadException('Upload failed');
        }
        
        // 2. Verificar tamanho
        if ($file['size'] > self::MAX_SIZE) {
            throw new UploadException('File too large');
        }
        
        // 3. Verificar MIME type real (não confiar em $file['type'])
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            throw new UploadException('Invalid file type');
        }
        
        // 4. Gerar nome seguro
        $extension = self::ALLOWED_TYPES[$mimeType];
        $filename = wp_generate_uuid4() . '.' . $extension;
        
        // 5. Usar função WP para upload
        $upload = wp_handle_upload($file, [
            'test_form' => false,
            'mimes' => self::ALLOWED_TYPES,
        ]);
        
        if (isset($upload['error'])) {
            throw new UploadException($upload['error']);
        }
        
        return $upload['url'];
    }
}
```

---

## 7. CSRF Protection

### 7.1 Nonces em Forms

```php
// PHP: Form com nonce
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <?php wp_nonce_field('canil_update_settings', 'canil_nonce'); ?>
    <input type="hidden" name="action" value="canil_update_settings">
    <!-- campos do form -->
</form>

// PHP: Verificar nonce
add_action('admin_post_canil_update_settings', function() {
    if (!wp_verify_nonce($_POST['canil_nonce'], 'canil_update_settings')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_settings')) {
        wp_die('Permission denied');
    }
    
    // Processar form
});
```

### 7.2 Nonces em AJAX

```php
// PHP: Localizar nonce
wp_localize_script('canil-admin', 'canilAjax', [
    'url'   => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('canil_ajax_nonce'),
]);

// JavaScript: Enviar nonce
jQuery.post(canilAjax.url, {
    action: 'canil_some_action',
    _ajax_nonce: canilAjax.nonce,
    data: formData,
});

// PHP: Verificar nonce
add_action('wp_ajax_canil_some_action', function() {
    check_ajax_referer('canil_ajax_nonce');
    
    // Processar
});
```

---

## 8. Sensitive Data Protection

### 8.1 Dados Pessoais (LGPD/GDPR)

```php
class PersonalDataHandler {
    /**
     * Campos considerados dados pessoais.
     */
    private const PERSONAL_FIELDS = [
        'email',
        'phone',
        'address',
        'document_cpf',
        'document_rg',
    ];
    
    /**
     * Exportar dados pessoais de um usuário.
     */
    public function export(int $userId): array {
        $data = [];
        
        // Cães
        $dogs = $this->dogRepository->findAllByTenant($userId);
        $data['dogs'] = array_map(fn($d) => $d->toArray(), $dogs);
        
        // Pessoas (compradores)
        $people = $this->personRepository->findAllByTenant($userId);
        $data['people'] = array_map(fn($p) => $p->toArray(), $people);
        
        // Eventos
        $events = $this->eventRepository->findAllByTenant($userId);
        $data['events'] = array_map(fn($e) => $e->toArray(), $events);
        
        return $data;
    }
    
    /**
     * Anonimizar dados de um comprador.
     */
    public function anonymize(int $personId): void {
        $person = $this->personRepository->findById($personId);
        
        if (!$person) {
            throw new NotFoundException('Person not found');
        }
        
        $anonymized = [
            'name'     => 'Usuário Anonimizado',
            'email'    => 'anonimizado_' . $personId . '@deleted.local',
            'phone'    => null,
            'address'  => null,
            'document_cpf' => null,
            'document_rg'  => null,
            'notes'    => '[Dados anonimizados em ' . date('Y-m-d') . ']',
        ];
        
        $this->personRepository->update($personId, $anonymized);
        
        // Log da anonimização
        $this->auditLog->log('anonymize', 'person', $personId, [
            'reason' => 'User request',
        ]);
    }
}
```

### 8.2 Logs e Debugging

```php
// NUNCA logar dados sensíveis em produção

// ❌ ERRADO
error_log("User login: " . $email . " password: " . $password);
error_log("Payment data: " . json_encode($paymentData));

// ✅ CORRETO
error_log("User login attempt: " . hash('sha256', $email));
error_log("Payment processed for order: " . $orderId);

// Configurar WP_DEBUG apenas em desenvolvimento
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("Debug: " . $debugInfo);
}
```

### 8.3 Dados em Transients/Cache

```php
// Cuidado com dados sensíveis em cache

// ❌ ERRADO: cachear dados sensíveis
set_transient('user_data_' . $userId, [
    'name' => $name,
    'email' => $email,
    'cpf' => $cpf,
], HOUR_IN_SECONDS);

// ✅ CORRETO: cachear apenas dados não-sensíveis ou usar object cache seguro
set_transient('dogs_count_' . $userId, $count, HOUR_IN_SECONDS);
```

---

## 9. Auditoria

### 9.1 Eventos a Registrar

| Evento | Criticidade | Dados a Registrar |
|--------|-------------|-------------------|
| Login falho | Alta | user_email, ip, timestamp |
| Login sucesso | Média | user_id, ip, timestamp |
| Criação de entidade | Média | entity_type, entity_id, user_id |
| Atualização de entidade | Média | entity_type, entity_id, campos alterados |
| Exclusão de entidade | Alta | entity_type, entity_id, dados antes da exclusão |
| Venda de filhote | Alta | puppy_id, buyer_id, valor |
| Export de dados | Alta | tipo, filtros, user_id |
| Alteração de permissões | Alta | user_id, caps alteradas |

### 9.2 Implementação

```php
class AuditLog {
    private string $table;
    
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'canil_audit_log';
    }
    
    public function log(
        string $action,
        string $entityType,
        int $entityId,
        array $details = [],
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        global $wpdb;
        
        $wpdb->insert($this->table, [
            'tenant_id'   => get_current_user_id(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues ? wp_json_encode($oldValues) : null,
            'new_values'  => $newValues ? wp_json_encode($newValues) : null,
            'details'     => wp_json_encode($details),
            'user_id'     => get_current_user_id(),
            'ip_address'  => $this->getClientIp(),
            'user_agent'  => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'created_at'  => current_time('mysql'),
        ]);
    }
    
    private function getClientIp(): string {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_X_REAL_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '';
        
        // Se houver múltiplos IPs (proxies), pegar o primeiro
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }
}

// Uso automático via hooks
add_action('canil_core_after_save_dog', function($dog, $isNew) {
    $auditLog = new AuditLog();
    $auditLog->log(
        $isNew ? 'create' : 'update',
        'dog',
        $dog->getId(),
        ['name' => $dog->getName()]
    );
}, 10, 2);
```

---

## 10. Checklist de Segurança para Code Review

### 10.1 Antes de Aprovar PR

```markdown
## Segurança

### Multi-tenant
- [ ] tenant_id vem SEMPRE de get_current_user_id()?
- [ ] Queries filtram por tenant_id?
- [ ] Testes de isolamento existem?

### Autenticação/Autorização
- [ ] Endpoints têm permission_callback?
- [ ] Capabilities são verificadas?
- [ ] Nonces são usados em forms/ajax?

### Input/Output
- [ ] Inputs são sanitizados?
- [ ] Outputs são escapados?
- [ ] $wpdb->prepare() é usado?

### Uploads
- [ ] Tipos de arquivo são validados?
- [ ] MIME type real é verificado?
- [ ] Tamanho máximo é aplicado?

### Dados Sensíveis
- [ ] Logs não contêm dados pessoais?
- [ ] Cache não armazena dados sensíveis?
- [ ] Erros não expõem informações internas?
```

---

## 11. Resposta a Incidentes

### 11.1 Em Caso de Vazamento de Dados

1. **Identificar escopo**: Quais tenants foram afetados?
2. **Conter**: Desativar endpoint vulnerável
3. **Notificar**: Informar usuários afetados (LGPD)
4. **Corrigir**: Deploy de patch de segurança
5. **Auditar**: Revisar logs para entender extensão
6. **Prevenir**: Adicionar testes para evitar recorrência

### 11.2 Contatos de Emergência

| Tipo | Contato |
|------|---------|
| Security Lead | security@canil.app |
| Hotfix Deploy | devops@canil.app |
| Legal (LGPD) | legal@canil.app |

---

*Documento gerado em: 02/02/2026*
