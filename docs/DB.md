# DB.md - Modelo de Dados

## Plugin WordPress para Gestão de Canil (SaaS Multi-tenant)

**Data:** 02/02/2026  
**Versão:** 1.0

---

## 1. Visão Geral

### 1.1 Estratégia de Armazenamento

- **Tabelas Customizadas**: Para dados operacionais do canil (cães, ninhadas, eventos, etc.)
- **Prefixo**: `{$wpdb->prefix}canil_` (ex: `wp_canil_dogs`)
- **Multi-tenant**: Todas as tabelas contêm `tenant_id` com índice

### 1.2 Convenções

| Convenção | Descrição |
|-----------|-----------|
| Nomenclatura | snake_case para tabelas e colunas |
| IDs | BIGINT UNSIGNED AUTO_INCREMENT |
| Timestamps | DATETIME com created_at e updated_at |
| Soft Delete | deleted_at DATETIME NULL (opcional) |
| JSON | JSON type para dados flexíveis (MySQL 5.7+) |
| Tenant | tenant_id BIGINT UNSIGNED NOT NULL |

### 1.3 Diagrama ER

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                                   wp_users                                   │
│                              (WordPress nativo)                              │
│                                    ID (PK)                                   │
└─────────────────────────────────────┬───────────────────────────────────────┘
                                      │ 1
                                      │
                    ┌─────────────────┼─────────────────┐
                    │                 │                 │
                    ▼ N               ▼ N               ▼ N
         ┌──────────────────┐ ┌───────────────┐ ┌──────────────────┐
         │  canil_dogs      │ │ canil_people  │ │  canil_events    │
         │  ────────────    │ │ ────────────  │ │  ────────────    │
         │  id (PK)         │ │ id (PK)       │ │  id (PK)         │
         │  tenant_id (FK)  │ │ tenant_id(FK) │ │  tenant_id (FK)  │
         │  name            │ │ name          │ │  entity_type     │
         │  sire_id (self)  │ │ email         │ │  entity_id       │
         │  dam_id (self)   │ │ phone         │ │  event_type      │
         │  ...             │ │ type          │ │  payload_json    │
         └────────┬─────────┘ └───────┬───────┘ └──────────────────┘
                  │                   │
                  │ 1                 │
         ┌────────┴─────────┐         │
         │                  │         │
         ▼ N                ▼ N       │
┌─────────────────┐  ┌──────────────────┐
│ canil_litters   │  │ canil_puppies    │
│ ─────────────   │  │ ─────────────    │
│ id (PK)         │  │ id (PK)          │
│ tenant_id (FK)  │  │ tenant_id (FK)   │
│ dam_id (FK)     │◄─┤ litter_id (FK)   │
│ sire_id (FK)    │  │ buyer_id (FK) ───┼──► canil_people
│ status          │  │ status           │
│ mating_date     │  │ ...              │
│ ...             │  └──────────────────┘
└─────────────────┘
```

---

## 2. Tabelas Detalhadas

### 2.1 canil_dogs (Cães)

Armazena informações do plantel de cães.

```sql
CREATE TABLE {$prefix}canil_dogs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    
    -- Identificação
    name VARCHAR(255) NOT NULL,
    call_name VARCHAR(100) DEFAULT NULL COMMENT 'Nome de chamada/apelido',
    registration_number VARCHAR(100) DEFAULT NULL COMMENT 'Número de registro (CBKC, AKC, etc)',
    chip_number VARCHAR(50) DEFAULT NULL COMMENT 'Número do microchip',
    tattoo VARCHAR(50) DEFAULT NULL COMMENT 'Número da tatuagem',
    
    -- Características
    breed VARCHAR(100) NOT NULL,
    variety VARCHAR(100) DEFAULT NULL COMMENT 'Variedade (pelo, tamanho)',
    color VARCHAR(100) DEFAULT NULL,
    markings VARCHAR(255) DEFAULT NULL COMMENT 'Marcações específicas',
    
    -- Datas
    birth_date DATE NOT NULL,
    death_date DATE DEFAULT NULL,
    
    -- Classificação
    sex ENUM('male', 'female') NOT NULL,
    status ENUM('active', 'breeding', 'retired', 'sold', 'deceased', 'coowned') NOT NULL DEFAULT 'active',
    
    -- Pedigree (auto-referência)
    sire_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID do pai',
    dam_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID da mãe',
    
    -- Mídia
    photo_main_url VARCHAR(500) DEFAULT NULL,
    photos JSON DEFAULT NULL COMMENT 'Array de URLs de fotos adicionais',
    
    -- Informações adicionais
    titles JSON DEFAULT NULL COMMENT 'Array de títulos conquistados',
    health_tests JSON DEFAULT NULL COMMENT 'Array de exames de saúde',
    notes TEXT DEFAULT NULL,
    
    -- Metadados
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    
    PRIMARY KEY (id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_tenant_sex (tenant_id, sex),
    INDEX idx_tenant_breed (tenant_id, breed),
    INDEX idx_sire (sire_id),
    INDEX idx_dam (dam_id),
    INDEX idx_chip (chip_number),
    INDEX idx_registration (registration_number),
    
    FOREIGN KEY (tenant_id) REFERENCES {$wp_prefix}users(ID) ON DELETE CASCADE,
    FOREIGN KEY (sire_id) REFERENCES {$prefix}canil_dogs(id) ON DELETE SET NULL,
    FOREIGN KEY (dam_id) REFERENCES {$prefix}canil_dogs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Campos JSON

**photos**:
```json
[
    {"url": "https://...", "caption": "Foto frontal", "order": 1},
    {"url": "https://...", "caption": "Perfil", "order": 2}
]
```

**titles**:
```json
[
    {"title": "CH", "organization": "CBKC", "date": "2024-05-15"},
    {"title": "GRAND CH", "organization": "CBKC", "date": "2025-01-20"}
]
```

**health_tests**:
```json
[
    {"test": "HD", "result": "A", "date": "2023-06-10", "lab": "Vet Lab"},
    {"test": "ED", "result": "Normal", "date": "2023-06-10", "lab": "Vet Lab"}
]
```

---

### 2.2 canil_litters (Ninhadas)

Armazena informações de ninhadas/crias.

```sql
CREATE TABLE {$prefix}canil_litters (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    
    -- Identificação
    name VARCHAR(255) DEFAULT NULL COMMENT 'Nome/letra da ninhada (ex: Ninhada A)',
    litter_letter CHAR(1) DEFAULT NULL COMMENT 'Letra da ninhada',
    
    -- Pais
    dam_id BIGINT UNSIGNED NOT NULL COMMENT 'ID da matriz',
    sire_id BIGINT UNSIGNED NOT NULL COMMENT 'ID do reprodutor',
    
    -- Status e datas
    status ENUM('planned', 'confirmed', 'pregnant', 'born', 'weaned', 'closed', 'cancelled') NOT NULL DEFAULT 'planned',
    
    -- Reprodução
    heat_start_date DATE DEFAULT NULL COMMENT 'Início do cio',
    mating_date DATE DEFAULT NULL COMMENT 'Data da cobertura/inseminação',
    mating_type ENUM('natural', 'artificial_fresh', 'artificial_frozen') DEFAULT NULL,
    pregnancy_confirmed_date DATE DEFAULT NULL,
    
    -- Parto
    expected_birth_date DATE DEFAULT NULL COMMENT 'Previsão de parto',
    actual_birth_date DATE DEFAULT NULL COMMENT 'Data real do parto',
    birth_type ENUM('natural', 'cesarean', 'assisted') DEFAULT NULL,
    
    -- Estatísticas
    puppies_born_count TINYINT UNSIGNED DEFAULT 0 COMMENT 'Total nascidos',
    puppies_alive_count TINYINT UNSIGNED DEFAULT 0 COMMENT 'Total vivos',
    males_count TINYINT UNSIGNED DEFAULT 0,
    females_count TINYINT UNSIGNED DEFAULT 0,
    
    -- Informações adicionais
    veterinarian_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID do veterinário (canil_people)',
    notes TEXT DEFAULT NULL,
    
    -- Metadados
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    
    PRIMARY KEY (id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_dam (dam_id),
    INDEX idx_sire (sire_id),
    INDEX idx_birth_date (actual_birth_date),
    
    FOREIGN KEY (tenant_id) REFERENCES {$wp_prefix}users(ID) ON DELETE CASCADE,
    FOREIGN KEY (dam_id) REFERENCES {$prefix}canil_dogs(id) ON DELETE RESTRICT,
    FOREIGN KEY (sire_id) REFERENCES {$prefix}canil_dogs(id) ON DELETE RESTRICT,
    FOREIGN KEY (veterinarian_id) REFERENCES {$prefix}canil_people(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.3 canil_puppies (Filhotes)

Armazena informações de filhotes de cada ninhada.

```sql
CREATE TABLE {$prefix}canil_puppies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    litter_id BIGINT UNSIGNED NOT NULL,
    
    -- Identificação
    identifier VARCHAR(50) NOT NULL COMMENT 'Identificador único na ninhada (ex: M1, F2)',
    name VARCHAR(255) DEFAULT NULL COMMENT 'Nome registrado',
    call_name VARCHAR(100) DEFAULT NULL COMMENT 'Nome de chamada',
    registration_number VARCHAR(100) DEFAULT NULL,
    chip_number VARCHAR(50) DEFAULT NULL,
    
    -- Características
    sex ENUM('male', 'female') NOT NULL,
    color VARCHAR(100) DEFAULT NULL,
    markings VARCHAR(255) DEFAULT NULL,
    
    -- Nascimento
    birth_weight DECIMAL(6,2) DEFAULT NULL COMMENT 'Peso ao nascer (gramas)',
    birth_order TINYINT UNSIGNED DEFAULT NULL COMMENT 'Ordem de nascimento',
    birth_notes TEXT DEFAULT NULL COMMENT 'Observações do nascimento',
    
    -- Status
    status ENUM('available', 'reserved', 'sold', 'retained', 'deceased', 'returned') NOT NULL DEFAULT 'available',
    
    -- Venda/Reserva
    buyer_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID do comprador (canil_people)',
    reservation_date DATE DEFAULT NULL,
    sale_date DATE DEFAULT NULL,
    delivery_date DATE DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT NULL,
    
    -- Mídia
    photo_main_url VARCHAR(500) DEFAULT NULL,
    photos JSON DEFAULT NULL,
    
    -- Informações adicionais
    notes TEXT DEFAULT NULL,
    
    -- Metadados
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    
    PRIMARY KEY (id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_litter (litter_id),
    INDEX idx_buyer (buyer_id),
    INDEX idx_chip (chip_number),
    
    FOREIGN KEY (tenant_id) REFERENCES {$wp_prefix}users(ID) ON DELETE CASCADE,
    FOREIGN KEY (litter_id) REFERENCES {$prefix}canil_litters(id) ON DELETE RESTRICT,
    FOREIGN KEY (buyer_id) REFERENCES {$prefix}canil_people(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.4 canil_people (Pessoas)

Armazena informações de pessoas relacionadas ao canil.

```sql
CREATE TABLE {$prefix}canil_people (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    
    -- Identificação
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    phone_secondary VARCHAR(50) DEFAULT NULL,
    
    -- Tipo
    type ENUM('interested', 'buyer', 'veterinarian', 'handler', 'partner', 'other') NOT NULL DEFAULT 'interested',
    
    -- Endereço
    address_street VARCHAR(255) DEFAULT NULL,
    address_number VARCHAR(20) DEFAULT NULL,
    address_complement VARCHAR(100) DEFAULT NULL,
    address_neighborhood VARCHAR(100) DEFAULT NULL,
    address_city VARCHAR(100) DEFAULT NULL,
    address_state VARCHAR(50) DEFAULT NULL,
    address_zip VARCHAR(20) DEFAULT NULL,
    address_country VARCHAR(50) DEFAULT 'Brasil',
    
    -- Documentos
    document_cpf VARCHAR(20) DEFAULT NULL,
    document_rg VARCHAR(30) DEFAULT NULL,
    
    -- Preferências (para interessados)
    preferences JSON DEFAULT NULL COMMENT 'Preferências de filhote',
    
    -- Relacionamentos
    referred_by_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'Indicado por',
    
    -- Informações adicionais
    notes TEXT DEFAULT NULL,
    tags JSON DEFAULT NULL COMMENT 'Tags para categorização',
    
    -- Metadados
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    
    PRIMARY KEY (id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_tenant_type (tenant_id, type),
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    
    FOREIGN KEY (tenant_id) REFERENCES {$wp_prefix}users(ID) ON DELETE CASCADE,
    FOREIGN KEY (referred_by_id) REFERENCES {$prefix}canil_people(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Campo JSON preferences

```json
{
    "breed": "Golden Retriever",
    "sex": "female",
    "color": "dourado claro",
    "budget_min": 3000,
    "budget_max": 5000,
    "timeline": "próximos 3 meses",
    "notes": "Prefere fêmea mais calma"
}
```

---

### 2.5 canil_events (Eventos/Timeline)

Armazena todos os eventos relacionados a cães, ninhadas e filhotes.

```sql
CREATE TABLE {$prefix}canil_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    
    -- Entidade relacionada
    entity_type ENUM('dog', 'litter', 'puppy') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    
    -- Evento
    event_type VARCHAR(50) NOT NULL COMMENT 'Tipo do evento',
    event_date DATETIME NOT NULL,
    event_end_date DATETIME DEFAULT NULL COMMENT 'Data fim (para eventos com duração)',
    
    -- Dados específicos do evento
    payload JSON NOT NULL COMMENT 'Dados estruturados do evento',
    
    -- Lembretes
    reminder_date DATETIME DEFAULT NULL COMMENT 'Data do próximo lembrete',
    reminder_completed TINYINT(1) DEFAULT 0,
    
    -- Informações adicionais
    notes TEXT DEFAULT NULL,
    attachments JSON DEFAULT NULL COMMENT 'URLs de anexos',
    
    -- Metadados
    created_by BIGINT UNSIGNED DEFAULT NULL COMMENT 'User ID que criou',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    
    PRIMARY KEY (id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_tenant_entity (tenant_id, entity_type, entity_id),
    INDEX idx_event_type (event_type),
    INDEX idx_event_date (event_date),
    INDEX idx_reminder (reminder_date, reminder_completed),
    
    FOREIGN KEY (tenant_id) REFERENCES {$wp_prefix}users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Tipos de Evento e Payload

| event_type | Descrição | Payload Schema |
|------------|-----------|----------------|
| **Reprodução** | | |
| heat | Início do cio | `{day: 1}` |
| mating | Cobertura/Inseminação | `{type: 'natural'|'ai', attempts: 1}` |
| pregnancy_test | Teste de gestação | `{result: 'positive'|'negative', method: 'ultrasound'|'palpation'}` |
| birth | Nascimento | `{type: 'natural'|'cesarean', puppies_count: 6, notes: ''}` |
| **Saúde** | | |
| vaccine | Vacinação | `{name: 'V10', manufacturer: 'Zoetis', batch: 'ABC123', next_date: '2025-03-01'}` |
| deworming | Vermífugo | `{product: 'Drontal', dosage: '1 comprimido', weight_kg: 25, next_date: '2025-04-01'}` |
| exam | Exame | `{type: 'HD', result: 'A', lab: 'VetLab', attachments: []}` |
| medication | Medicação | `{name: 'Antibiótico', dosage: '500mg', frequency: '12/12h', start: '', end: ''}` |
| surgery | Cirurgia | `{type: 'Castração', veterinarian: 'Dr. Silva', clinic: 'VetCare'}` |
| vet_visit | Consulta | `{reason: 'Check-up', veterinarian: 'Dr. Silva', findings: '', prescriptions: []}` |
| **Pesagem** | | |
| weighing | Pesagem | `{weight_grams: 2500, weight_kg: null, notes: ''}` |
| **Outros** | | |
| grooming | Banho/Tosa | `{type: 'bath'|'grooming'|'both', groomer: ''}` |
| training | Treino | `{type: 'obedience'|'show', trainer: '', notes: ''}` |
| show | Exposição | `{name: 'Nacional CBKC', result: 'Excelente 1', title: 'CAC'}` |
| note | Anotação | `{text: 'Observação livre'}` |

---

### 2.6 canil_audit_log (Auditoria)

Log de operações críticas para rastreabilidade.

```sql
CREATE TABLE {$prefix}canil_audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    
    -- Ação
    action ENUM('create', 'update', 'delete', 'restore') NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    
    -- Dados
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    
    -- Contexto
    user_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    
    -- Timestamp
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (tenant_id) REFERENCES {$wp_prefix}users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.7 canil_settings (Configurações)

Configurações do canil por tenant.

```sql
CREATE TABLE {$prefix}canil_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    
    -- Chave-valor
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    
    -- Metadados
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY unique_tenant_key (tenant_id, setting_key),
    INDEX idx_tenant (tenant_id),
    
    FOREIGN KEY (tenant_id) REFERENCES {$wp_prefix}users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Configurações Comuns

| setting_key | Descrição | Valor Exemplo |
|-------------|-----------|---------------|
| kennel_name | Nome do canil | "Canil Golden Dreams" |
| kennel_affix | Afixo do canil | "Golden Dreams" |
| kennel_registration | Registro do canil | "CBKC 12345" |
| default_breed | Raça principal | "Golden Retriever" |
| gestation_days | Dias de gestação padrão | "63" |
| reminder_vaccine_days | Dias antes p/ lembrete vacina | "7" |
| reminder_deworming_days | Dias antes p/ lembrete vermífugo | "7" |
| date_format | Formato de data | "d/m/Y" |
| currency | Moeda | "BRL" |
| timezone | Fuso horário | "America/Sao_Paulo" |

---

## 3. Migrações

### 3.1 Estrutura de Migrações

```
plugin-core/migrations/
├── 001_create_dogs_table.php
├── 002_create_people_table.php
├── 003_create_litters_table.php
├── 004_create_puppies_table.php
├── 005_create_events_table.php
├── 006_create_audit_log_table.php
├── 007_create_settings_table.php
└── ...
```

### 3.2 Exemplo de Migração

```php
<?php
// 001_create_dogs_table.php

namespace CanilCore\Migrations;

class CreateDogsTable {
    public function up(): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'canil_dogs';
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            -- ... outros campos
            PRIMARY KEY (id),
            INDEX idx_tenant (tenant_id)
        ) $charset";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    public function down(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'canil_dogs';
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}
```

### 3.3 Controle de Versão

```php
// Option para controle de versão
update_option('canil_core_db_version', '007');

// Verificação na ativação
$current = get_option('canil_core_db_version', '000');
$migrations = glob(CANIL_CORE_PATH . '/migrations/*.php');

foreach ($migrations as $migration) {
    $version = basename($migration, '.php');
    if ($version > $current) {
        require_once $migration;
        // Executar migration
        update_option('canil_core_db_version', $version);
    }
}
```

---

## 4. Índices e Performance

### 4.1 Índices Obrigatórios

Todas as tabelas DEVEM ter:
- `INDEX idx_tenant (tenant_id)` — para isolamento multi-tenant

### 4.2 Índices Compostos Recomendados

```sql
-- Consultas frequentes
INDEX idx_tenant_status (tenant_id, status)
INDEX idx_tenant_type (tenant_id, type)
INDEX idx_tenant_entity (tenant_id, entity_type, entity_id)
```

### 4.3 Regras de Query

```php
// ✅ CORRETO: Sempre filtrar por tenant
$dogs = $wpdb->get_results($wpdb->prepare(
    "SELECT id, name, status FROM {$table} 
     WHERE tenant_id = %d AND status = %s 
     LIMIT %d OFFSET %d",
    $tenantId, $status, $perPage, $offset
));

// ❌ ERRADO: SELECT * em listagens
$dogs = $wpdb->get_results("SELECT * FROM {$table}");

// ❌ ERRADO: Sem filtro de tenant
$dogs = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table} WHERE status = %s",
    $status
));
```

---

## 5. Relacionamentos

### 5.1 Diagrama de Relacionamentos

```
wp_users (1) ───< (N) canil_dogs
                      │
                      ├── (1) ───< (N) canil_litters (como dam_id)
                      ├── (1) ───< (N) canil_litters (como sire_id)
                      ├── (1) ───< (N) canil_dogs (como sire_id - self)
                      └── (1) ───< (N) canil_dogs (como dam_id - self)

canil_litters (1) ───< (N) canil_puppies

canil_people (1) ───< (N) canil_puppies (como buyer_id)

canil_dogs/litters/puppies (1) ───< (N) canil_events (polimórfico)
```

### 5.2 Integridade Referencial

| Tabela | FK | Ação ON DELETE |
|--------|----|-----------------| 
| dogs | tenant_id → users | CASCADE |
| dogs | sire_id/dam_id → dogs | SET NULL |
| litters | dam_id/sire_id → dogs | RESTRICT |
| puppies | litter_id → litters | RESTRICT |
| puppies | buyer_id → people | SET NULL |
| events | tenant_id → users | CASCADE |
| audit_log | tenant_id → users | CASCADE |

---

## 6. Backup e Recuperação

### 6.1 Estratégia de Backup

- Backup regular do MySQL (via hosting ou plugin)
- Export JSON por tenant (funcionalidade futura)
- Audit log para rastreabilidade

### 6.2 Soft Delete

Tabelas principais usam `deleted_at` para soft delete:

```php
// Soft delete
$wpdb->update($table, ['deleted_at' => current_time('mysql')], ['id' => $id]);

// Queries devem excluir deletados
"SELECT * FROM {$table} WHERE tenant_id = %d AND deleted_at IS NULL"

// Restore
$wpdb->update($table, ['deleted_at' => null], ['id' => $id]);
```

---

## 7. Evolução do Schema

### 7.1 Adicionando Colunas

```php
// Nova migração
public function up(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'canil_dogs';
    
    // Verificar se coluna já existe
    $column_exists = $wpdb->get_results($wpdb->prepare(
        "SHOW COLUMNS FROM {$table} LIKE %s",
        'new_column'
    ));
    
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN new_column VARCHAR(100) DEFAULT NULL");
    }
}
```

### 7.2 Versionamento

- Cada alteração de schema = nova migração
- Nunca modificar migrações já executadas em produção
- Manter compatibilidade retroativa quando possível

---

*Documento gerado em: 02/02/2026*
