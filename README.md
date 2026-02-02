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

# Instale dependências JS e build do admin
npm install
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
| [PADROES.md](docs/PADROES.md) | **Padrões de Desenvolvimento (DRY, SOLID)** |
| [SEGURANCA.md](docs/SEGURANCA.md) | **Guia de Segurança Detalhado** |
| [MELHORIAS.md](docs/MELHORIAS.md) | Sugestões de Melhorias |

## 🎯 Princípios de Desenvolvimento

Este projeto segue rigorosamente os seguintes princípios:

- **DRY** (Don't Repeat Yourself) - Código duplicado = código errado
- **SOLID** - Separação de responsabilidades, extensibilidade via hooks
- **KISS** (Keep It Simple) - Soluções simples que funcionam
- **YAGNI** (You Aren't Gonna Need It) - Sem código especulativo

Ver [PADROES.md](docs/PADROES.md) para guia completo.

## 🔒 Segurança (Multi-tenant)

Este plugin opera em modo **SaaS multi-tenant** com múltiplas camadas de segurança:

1. **Autenticação** - WordPress Authentication (cookies + nonces)
2. **Autorização** - Capabilities específicas por funcionalidade
3. **Isolamento Tenant** - Toda query filtra por `tenant_id`
4. **Validação** - Schema validation + type checking
5. **Sanitização** - `sanitize_*` na entrada, `esc_*` na saída
6. **Auditoria** - Log de operações críticas

```php
// ✅ CORRETO
$tenantId = get_current_user_id();

// ❌ PROIBIDO
$tenantId = $request->get_param('tenant_id');
```

Ver [SEGURANCA.md](docs/SEGURANCA.md) para guia completo de segurança.

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