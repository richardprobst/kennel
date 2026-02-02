# Canil Site Público

Add-on para o plugin **Canil Core** que permite criar páginas públicas do canil com vitrine de filhotes e formulário de interesse.

## Requisitos

- WordPress 6.0+
- PHP 8.1+
- **Canil Core 1.0.0+** (obrigatório)

## Instalação

1. Certifique-se de que o plugin **Canil Core** está instalado e ativado.
2. Faça upload da pasta `canil-site-publico` para `/wp-content/plugins/`.
3. Ative o plugin "Canil Site Público" no painel de administração.
4. Configure as opções em **Canil > Site Público**.

## Funcionalidades

### Informações do Canil
- Nome e descrição do canil
- Informações de contato (telefone, e-mail, WhatsApp)
- Links para redes sociais (Instagram, Facebook)

### Vitrine de Filhotes
- Listagem de filhotes disponíveis
- Filtros por raça, sexo e cor
- Cards com foto, detalhes e status
- Página de detalhe com galeria

### Formulário de Interesse
- Coleta dados de contato do interessado
- Vinculação ao filhote específico
- Envio de e-mail ao criador
- Opção de contato via WhatsApp

## Shortcodes

### `[canil_info]`

Exibe informações do canil.

**Parâmetros:**
| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| `show_name` | `yes` | Exibe o nome do canil |
| `show_description` | `yes` | Exibe a descrição |
| `show_contact` | `yes` | Exibe informações de contato |
| `show_social` | `yes` | Exibe links de redes sociais |
| `class` | - | Classe CSS adicional |

**Exemplo:**
```
[canil_info show_social="no"]
```

### `[canil_filhotes]`

Lista filhotes disponíveis com filtros.

**Parâmetros:**
| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| `status` | `available` | Status dos filhotes (`available`, `reserved`) |
| `limit` | `12` | Número máximo de filhotes |
| `columns` | `3` | Número de colunas (1-6) |
| `show_filters` | `yes` | Exibe filtros de busca |
| `show_price` | `auto` | Exibe preços (`yes`, `no`, `auto`) |
| `detail_page` | - | URL da página de detalhe |
| `class` | - | Classe CSS adicional |

**Exemplo:**
```
[canil_filhotes limit="6" columns="3" detail_page="/filhote"]
```

### `[canil_filhote]`

Exibe detalhe de um filhote específico.

**Parâmetros:**
| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| `id` | - | ID do filhote (ou usa `?filhote=ID` da URL) |
| `show_price` | `auto` | Exibe preço |
| `show_parents` | `yes` | Exibe informações dos pais |
| `show_gallery` | `yes` | Exibe galeria de fotos |
| `interest_form` | `yes` | Exibe formulário de interesse |
| `class` | - | Classe CSS adicional |

**Exemplo:**
```
[canil_filhote show_price="yes" show_parents="yes"]
```

### `[canil_interesse]`

Formulário de demonstração de interesse.

**Parâmetros:**
| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| `filhote_id` | `0` | ID do filhote relacionado |
| `filhote_nome` | - | Nome do filhote |
| `class` | - | Classe CSS adicional |

**Exemplo:**
```
[canil_interesse filhote_id="123" filhote_nome="Max"]
```

## API REST

O add-on disponibiliza endpoints públicos (não requerem autenticação):

### `GET /wp-json/canil-site-publico/v1/puppies`

Lista filhotes disponíveis.

**Parâmetros:**
- `status` - `available` (padrão) ou `reserved`
- `breed` - Filtrar por raça
- `sex` - `male` ou `female`
- `color` - Filtrar por cor
- `limit` - Número máximo de resultados

### `GET /wp-json/canil-site-publico/v1/puppies/{id}`

Retorna detalhes de um filhote.

### `GET /wp-json/canil-site-publico/v1/puppies/filters`

Retorna opções de filtro disponíveis (raças, cores).

### `POST /wp-json/canil-site-publico/v1/interest`

Envia formulário de interesse.

**Campos:**
- `name` (obrigatório)
- `email` (obrigatório)
- `phone` (obrigatório)
- `city`
- `message`
- `puppy_id`
- `puppy_name`
- `contact_whatsapp`
- `privacy_accepted` (obrigatório)

## Hooks

### Actions

```php
// Após envio bem-sucedido do formulário de interesse
do_action( 'canil_site_publico_interest_submitted', array $data, string $to_email );
```

### Filters

Os dados exibidos podem ser filtrados através dos hooks do Canil Core.

## Configurações

Acesse **Canil > Site Público** para configurar:

1. **Informações do Canil**: Nome, descrição, endereço
2. **Contato**: Telefone, e-mail, WhatsApp
3. **Redes Sociais**: Instagram, Facebook
4. **Exibição de Filhotes**: Filtros, preços
5. **Formulário de Interesse**: E-mail de destino

## Estrutura de Pastas

```
canil-site-publico/
├── canil-site-publico.php     # Plugin principal
├── includes/
│   ├── Plugin.php             # Classe principal
│   ├── Domain/
│   │   └── PublicPuppyService.php
│   ├── Rest/Controllers/
│   │   ├── PublicPuppiesController.php
│   │   └── InterestFormController.php
│   ├── Settings/
│   │   └── settings-page.php
│   └── Shortcodes/
│       ├── BaseShortcode.php
│       ├── KennelInfoShortcode.php
│       ├── PuppiesListShortcode.php
│       ├── PuppyDetailShortcode.php
│       └── InterestFormShortcode.php
└── assets/
    ├── css/
    │   ├── frontend.css
    │   └── admin.css
    └── js/
        └── frontend.js
```

## Exemplo de Páginas

### Página "Sobre o Canil"

```
[canil_info]
```

### Página "Filhotes Disponíveis"

```
[canil_filhotes limit="12" columns="3" detail_page="/filhote"]
```

### Página "Detalhe do Filhote"

```
[canil_filhote]
```

A URL desta página deve receber o parâmetro `?filhote=ID`.

### Página "Contato"

```
[canil_info show_description="no"]

[canil_interesse]
```

## Licença

GPL v2 or later

## Suporte

Para suporte, abra uma issue no repositório do projeto.
