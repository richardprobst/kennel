Guia em Etapas — Plugin WordPress (Canil SaaS)

Documento de orientação para desenvolvimento, revisão e manutenção
Data: 01/02/2026

Este documento descreve um roteiro operacional para construir, revisar e evoluir um plugin WordPress para gestão de canil de criação em modo SaaS (multi-tenant por usuário), utilizando o GitHub Copilot e selecionando Claude no Copilot Chat quando disponível.

0) Objetivo e princípios
Objetivo
Plugin WordPress para gestão de canil de criação (plantel, reprodução, ninhadas/filhotes, saúde, agenda, documentos e relatórios), em modo SaaS: cada usuário do WordPress gerencia seu próprio canil.
Princípios
•	Multi-tenant por usuário: todo registro associado a tenant_id = wp_users.ID.
•	Core estável e extensível + add-ons desacoplados.
•	Admin moderno (REST API + UI reativa).
•	Segurança e isolamento como requisito fundamental.
1) Setup no GitHub (Copilot + Claude)
1.1 Pré-requisitos
•	Repositório no GitHub com branch main protegida e PR obrigatório.
•	Workflows de CI (lint, testes e build) configurados.
•	GitHub Copilot habilitado na conta ou organização.
1.2 Selecionar Claude no Copilot
•	No Copilot Chat, use o seletor de modelo (model picker) e escolha um Claude (quando disponível).
•	Se existir modo Auto, você pode deixar em Auto e trocar para Claude quando a tarefa exigir.
Dica de uso por tipo de tarefa:
•	Claude Haiku: ajustes rápidos, tarefas repetitivas, refactors pequenos.
•	Claude Sonnet: tarefas do dia a dia (endpoints REST + UI + validações).
•	Claude Opus: decisões de arquitetura, migrações e fluxos complexos.
Observação: os modelos disponíveis variam por cliente/plano/região e alguns podem ser descontinuados. Verifique sempre a documentação oficial do GitHub Copilot.
2) Instruções persistentes para o Copilot (obrigatório)
Crie o arquivo:
•	.github/copilot-instructions.md
Essas instruções valem para Copilot Chat, Coding Agent e Code Review. Elas ajudam a manter padrão, segurança e consistência ao longo do projeto.
2.1 Template sugerido para .github/copilot-instructions.md
Copie/cole e ajuste conforme seu ambiente local:
# Instruções do repositório (Copilot)

## Contexto do projeto
- WordPress plugin SaaS (multi-tenant por usuário).
- Isolamento: todo dado tem tenant_id = user_id do WP (NUNCA aceitar tenant_id do client).
- Segurança: capabilities e nonces; sanitização/validação em toda entrada.

## Como rodar local
- (descreva aqui) ex.: wp-env / Docker / composer / npm
- Comandos:
  - composer test
  - composer lint
  - npm run build (admin UI)
  - npm run test

## Regras de arquitetura
- Core em plugin-core/
- Add-ons em addons/<nome>/ como plugins separados
- Separar camadas:
  - Domain (regras)
  - Infrastructure (db/queries)
  - Rest (controllers + schema)
  - AdminUI (React)

## Regras SaaS e acesso a dados (MANDATÓRIO)
- Toda query filtra por tenant_id = current_user_id().
- Toda mutação valida current_user_can(<cap>).
- Endpoints REST:
  - checar nonce/autenticação conforme padrão WP
  - validar schema e sanitizar campos
- Nunca expor dados de outro tenant em listagem, busca, export, logs.

## Definition of Done (DoD)
- Testes/validações adicionados quando muda regra.
- CI verde.
- Docs atualizadas (API/DB/fluxos).
3) Arquitetura-alvo (Core + Add-ons)
3.1 Plugin Core (obrigatório)
•	O Core deve incluir:
•	Tenancy + roles/caps.
•	CRUD: Cães, Ninhadas, Filhotes, Pessoas (compradores/interessados).
•	Eventos/timeline (reprodução + saúde) + pesagens.
•	Agenda e lembretes (mínimo: notificações internas).
•	Pedigree básico (3–5 gerações).
•	Relatórios essenciais + export CSV.
3.2 Add-ons (plugins separados)
•	Sugestões:
•	Genética/pedigree avançado (COI/AVK, trial mating, cor).
•	Financeiro e vendas (reservas, sinal, parcelas, recibos, integrações).
•	CRM e automação (lista de espera, pipeline, templates).
•	Site público (vitrine + SEO + formulários).
•	Integrações (entidades/registro, exports específicos).
4) Estrutura do repositório (sugestão)
Estrutura recomendada:
/
- plugin-core/
  - canil-core.php
  - includes/
    - Domain/
    - Infrastructure/
    - Rest/
    - AdminUI/
  - assets-admin/
  - migrations/
  - tests/
- addons/
  - canil-genetica/
  - canil-financeiro/
  - canil-crm/
  - canil-site-publico/
- docs/
  - GUIA-COPILOT-CLAUDE.docx (este documento)
  - PRD.md
  - ARQUITETURA.md
  - API.md
  - DB.md
  - UX.md
- .github/
  - copilot-instructions.md
  - workflows/
  - PULL_REQUEST_TEMPLATE.md
  - ISSUE_TEMPLATE/
5) Roadmap em etapas (com entregáveis)
Fluxo recomendado: Issue -> (Copilot Chat ou Assign Issue to Copilot) -> PR -> Copilot Code Review -> revisão humana -> merge.
ETAPA 0 — Fundação
Entregáveis:
•	Skeleton do Core.
•	Ambiente local documentado.
•	CI básico (lint/test/build).
•	Arquivo .github/copilot-instructions.md.
Definition of Done (DoD):
•	Instala e ativa sem warnings.
•	CI passa no PR.
ETAPA 1 — Tenancy (SaaS) + Modelo de dados
Entregáveis:
•	Tabelas (ou híbrido CPT + tabelas) com tenant_id.
•	Camada de acesso a dados com filtro obrigatório por tenant.
•	Roles/caps.
•	Auditoria mínima.
Definition of Done (DoD):
•	Nenhum endpoint/listagem vaza dados de outro usuário.
ETAPA 2 — REST API + Admin UI base
Entregáveis:
•	CRUD REST: cães, ninhadas, filhotes, pessoas.
•	Paginação e filtros (search/status/tags).
•	Admin UI (React) com listas e formulários.
ETAPA 3 — Reprodução (workflow)
Entregáveis:
•	Fluxo: cio -> cobertura/inseminação -> gestação -> parto.
•	Timeline por animal e por ninhada.
•	Eventos automáticos (previsões).
ETAPA 4 — Saúde + Pesagens + Agenda/Lembretes
Entregáveis:
•	Eventos de saúde.
•	Pesagens.
•	Calendário unificado.
•	Lembretes.
ETAPA 5 — Pedigree básico + Relatórios
Entregáveis:
•	Pedigree (3–5 gerações) + export.
•	Relatórios essenciais + CSV/PDF básico.
ETAPA 6 — Add-ons (primeiro pacote)
Entregáveis:
•	Escolher 1–2 add-ons para validar a arquitetura.
•	Exemplo: Financeiro (mínimo) + Site público (mínimo).
ETAPA 7 — Hardening
Entregáveis:
•	Testes (unit/integração).
•	Índices/otimizações por tenant_id.
•	Políticas de versão (SemVer) + changelog.
6) Como usar Copilot (Claude) no dia a dia do repo
6.1 Criar Issues amigáveis para o Copilot
•	Modelo recomendado de Issue:
•	Contexto.
•	Objetivo.
•	Regras SaaS (tenant/caps).
•	API/UI esperada.
•	Critérios de aceite (checklist).
6.2 Duas formas de delegar trabalho
Opção A — Copilot Chat -> criar PR (Coding Agent)
•	No GitHub.com Copilot Chat: use /task e descreva a tarefa.
•	Em IDEs suportadas: use o botão de delegar para Coding Agent (quando disponível).
•	Resultado esperado: Copilot cria uma branch e abre um PR para revisão.
Opção B — Atribuir Issue ao Copilot (Coding Agent)
•	Abra a Issue e atribua para Copilot (assignee), quando disponível.
•	O agente executa a tarefa e abre o PR.
6.3 Iterar no PR com @copilot
•	No PR, comente mencionando @copilot com pedidos objetivos (ex.: adicionar validação X, corrigir bug Y, adicionar teste Z).
•	Revisar alterações, rodar CI e manter o PR dentro do DoD.
7) Code Review com Copilot (sempre + revisão humana)
7.1 Pedir review do Copilot no PR
•	No PR, em Reviewers, selecione Copilot para receber comentários.
•	Resolver apontamentos, re-rodar CI e então fazer merge.
7.2 Configurar revisão automática (opcional)
Se fizer sentido para o time, habilite revisão automática no repositório ou organização. Atenção a limites e consumo de requisições conforme o plano.
8) Definition of Done (DoD) padrão
•	Tenant aplicado (nenhum acesso cruzado).
•	Permissões/capacidades checadas em ações sensíveis.
•	Validação e sanitização em inputs.
•	Testes adicionados/atualizados quando muda regra.
•	CI passando.
•	Docs atualizadas quando muda API/DB/fluxos.
•	Sem mudanças silenciosas (changelog/nota no PR).
