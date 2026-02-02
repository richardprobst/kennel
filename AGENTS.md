# AGENTS.md — Instruções persistentes para agentes (Copilot/Claude) e colaboradores

Este arquivo define como trabalhar neste repositório: **arquitetura**, **padrões**, **comandos**, **regras SaaS (multi-tenant)**, **segurança**, e **Definition of Done**.  
Use estas regras ao implementar features, corrigir bugs, criar migrações e abrir PRs.

---

## 1) Visão geral do produto

Plugin WordPress para gestão de canil de criação (plantel, reprodução, ninhadas/filhotes, saúde, agenda, documentos e relatórios), em modo SaaS:

- **Multi-tenant por usuário**: cada usuário do WordPress gerencia seu próprio canil.
- O **Core** entrega o essencial e fornece extensão via **hooks** e **módulos**.
- Funções avançadas entram como **add-ons** (plugins separados).

---

## 2) Stack e requisitos

### Backend
- PHP (compatível com a política de suporte do WordPress; preferencialmente **>= 8.1**).
- WordPress + REST API.
- Banco: MySQL/MariaDB.

### Frontend (Admin)
- UI no wp-admin usando React (Gutenberg / `@wordpress/*`) e REST API.

### Qualidade
- PHPUnit (testes), PHPCS (WordPress Coding Standards), PHPStan/Psalm (opcional), ESLint (JS).

---

## 3) Estrutura do repositório

- `plugin-core/` — plugin principal
  - `canil-core.php` — bootstrap do plugin
  - `includes/`
    - `Domain/` — regras de negócio (puras, sem WP direto quando possível)
    - `Infrastructure/` — persistência (queries, repositórios, mappers)
    - `Rest/` — controllers, schemas, rotas
    - `AdminUI/` — integração admin (enqueue, assets, rotas SPA)
  - `migrations/` — versões/rotinas de criação e evolução de tabelas
  - `tests/` — testes automatizados
  - `assets-admin/` — build do React (gerado)
  - `assets-admin-src/` — fonte do React (se existir)
- `addons/` — add-ons (plugins separados), ex.:
  - `addons/canil-financeiro/`
  - `addons/canil-crm/`
  - `addons/canil-genetica/`
  - `addons/canil-site-publico/`
- `docs/` — documentação (PRD, arquitetura, API, DB, UX, guias)

---

## 4) Modo SaaS (Multi-tenant) — REGRAS OBRIGATÓRIAS

### 4.1 Tenant
- **Tenant = usuário WP** (por enquanto): `tenant_id = get_current_user_id()`.
- **NUNCA** aceitar `tenant_id` vindo do client (request body, querystring, headers, etc.).
- Toda tabela de dados do produto deve conter `tenant_id` e índices apropriados.

### 4.2 Isolamento
- **Toda leitura** deve filtrar por `tenant_id`.
- **Toda gravação** deve:
  1) obter o `tenant_id` do usuário autenticado;  
  2) validar permissão/capacidades;  
  3) validar/sanitizar entrada;  
  4) gravar sempre com `tenant_id` forçado do servidor.

### 4.3 Onde aplicar
- Camada de persistência (repositórios/queries) deve exigir `tenant_id`.
- Controllers REST não devem fazer query “crua” sem usar a camada com tenant.
- Exportações (CSV/PDF), buscas e relatórios também devem aplicar tenant.

### 4.4 Teste obrigatório
- Para cada endpoint/listagem/export:
  - **Teste de não vazamento**: usuário A não enxerga dados do usuário B.

---

## 5) Segurança e permissões

### 5.1 Capacidades (caps)
- Definir caps do produto no activation:
  - `manage_kennel`
  - `manage_dogs`
  - `manage_litters`
  - `manage_puppies`
  - `manage_people`
  - `view_reports`
  - `manage_settings`
- Todos endpoints REST e ações sensíveis devem verificar `current_user_can(...)`.

### 5.2 Nonces e autenticação
- REST: exigir autenticação (cookies + nonce no admin) conforme padrão WP.
- Para admin SPA: enviar nonce via `wp_create_nonce( 'wp_rest' )` e usar em requests.

### 5.3 Validação e sanitização (obrigatório)
- Use `sanitize_text_field`, `sanitize_email`, `absint`, `floatval`, `wp_kses_post` quando aplicável.
- Escape na saída: `esc_html`, `esc_attr`, `esc_url` etc.
- Validar datas com `DateTimeImmutable` e formatos ISO (`Y-m-d` / `c`), quando possível.
- Não salvar HTML não confiável em campos que serão exibidos.

### 5.4 Auditoria mínima
- Registrar operações críticas (criação/edição/exclusão de ninhadas, parto, venda, exclusões).
- Auditoria deve ser multi-tenant e respeitar permissões.

---

## 6) Banco de dados e migrações

### 6.1 Estratégia
- Preferência por **tabelas custom** para dados operacionais (eventos, pesagens, finanças).
- Campos flexíveis em `payload_json` são permitidos, mas mantenha schemas e validação por tipo de evento.

### 6.2 Migrações
- Migrações versionadas em `plugin-core/migrations/` (ex.: `001_create_tables.php`).
- Um option de versão no WP (ex.: `canil_core_db_version`) controla upgrades.
- Usar `dbDelta` quando adequado e/ou SQL explícito controlado.
- Criar índices: **sempre indexar `tenant_id`** e colunas de filtro frequente.

### 6.3 Regras de query
- Sem `SELECT *` em listas grandes.
- Paginação obrigatória em listagens.
- Nunca fazer query sem `WHERE tenant_id = ?`.

---

## 7) REST API — convenções

### 7.1 Namespace e versionamento
- Namespace: `/canil/v1`
- Rotas por recurso, ex.:
  - `GET /dogs`, `POST /dogs`, `GET /dogs/{id}`, `PUT /dogs/{id}`, `DELETE /dogs/{id}`
  - `GET /litters`, `POST /litters`, …
  - `GET /events`, `POST /events`, …
- Planejar `v2` quando quebrar compatibilidade.

### 7.2 Padrões de resposta
- JSON consistente:
  - `data`, `meta` (paginação), `errors` (quando aplicável)
- Paginação:
  - `page`, `per_page`, `total`, `total_pages`

### 7.3 Validação de request
- Cada endpoint deve ter schema claro (campos, tipos, required).
- Campos desconhecidos devem ser ignorados ou rejeitados (defina padrão por endpoint).
- IDs são sempre `absint`.

---

## 8) Admin UI (React) — padrões

- Preferir `@wordpress/components`, `@wordpress/data`, `@wordpress/api-fetch`.
- UX:
  - listas com busca/filtros
  - forms com validação
  - estados vazios e erros claros
- Performance:
  - evitar re-render em excesso
  - carregar dados com paginação
- A UI nunca controla `tenant_id`.

---

## 9) Como rodar localmente (sugestão padrão)

> Ajuste esta seção conforme a infra que vocês escolherem (wp-env, Docker Compose, etc.).

### 9.1 Requisitos locais
- Docker + Docker Compose
- Node.js LTS
- Composer

### 9.2 Comandos (exemplo)
- Subir WP local (se usar wp-env):
  - `npm i`
  - `npm run wp-env:start`
- Instalar deps PHP:
  - `composer install`
- Build Admin:
  - `npm run build`
- Watch Admin:
  - `npm run dev`

> Se vocês usarem outro setup (docker-compose), documentem aqui os comandos reais.

---

## 10) Testes e qualidade

### 10.1 PHP
- `composer test` — PHPUnit
- `composer lint` — PHPCS (WordPress Coding Standards)
- (opcional) `composer stan` — PHPStan

### 10.2 JS
- `npm run lint`
- `npm run test` (se existir)
- `npm run build`

### 10.3 Testes obrigatórios (mínimo)
- Multi-tenant: A não acessa B (por endpoint e por export).
- Permissões: usuário sem cap não cria/edita/exclui.
- Validação: campos inválidos retornam erro correto.

---

## 11) Extensibilidade (Core ↔ Add-ons)

- Core deve expor:
  - actions/filters bem nomeados (ex.: `canil_core_before_save_dog`, `canil_core_after_save_litter`)
  - registro de módulos (se houver)
  - contratos claros (DTOs/schemas)
- Add-ons não devem modificar core diretamente.
- Add-ons usam REST/hook/camadas públicas do core.

---

## 12) Padrão de branches, commits e PRs

### 12.1 Branches
- `feature/<slug>`
- `fix/<slug>`
- `chore/<slug>`

### 12.2 PR template (resumo)
Todo PR deve incluir:
- O que mudou + por quê
- Como testar
- Migrações (se houver)
- Checklist DoD (abaixo)

---

## 13) Definition of Done (DoD)

- [ ] Multi-tenant aplicado (nenhum acesso cruzado)
- [ ] Permissões/caps checadas em ações sensíveis
- [ ] Validação e sanitização em inputs
- [ ] Testes adicionados/atualizados (incluindo tenant)
- [ ] CI verde
- [ ] Docs atualizadas (API/DB/fluxos), quando aplicável
- [ ] Sem mudanças silenciosas (nota no PR/changelog quando necessário)

---

## 14) Princípios de Desenvolvimento (DRY, SOLID, KISS)

### 14.1 DRY (Don't Repeat Yourself)

- **Código duplicado = código errado**: Extrair para funções/classes/traits
- **Usar classes base**: `BaseRepository`, `BaseController`, `BaseEntity`
- **Constantes centralizadas**: Ver `Constants/` (DogStatus, LitterStatus, etc.)
- **Helpers reutilizáveis**: Ver `Helpers/` (Sanitizer, Validator, DateHelper)
- **Componentes React reutilizáveis**: Ver `components/common/`

### 14.2 SOLID

| Princípio | Aplicação |
|-----------|-----------|
| Single Responsibility | Controllers roteiam, Services processam, Repositories persistem |
| Open/Closed | Usar hooks/filters para extensão, não modificar código base |
| Liskov Substitution | Interfaces bem definidas |
| Interface Segregation | Contracts pequenos e específicos |
| Dependency Inversion | Injeção de dependência |

### 14.3 KISS (Keep It Simple)

- Preferir soluções simples que funcionam
- Código legível > código "esperto"
- Complexidade só quando necessária

### 14.4 Anti-Patterns a Evitar

- **Magic numbers/strings**: Usar constantes
- **God classes**: Separar responsabilidades
- **Código duplicado**: Extrair para funções/classes
- **tenant_id do cliente**: SEMPRE do servidor

---

## 15) Documentação Obrigatória

Ver pasta `docs/` para documentação completa:

| Documento | Descrição |
|-----------|-----------|
| [PRD.md](docs/PRD.md) | Requisitos do produto |
| [ARQUITETURA.md](docs/ARQUITETURA.md) | Arquitetura técnica |
| [ROADMAP.md](docs/ROADMAP.md) | Plano de implementação |
| [DB.md](docs/DB.md) | Modelo de dados |
| [API.md](docs/API.md) | Especificação REST |
| [UX.md](docs/UX.md) | Guia de interface |
| [PADROES.md](docs/PADROES.md) | **Padrões de desenvolvimento (DRY, SOLID)** |
| [SEGURANCA.md](docs/SEGURANCA.md) | **Guia de segurança detalhado** |
| [MELHORIAS.md](docs/MELHORIAS.md) | Sugestões de melhorias |

---

## 16) Como pedir ajuda ao Copilot (Claude) de forma eficiente

Ao abrir Issue/PR, descreva sempre:
- Contexto e objetivo
- Regras SaaS (tenant/caps)
- API esperada (rotas, payloads)
- UI esperada (telas e estados)
- Critérios de aceite (checklist)
- Riscos (segurança/performance)

Exemplo de pedido:
> “Implemente CRUD de cães no core, com REST `/canil/v1/dogs`, filtros/paginação, UI list+form, forçando `tenant_id` do usuário logado, checando `manage_dogs`, com testes de isolamento A≠B.”

