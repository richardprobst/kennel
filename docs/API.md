# API.md - Especificação REST API

## Plugin WordPress para Gestão de Canil (SaaS Multi-tenant)

**Data:** 02/02/2026  
**Versão:** 1.0  
**Base URL:** `/wp-json/canil/v1`

---

## 1. Visão Geral

### 1.1 Convenções

| Item | Convenção |
|------|-----------|
| Namespace | `/canil/v1` |
| Content-Type | `application/json` |
| Autenticação | WordPress cookies + nonce |
| IDs | Inteiros positivos |
| Datas | ISO 8601 (`YYYY-MM-DD` ou `YYYY-MM-DDTHH:mm:ss`) |
| Paginação | `page`, `per_page`, `total`, `total_pages` |

### 1.2 Autenticação

Todas as rotas requerem usuário WordPress autenticado.

```javascript
// No admin (React), usar @wordpress/api-fetch
import apiFetch from '@wordpress/api-fetch';

const dogs = await apiFetch({ path: '/canil/v1/dogs' });
```

O `@wordpress/api-fetch` adiciona automaticamente o nonce de autenticação.

### 1.3 Resposta Padrão

#### Sucesso (Lista)
```json
{
    "data": [...],
    "meta": {
        "total": 50,
        "page": 1,
        "per_page": 20,
        "total_pages": 3
    }
}
```

#### Sucesso (Item Único)
```json
{
    "data": {...}
}
```

#### Erro
```json
{
    "code": "validation_error",
    "message": "Validation failed",
    "data": {
        "status": 400,
        "errors": {
            "name": ["Name is required"],
            "birth_date": ["Invalid date format"]
        }
    }
}
```

### 1.4 Códigos HTTP

| Código | Descrição |
|--------|-----------|
| 200 | OK - Sucesso |
| 201 | Created - Recurso criado |
| 204 | No Content - Deletado com sucesso |
| 400 | Bad Request - Erro de validação |
| 401 | Unauthorized - Não autenticado |
| 403 | Forbidden - Sem permissão |
| 404 | Not Found - Recurso não encontrado |
| 500 | Internal Server Error |

---

## 2. Endpoints - Dogs (Cães)

### 2.1 Listar Cães

```
GET /canil/v1/dogs
```

**Permissão:** `manage_dogs`

**Query Parameters:**

| Param | Tipo | Descrição |
|-------|------|-----------|
| search | string | Busca por nome, chip, registro |
| status | string | Filtro por status |
| sex | string | `male` ou `female` |
| breed | string | Filtro por raça |
| page | int | Página (default: 1) |
| per_page | int | Itens por página (default: 20, max: 100) |
| orderby | string | Campo para ordenação |
| order | string | `asc` ou `desc` |

**Exemplo:**
```
GET /canil/v1/dogs?status=breeding&sex=female&page=1&per_page=10
```

**Resposta:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Luna",
            "call_name": "Lu",
            "registration_number": "CBKC 12345",
            "chip_number": "123456789012345",
            "breed": "Golden Retriever",
            "color": "Dourado",
            "birth_date": "2020-05-15",
            "sex": "female",
            "status": "breeding",
            "sire_id": 5,
            "dam_id": 3,
            "photo_main_url": "https://...",
            "age_years": 5,
            "age_months": 8,
            "created_at": "2024-01-10T10:30:00",
            "updated_at": "2025-02-01T14:00:00"
        }
    ],
    "meta": {
        "total": 25,
        "page": 1,
        "per_page": 10,
        "total_pages": 3
    }
}
```

---

### 2.2 Obter Cão

```
GET /canil/v1/dogs/{id}
```

**Permissão:** `manage_dogs`

**Resposta:**
```json
{
    "data": {
        "id": 1,
        "name": "Luna",
        "call_name": "Lu",
        "registration_number": "CBKC 12345",
        "chip_number": "123456789012345",
        "tattoo": null,
        "breed": "Golden Retriever",
        "variety": null,
        "color": "Dourado",
        "markings": null,
        "birth_date": "2020-05-15",
        "death_date": null,
        "sex": "female",
        "status": "breeding",
        "sire_id": 5,
        "sire": {
            "id": 5,
            "name": "Max",
            "registration_number": "CBKC 11111"
        },
        "dam_id": 3,
        "dam": {
            "id": 3,
            "name": "Bella",
            "registration_number": "CBKC 22222"
        },
        "photo_main_url": "https://...",
        "photos": [
            {"url": "https://...", "caption": "Frontal", "order": 1}
        ],
        "titles": [
            {"title": "CH", "organization": "CBKC", "date": "2024-05-15"}
        ],
        "health_tests": [
            {"test": "HD", "result": "A", "date": "2023-06-10", "lab": "VetLab"}
        ],
        "notes": "Excelente temperamento",
        "age_years": 5,
        "age_months": 8,
        "litters_count": 3,
        "puppies_count": 18,
        "created_at": "2024-01-10T10:30:00",
        "updated_at": "2025-02-01T14:00:00"
    }
}
```

---

### 2.3 Criar Cão

```
POST /canil/v1/dogs
```

**Permissão:** `manage_dogs`

**Body:**
```json
{
    "name": "Luna",
    "call_name": "Lu",
    "registration_number": "CBKC 12345",
    "chip_number": "123456789012345",
    "breed": "Golden Retriever",
    "color": "Dourado",
    "birth_date": "2020-05-15",
    "sex": "female",
    "status": "breeding",
    "sire_id": 5,
    "dam_id": 3,
    "notes": "Excelente temperamento"
}
```

**Validação:**

| Campo | Regras |
|-------|--------|
| name | obrigatório, max 255 chars |
| breed | obrigatório, max 100 chars |
| birth_date | obrigatório, formato YYYY-MM-DD |
| sex | obrigatório, enum: male, female |
| status | opcional, enum: active, breeding, retired, sold, deceased, coowned |

**Resposta:** `201 Created`
```json
{
    "data": {
        "id": 15,
        "name": "Luna",
        ...
    }
}
```

---

### 2.4 Atualizar Cão

```
PUT /canil/v1/dogs/{id}
```

**Permissão:** `manage_dogs`

**Body:** (parcial ou completo)
```json
{
    "status": "retired",
    "notes": "Aposentada da reprodução"
}
```

**Resposta:** `200 OK`

---

### 2.5 Excluir Cão

```
DELETE /canil/v1/dogs/{id}
```

**Permissão:** `manage_dogs`

**Resposta:** `204 No Content`

**Regras:**
- Soft delete (define deleted_at)
- Não permite excluir se tiver ninhadas ativas

---

### 2.6 Pedigree do Cão

```
GET /canil/v1/dogs/{id}/pedigree
```

**Query Parameters:**

| Param | Tipo | Descrição |
|-------|------|-----------|
| generations | int | Número de gerações (3-5, default: 3) |

**Resposta:**
```json
{
    "data": {
        "dog": {
            "id": 1,
            "name": "Luna",
            "registration_number": "CBKC 12345"
        },
        "sire": {
            "id": 5,
            "name": "Max",
            "sire": {
                "id": 10,
                "name": "Thor",
                "sire": null,
                "dam": null
            },
            "dam": {
                "id": 11,
                "name": "Maya",
                "sire": null,
                "dam": null
            }
        },
        "dam": {
            "id": 3,
            "name": "Bella",
            "sire": {...},
            "dam": {...}
        }
    }
}
```

---

### 2.7 Timeline do Cão

```
GET /canil/v1/dogs/{id}/timeline
```

**Query Parameters:**

| Param | Tipo | Descrição |
|-------|------|-----------|
| event_type | string | Filtro por tipo de evento |
| from | date | Data inicial |
| to | date | Data final |
| page | int | Página |
| per_page | int | Itens por página |

**Resposta:**
```json
{
    "data": [
        {
            "id": 100,
            "event_type": "vaccine",
            "event_date": "2025-01-15T10:00:00",
            "payload": {
                "name": "V10",
                "manufacturer": "Zoetis",
                "batch": "ABC123",
                "next_date": "2026-01-15"
            },
            "notes": "Aplicada sem reações"
        },
        {
            "id": 95,
            "event_type": "weighing",
            "event_date": "2025-01-10T09:00:00",
            "payload": {
                "weight_kg": 28.5
            },
            "notes": null
        }
    ],
    "meta": {...}
}
```

---

## 3. Endpoints - Litters (Ninhadas)

### 3.1 Listar Ninhadas

```
GET /canil/v1/litters
```

**Query Parameters:**

| Param | Tipo | Descrição |
|-------|------|-----------|
| status | string | Filtro por status |
| dam_id | int | Filtro por matriz |
| sire_id | int | Filtro por reprodutor |
| year | int | Filtro por ano de nascimento |
| page | int | Página |
| per_page | int | Itens por página |

**Resposta:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Ninhada A",
            "litter_letter": "A",
            "status": "born",
            "dam_id": 1,
            "dam": {
                "id": 1,
                "name": "Luna"
            },
            "sire_id": 5,
            "sire": {
                "id": 5,
                "name": "Max"
            },
            "mating_date": "2024-10-01",
            "expected_birth_date": "2024-12-03",
            "actual_birth_date": "2024-12-02",
            "puppies_born_count": 8,
            "puppies_alive_count": 8,
            "males_count": 5,
            "females_count": 3,
            "puppies_available_count": 3,
            "created_at": "2024-10-01T10:00:00"
        }
    ],
    "meta": {...}
}
```

---

### 3.2 Obter Ninhada

```
GET /canil/v1/litters/{id}
```

**Resposta inclui lista de filhotes:**
```json
{
    "data": {
        "id": 1,
        "name": "Ninhada A",
        ...
        "puppies": [
            {
                "id": 10,
                "identifier": "M1",
                "name": "Apollo",
                "sex": "male",
                "status": "sold",
                ...
            }
        ],
        "timeline": [
            {
                "event_type": "mating",
                "event_date": "2024-10-01"
            },
            {
                "event_type": "pregnancy_test",
                "event_date": "2024-10-28"
            }
        ]
    }
}
```

---

### 3.3 Criar Ninhada

```
POST /canil/v1/litters
```

**Body:**
```json
{
    "name": "Ninhada B",
    "litter_letter": "B",
    "dam_id": 1,
    "sire_id": 5,
    "mating_date": "2025-02-01",
    "mating_type": "natural",
    "notes": "Segunda cria da Luna"
}
```

**Validação:**

| Campo | Regras |
|-------|--------|
| dam_id | obrigatório, deve ser fêmea existente |
| sire_id | obrigatório, deve ser macho existente |
| mating_date | obrigatório se status != 'planned' |

**Automático:**
- `expected_birth_date` calculado (mating_date + 63 dias)
- `status` inicial: `confirmed` (se mating_date) ou `planned`

---

### 3.4 Registrar Parto

```
POST /canil/v1/litters/{id}/birth
```

**Body:**
```json
{
    "birth_date": "2025-04-05",
    "birth_type": "natural",
    "notes": "Parto sem complicações",
    "puppies": [
        {
            "identifier": "M1",
            "sex": "male",
            "color": "Dourado Claro",
            "birth_weight": 450,
            "notes": "Primeiro a nascer"
        },
        {
            "identifier": "F1",
            "sex": "female",
            "color": "Dourado",
            "birth_weight": 420
        }
    ]
}
```

**Efeitos:**
- Atualiza status da ninhada para `born`
- Cria registros de filhotes
- Atualiza contadores (puppies_born_count, etc.)
- Registra evento de nascimento na timeline

---

## 4. Endpoints - Puppies (Filhotes)

### 4.1 Listar Filhotes

```
GET /canil/v1/puppies
```

**Query Parameters:**

| Param | Tipo | Descrição |
|-------|------|-----------|
| status | string | available, reserved, sold, retained, deceased |
| litter_id | int | Filtro por ninhada |
| sex | string | male, female |
| page | int | Página |
| per_page | int | Itens por página |

---

### 4.2 Atualizar Status do Filhote

```
PUT /canil/v1/puppies/{id}/status
```

**Body:**
```json
{
    "status": "reserved",
    "buyer_id": 10,
    "reservation_date": "2025-01-15",
    "notes": "Reserva com sinal de R$ 500"
}
```

---

### 4.3 Registrar Venda

```
POST /canil/v1/puppies/{id}/sale
```

**Body:**
```json
{
    "buyer_id": 10,
    "sale_date": "2025-02-01",
    "delivery_date": "2025-02-15",
    "price": 4500.00,
    "notes": "Entrega com 60 dias"
}
```

---

### 4.4 Pesagens do Filhote

```
GET /canil/v1/puppies/{id}/weighings
POST /canil/v1/puppies/{id}/weighings
```

**POST Body:**
```json
{
    "date": "2025-01-20",
    "weight_grams": 1500,
    "notes": "3 semanas"
}
```

---

## 5. Endpoints - People (Pessoas)

### 5.1 Listar Pessoas

```
GET /canil/v1/people
```

**Query Parameters:**

| Param | Tipo | Descrição |
|-------|------|-----------|
| search | string | Busca por nome, email, telefone |
| type | string | interested, buyer, veterinarian, handler, partner |
| page | int | Página |
| per_page | int | Itens por página |

---

### 5.2 CRUD Padrão

```
POST   /canil/v1/people          - Criar
GET    /canil/v1/people/{id}     - Obter
PUT    /canil/v1/people/{id}     - Atualizar
DELETE /canil/v1/people/{id}     - Excluir
```

---

## 6. Endpoints - Events (Eventos)

### 6.1 Criar Evento

```
POST /canil/v1/events
```

**Body:**
```json
{
    "entity_type": "dog",
    "entity_id": 1,
    "event_type": "vaccine",
    "event_date": "2025-02-01T10:00:00",
    "payload": {
        "name": "V10",
        "manufacturer": "Zoetis",
        "batch": "XYZ789",
        "next_date": "2026-02-01"
    },
    "notes": "Aplicada na clínica VetCare",
    "reminder_date": "2026-01-25T09:00:00"
}
```

---

### 6.2 Listar Eventos

```
GET /canil/v1/events
```

**Query Parameters:**

| Param | Tipo | Descrição |
|-------|------|-----------|
| entity_type | string | dog, litter, puppy |
| entity_id | int | ID da entidade |
| event_type | string | Tipo do evento |
| from | date | Data inicial |
| to | date | Data final |
| has_reminder | bool | Apenas com lembrete pendente |
| page | int | Página |
| per_page | int | Itens por página |

---

### 6.3 Eventos por Tipo (Atalhos)

```
GET /canil/v1/events/vaccines      - Lista vacinas
GET /canil/v1/events/dewormings    - Lista vermífugos
GET /canil/v1/events/weighings     - Lista pesagens
GET /canil/v1/events/health        - Todos eventos de saúde
GET /canil/v1/events/reproduction  - Eventos reprodutivos
```

---

## 7. Endpoints - Calendar (Calendário)

### 7.1 Eventos do Mês

```
GET /canil/v1/calendar
```

**Query Parameters:**

| Param | Tipo | Descrição |
|-------|------|-----------|
| year | int | Ano (obrigatório) |
| month | int | Mês (obrigatório) |
| types | string | Tipos de evento (vírgula separado) |

**Resposta:**
```json
{
    "data": [
        {
            "date": "2025-02-05",
            "events": [
                {
                    "id": 100,
                    "type": "vaccine",
                    "title": "Vacina V10 - Luna",
                    "entity_type": "dog",
                    "entity_id": 1,
                    "entity_name": "Luna"
                }
            ]
        },
        {
            "date": "2025-02-15",
            "events": [
                {
                    "type": "expected_birth",
                    "title": "Previsão de Parto - Ninhada B",
                    "entity_type": "litter",
                    "entity_id": 5,
                    "entity_name": "Ninhada B"
                }
            ]
        }
    ]
}
```

---

## 8. Endpoints - Reminders (Lembretes)

### 8.1 Listar Lembretes Pendentes

```
GET /canil/v1/reminders
```

**Query Parameters:**

| Param | Tipo | Descrição |
|-------|------|-----------|
| status | string | pending, completed, all |
| from | date | Data inicial |
| to | date | Data final |
| page | int | Página |
| per_page | int | Itens por página |

---

### 8.2 Marcar como Concluído

```
POST /canil/v1/reminders/{event_id}/complete
```

---

## 9. Endpoints - Reports (Relatórios)

### 9.1 Relatório do Plantel

```
GET /canil/v1/reports/kennel
```

**Resposta:**
```json
{
    "data": {
        "summary": {
            "total_dogs": 15,
            "males": 5,
            "females": 10,
            "by_status": {
                "active": 3,
                "breeding": 8,
                "retired": 4
            }
        },
        "dogs": [...]
    }
}
```

---

### 9.2 Relatório de Ninhadas

```
GET /canil/v1/reports/litters?year=2024
```

---

### 9.3 Relatório de Filhotes

```
GET /canil/v1/reports/puppies?year=2024&status=sold
```

---

### 9.4 Exportar CSV

```
GET /canil/v1/reports/{report_type}/export?format=csv
```

**Headers de Resposta:**
```
Content-Type: text/csv
Content-Disposition: attachment; filename="plantel_2025-02-02.csv"
```

---

## 10. Endpoints - Settings (Configurações)

### 10.1 Obter Configurações

```
GET /canil/v1/settings
```

**Resposta:**
```json
{
    "data": {
        "kennel_name": "Canil Golden Dreams",
        "kennel_affix": "Golden Dreams",
        "default_breed": "Golden Retriever",
        "gestation_days": 63,
        "date_format": "d/m/Y",
        "currency": "BRL",
        "timezone": "America/Sao_Paulo"
    }
}
```

---

### 10.2 Atualizar Configurações

```
PUT /canil/v1/settings
```

**Permissão:** `manage_settings`

**Body:**
```json
{
    "kennel_name": "Canil Golden Dreams",
    "gestation_days": 63
}
```

---

## 11. Hooks para Add-ons

### 11.1 Filtros para Extensão

```php
// Adicionar campos na resposta
add_filter('canil_rest_dog_response', function($response, $dog) {
    $response['custom_field'] = get_custom_data($dog->id);
    return $response;
}, 10, 2);

// Adicionar tipos de evento
add_filter('canil_event_types', function($types) {
    $types['payment'] = 'Pagamento';
    return $types;
});

// Validação customizada
add_filter('canil_validate_dog', function($errors, $data) {
    if (empty($data['custom_required_field'])) {
        $errors['custom_required_field'] = 'Campo obrigatório';
    }
    return $errors;
}, 10, 2);
```

### 11.2 Actions para Integração

```php
// Após criar cão
do_action('canil_dog_created', $dog);

// Após registrar venda
do_action('canil_puppy_sold', $puppy, $buyer, $sale_data);

// Após registrar evento
do_action('canil_event_created', $event);
```

---

## 12. Rate Limiting

### 12.1 Limites (sugestão)

| Endpoint | Limite |
|----------|--------|
| GET (listagens) | 60/minuto |
| POST/PUT/DELETE | 30/minuto |
| Exports | 5/minuto |

### 12.2 Headers de Resposta

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1706835600
```

---

## 13. Versionamento da API

### 13.1 Versão Atual

`v1` - Versão estável inicial

### 13.2 Política de Deprecação

- Mudanças breaking criam nova versão (`v2`)
- Versões antigas mantidas por 12 meses
- Headers de deprecation quando aplicável

---

*Documento gerado em: 02/02/2026*
