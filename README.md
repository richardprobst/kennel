# Canil Core - Plugin WordPress para Gestão de Canil

[![CI](https://github.com/richardprobst/kennel/actions/workflows/ci.yml/badge.svg)](https://github.com/richardprobst/kennel/actions/workflows/ci.yml)

Plugin WordPress para gestão completa de canil de criação, operando em modo SaaS (Software as a Service) com isolamento multi-tenant por usuário WordPress.

## 🎯 Funcionalidades

- **Gestão de Cães (Plantel)**: Cadastro completo com fotos, pedigree, status
- **Ninhadas**: Fluxo completo de reprodução (cio → cobertura → gestação → parto)
- **Filhotes**: Controle desde nascimento até venda
- **Pessoas**: Gestão de interessados, compradores, veterinários
- **Saúde**: Vacinas, vermífugos, exames, pesagens
- **Agenda**: Calendário integrado com lembretes
- **Pedigree**: Visualização de árvore genealógica (3-5 gerações)
- **Relatórios**: Plantel, ninhadas, filhotes com export CSV/PDF

## 🏗️ Arquitetura

```
plugin-core/          # Plugin principal
├── includes/
│   ├── Domain/       # Regras de negócio
│   ├── Infrastructure/   # Persistência (repositories)
│   ├── Rest/         # REST API controllers
│   └── AdminUI/      # Interface React
├── migrations/       # Versões do banco de dados
└── tests/           # Testes automatizados

addons/              # Add-ons (plugins separados)
├── canil-financeiro/
├── canil-crm/
├── canil-genetica/
└── canil-site-publico/

docs/                # Documentação completa
```

## 📋 Requisitos

- **WordPress**: 6.0+
- **PHP**: 8.1+
- **MySQL**: 5.7+ ou MariaDB 10.3+
- **Node.js**: 18+ (para desenvolvimento)

## 🚀 Instalação

### Desenvolvimento Local

```bash
# Clone o repositório
git clone https://github.com/richardprobst/kennel.git
cd kennel

# Instale dependências PHP
cd plugin-core
composer install

# Instale dependências JS
cd assets-admin-src
npm install

# Build do admin
npm run build

# Ou modo watch para desenvolvimento
npm run dev
```

### Ambiente WordPress (wp-env)

```bash
npm install
npm run wp-env:start
```

## 📖 Documentação

| Documento | Descrição |
|-----------|-----------|
| [PRD.md](docs/PRD.md) | Product Requirements Document |
| [ARQUITETURA.md](docs/ARQUITETURA.md) | Arquitetura do Sistema |
| [ROADMAP.md](docs/ROADMAP.md) | Plano de Implementação por Fases |
| [DB.md](docs/DB.md) | Modelo de Dados |
| [API.md](docs/API.md) | Especificação REST API |
| [UX.md](docs/UX.md) | Guia de Experiência do Usuário |
| [MELHORIAS.md](docs/MELHORIAS.md) | Sugestões de Melhorias |

## 🔒 Segurança (Multi-tenant)

Este plugin opera em modo **SaaS multi-tenant**:

- Cada usuário WordPress = 1 Tenant/Canil
- **Isolamento total** de dados entre tenants
- `tenant_id` é **SEMPRE** obtido do servidor (`get_current_user_id()`)
- **NUNCA** aceitar `tenant_id` do cliente

```php
// ✅ CORRETO
$tenantId = get_current_user_id();

// ❌ PROIBIDO
$tenantId = $request->get_param('tenant_id');
```

## 🧪 Testes

```bash
cd plugin-core

# PHPUnit
composer test

# PHPCS (lint)
composer lint

# ESLint (JS)
cd assets-admin-src
npm run lint
```

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch: `git checkout -b feature/nova-funcionalidade`
3. Commit suas mudanças: `git commit -m 'feat: adiciona nova funcionalidade'`
4. Push para a branch: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

Ver [AGENTS.md](AGENTS.md) para convenções e regras do projeto.

## 📝 License

Este projeto está sob a licença GPL-2.0 - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 📞 Suporte

- Issues: [GitHub Issues](https://github.com/richardprobst/kennel/issues)
- Documentação: [/docs](docs/)

---

Desenvolvido com ❤️ para criadores de cães