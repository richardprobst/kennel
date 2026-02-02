# Instruções do repositório (Copilot)

## Contexto do projeto

- **Plugin WordPress SaaS** para gestão de canil de criação
- **Multi-tenant por usuário**: cada usuário WP gerencia seu próprio canil
- **Isolamento obrigatório**: todo dado tem `tenant_id = user_id` do WP
- **NUNCA** aceitar `tenant_id` vindo do client (request body, querystring, headers)
- Segurança: capabilities, nonces, sanitização/validação em toda entrada

## Stack

- **Backend**: PHP 8.1+, WordPress, REST API, MySQL/MariaDB
- **Frontend Admin**: React com @wordpress/components, @wordpress/api-fetch
- **Qualidade**: PHPUnit, PHPCS (WordPress Coding Standards), ESLint

## Como rodar local

```bash
# Instalar dependências PHP
composer install

# Instalar dependências JS
npm install

# Subir ambiente (wp-env ou Docker)
npm run wp-env:start

# Build do admin (React)
npm run build

# Watch mode
npm run dev
```

## Comandos de qualidade

```bash
# PHP
composer test         # PHPUnit
composer lint         # PHPCS
composer lint:fix     # PHPCBF (auto-fix)

# JavaScript
npm run lint          # ESLint
npm run lint:fix      # ESLint auto-fix
npm run test          # Jest
npm run build         # Build produção
```

## Regras de arquitetura

- **Core** em `plugin-core/`
- **Add-ons** em `addons/<nome>/` como plugins separados
- Separar camadas:
  - `Domain/` - regras de negócio (puras)
  - `Infrastructure/` - persistência (repositories, queries)
  - `Rest/` - controllers REST + schemas
  - `AdminUI/` - React (pages, components)

## Regras SaaS e acesso a dados (MANDATÓRIO)

```php
// CORRETO: tenant sempre do servidor
$tenantId = get_current_user_id();

// ERRADO: nunca aceitar do request
$tenantId = $request->get_param('tenant_id'); // PROIBIDO!
```

- **Toda query** filtra por `tenant_id = current_user_id()`
- **Toda mutação** valida `current_user_can(<cap>)`
- Endpoints REST:
  - Checar nonce/autenticação conforme padrão WP
  - Validar schema e sanitizar campos
- **Nunca** expor dados de outro tenant em listagem, busca, export, logs

## Capabilities do plugin

```php
'manage_kennel'   // Gerenciar configurações do canil
'manage_dogs'     // CRUD de cães
'manage_litters'  // CRUD de ninhadas  
'manage_puppies'  // CRUD de filhotes
'manage_people'   // CRUD de pessoas
'view_reports'    // Visualizar relatórios
'manage_settings' // Alterar configurações
```

## REST API

- **Namespace**: `/canil/v1`
- **Rotas**: `/dogs`, `/litters`, `/puppies`, `/people`, `/events`
- **Formato de resposta**:
```json
{
  "data": [...],
  "meta": { "total": 50, "page": 1, "per_page": 20, "total_pages": 3 }
}
```

## Validação e Sanitização

```php
// Entrada
$name = sanitize_text_field($request->get_param('name'));
$email = sanitize_email($request->get_param('email'));
$id = absint($request->get_param('id'));

// Saída
esc_html($value);
esc_attr($value);
esc_url($value);
```

## Banco de dados

- Prefixo: `{$wpdb->prefix}canil_`
- **Todas tabelas** têm `tenant_id` com índice
- Usar `$wpdb->prepare()` para queries
- Paginação obrigatória em listagens
- Sem `SELECT *` em listas grandes

## Hooks para extensibilidade

```php
// Actions
do_action('canil_core_before_save_dog', $dog);
do_action('canil_core_after_save_dog', $dog);
do_action('canil_core_event_created', $event);

// Filters
apply_filters('canil_core_event_types', $types);
apply_filters('canil_core_dog_statuses', $statuses);
```

## Definition of Done (DoD)

- [ ] Multi-tenant aplicado (nenhum acesso cruzado)
- [ ] Permissões/caps checadas em ações sensíveis
- [ ] Validação e sanitização em inputs
- [ ] Testes adicionados/atualizados quando muda regra
- [ ] CI passando
- [ ] Docs atualizadas quando muda API/DB/fluxos
- [ ] Sem mudanças silenciosas (changelog/nota no PR)

## Testes obrigatórios (mínimo)

- **Multi-tenant**: User A não acessa dados de User B
- **Permissões**: Usuário sem cap não cria/edita/exclui
- **Validação**: Campos inválidos retornam erro correto

## Padrão de commits

Usar Conventional Commits:
- `feat:` nova funcionalidade
- `fix:` correção de bug
- `docs:` documentação
- `style:` formatação
- `refactor:` refatoração
- `test:` testes
- `chore:` manutenção

## Documentação

Ver pasta `docs/` para:
- `PRD.md` - Requisitos do produto
- `ARQUITETURA.md` - Arquitetura técnica
- `ROADMAP.md` - Plano de implementação
- `DB.md` - Modelo de dados
- `API.md` - Especificação REST
- `UX.md` - Guia de interface
