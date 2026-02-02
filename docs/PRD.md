# PRD - Product Requirements Document

## Plugin WordPress para Gestão de Canil (SaaS Multi-tenant)

**Data:** 02/02/2026  
**Versão:** 1.0

---

## 1. Visão Geral do Produto

### 1.1 Objetivo
Desenvolver um plugin WordPress robusto e escalável para gestão completa de canis de criação, operando em modo SaaS (Software as a Service) com isolamento multi-tenant por usuário WordPress.

### 1.2 Problema que Resolve
Criadores de cães enfrentam dificuldades em:
- Gerenciar informações do plantel de forma centralizada
- Acompanhar ciclos reprodutivos e gestações
- Registrar histórico de saúde e pesagens
- Controlar ninhadas e filhotes desde nascimento até venda
- Manter documentação organizada (pedigrees, contratos)
- Comunicar-se com interessados e compradores
- Gerar relatórios para tomada de decisão

### 1.3 Solução Proposta
Um sistema integrado que oferece:
- **Core Plugin**: Funcionalidades essenciais gratuitas ou básicas
- **Add-ons Premium**: Funcionalidades avançadas como plugins separados
- **SaaS Multi-tenant**: Cada usuário WP tem seu próprio canil isolado

---

## 2. Público-Alvo

### 2.1 Usuários Primários
- **Criadores Profissionais**: Canis registrados com múltiplos cães e ninhadas anuais
- **Criadores Amadores**: Pessoas com 1-5 cães que fazem crias ocasionais
- **Clubes e Associações**: Entidades que gerenciam múltiplos criadores

### 2.2 Personas

#### Persona 1: Maria (Criadora Profissional)
- 45 anos, cria Golden Retrievers há 15 anos
- Tem 12 cães no plantel, 3-4 ninhadas por ano
- Usa planilhas Excel e anotações em papel
- Quer automatizar lembretes de vacinação e acompanhar gestações
- Precisa de controle financeiro e pedigrees

#### Persona 2: Carlos (Criador Amador)
- 35 anos, tem 2 Bulldogs Franceses
- Faz 1-2 crias por ano
- Busca solução simples e acessível
- Quer organizar documentação e histórico dos cães
- Precisa de ajuda para comunicar com interessados

---

## 3. Requisitos Funcionais

### 3.1 Core Plugin (Obrigatório)

#### 3.1.1 Gestão de Cães (Plantel)
| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-001 | Cadastrar cão com nome, chip, registro, raça, cor, data nascimento, sexo | Alta |
| RF-002 | Upload de fotos (principal + galeria) | Alta |
| RF-003 | Definir status: ativo, reprodutor(a), aposentado, vendido, falecido | Alta |
| RF-004 | Vincular pai e mãe (pedigree interno) | Alta |
| RF-005 | Buscar e filtrar cães por nome, raça, status, sexo | Alta |
| RF-006 | Visualizar perfil completo com timeline de eventos | Média |
| RF-007 | Adicionar tags/categorias personalizadas | Média |

#### 3.1.2 Gestão de Ninhadas
| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-010 | Criar ninhada vinculando matriz e reprodutor | Alta |
| RF-011 | Registrar cobertura/inseminação (data, método, observações) | Alta |
| RF-012 | Acompanhar gestação com previsão de parto | Alta |
| RF-013 | Registrar parto (data, tipo, observações) | Alta |
| RF-014 | Definir status: planejada, confirmada, em gestação, nascida, encerrada | Alta |
| RF-015 | Timeline automática de eventos reprodutivos | Média |

#### 3.1.3 Gestão de Filhotes
| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-020 | Cadastrar filhote com identificador único, sexo, cor, peso ao nascer | Alta |
| RF-021 | Upload de fotos por filhote | Alta |
| RF-022 | Vincular à ninhada e pais | Alta |
| RF-023 | Definir status: disponível, reservado, vendido, retido, falecido | Alta |
| RF-024 | Registrar chip e documentação quando disponível | Média |
| RF-025 | Histórico de pesagens | Média |

#### 3.1.4 Gestão de Pessoas
| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-030 | Cadastrar pessoa: nome, email, telefone, endereço, tipo | Alta |
| RF-031 | Tipos: interessado, comprador, veterinário, parceiro | Alta |
| RF-032 | Vincular pessoa a filhote (reserva/venda) | Alta |
| RF-033 | Histórico de interações | Média |
| RF-034 | Notas e observações | Média |

#### 3.1.5 Eventos e Timeline
| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-040 | Registrar eventos de saúde: vacinas, vermífugos, exames | Alta |
| RF-041 | Registrar eventos reprodutivos: cio, cobertura, diagnóstico gestação | Alta |
| RF-042 | Registrar pesagens com data e peso | Alta |
| RF-043 | Timeline unificada por animal | Média |
| RF-044 | Tipos de evento extensíveis (hooks para add-ons) | Média |

#### 3.1.6 Agenda e Lembretes
| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-050 | Calendário visual com eventos | Alta |
| RF-051 | Lembretes automáticos: vacinas, vermífugos, retornos | Alta |
| RF-052 | Previsões automáticas: parto (63 dias após cobertura) | Alta |
| RF-053 | Notificações internas no admin | Média |
| RF-054 | (Add-on) Notificações por email/SMS | Baixa |

#### 3.1.7 Pedigree Básico
| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-060 | Visualizar árvore genealógica 3-5 gerações | Alta |
| RF-061 | Gerar PDF do pedigree | Média |
| RF-062 | (Add-on) Cálculos genéticos avançados | Baixa |

#### 3.1.8 Relatórios Essenciais
| ID | Requisito | Prioridade |
|----|-----------|------------|
| RF-070 | Relatório de plantel atual | Alta |
| RF-071 | Relatório de ninhadas por período | Alta |
| RF-072 | Relatório de filhotes (vendidos/disponíveis) | Alta |
| RF-073 | Export CSV básico | Alta |
| RF-074 | (Add-on) Relatórios avançados e dashboards | Baixa |

### 3.2 Add-ons (Plugins Separados)

#### 3.2.1 Genética/Pedigree Avançado
- Coeficiente de Inbreeding (COI)
- Ancestral Variety Coefficient (AVK)
- Trial Mating (simulação de acasalamento)
- Herança de cores e características

#### 3.2.2 Financeiro e Vendas
- Reservas com sinal
- Parcelas e pagamentos
- Geração de recibos/contratos
- Relatórios financeiros
- Integração com gateways

#### 3.2.3 CRM e Automação
- Lista de espera inteligente
- Pipeline de vendas
- Templates de mensagens
- Automação de follow-up
- Integração WhatsApp/Email

#### 3.2.4 Site Público
- Página pública do canil
- Vitrine de filhotes disponíveis
- SEO otimizado
- Formulário de interesse
- Galeria de fotos

#### 3.2.5 Integrações
- Exportação para entidades (CBKC, AKC)
- Importação de pedigrees
- Integração com prontuário veterinário

---

## 4. Requisitos Não-Funcionais

### 4.1 Segurança
| ID | Requisito | Descrição |
|----|-----------|-----------|
| RNF-001 | Multi-tenant | Isolamento total entre usuários/canis |
| RNF-002 | Autenticação | Via WordPress (cookies + nonces) |
| RNF-003 | Autorização | Capabilities específicas por função |
| RNF-004 | Validação | Sanitização de toda entrada de dados |
| RNF-005 | Auditoria | Log de operações críticas |

### 4.2 Performance
| ID | Requisito | Descrição |
|----|-----------|-----------|
| RNF-010 | Paginação | Todas listagens paginadas |
| RNF-011 | Índices | Índice em tenant_id e campos de filtro |
| RNF-012 | Lazy Loading | Carregar dados sob demanda |
| RNF-013 | Caching | Cache de consultas frequentes |

### 4.3 Usabilidade
| ID | Requisito | Descrição |
|----|-----------|-----------|
| RNF-020 | Responsivo | UI funcional em tablets |
| RNF-021 | Acessibilidade | Compatível com screen readers |
| RNF-022 | UX | Feedback claro de ações e erros |
| RNF-023 | Onboarding | Wizard de configuração inicial |

### 4.4 Manutenibilidade
| ID | Requisito | Descrição |
|----|-----------|-----------|
| RNF-030 | Código | WordPress Coding Standards |
| RNF-031 | Testes | Cobertura mínima em regras críticas |
| RNF-032 | Docs | API e DB documentados |
| RNF-033 | Extensibilidade | Hooks para add-ons |

---

## 5. Integrações

### 5.1 WordPress
- REST API nativa
- Roles e Capabilities
- Media Library
- Transients (cache)
- Cron (agendamento)

### 5.2 Futuras (Add-ons)
- Gateways de pagamento
- Serviços de email (SMTP, SendGrid)
- WhatsApp Business API
- Entidades de registro (CBKC, AKC)

---

## 6. Métricas de Sucesso

### 6.1 Adoção
- Instalações ativas
- Usuários ativos mensais
- Taxa de conversão free → premium

### 6.2 Engajamento
- Cães cadastrados por usuário
- Ninhadas registradas por ano
- Frequência de uso

### 6.3 Satisfação
- NPS (Net Promoter Score)
- Taxa de churn
- Tickets de suporte

---

## 7. Fora do Escopo (v1)

- App mobile nativo
- Modo offline
- Multi-idioma (i18n) completo
- Marketplace de add-ons
- White-label

---

## 8. Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| Vazamento de dados entre tenants | Baixa | Crítico | Testes obrigatórios de isolamento |
| Performance com muitos registros | Média | Alto | Índices e paginação desde início |
| Complexidade de pedigree | Média | Médio | Limitar gerações no core |
| Adoção lenta | Média | Alto | Versão gratuita funcional |

---

## 9. Timeline de Alto Nível

Ver documento [ROADMAP.md](./ROADMAP.md) para detalhamento por fases.

| Fase | Descrição | Estimativa |
|------|-----------|------------|
| 0 | Fundação e Setup | 2 semanas |
| 1 | Multi-tenant e Modelo de Dados | 2 semanas |
| 2 | REST API e UI Base | 4 semanas |
| 3 | Reprodução (Workflow) | 3 semanas |
| 4 | Saúde + Pesagens + Agenda | 3 semanas |
| 5 | Pedigree + Relatórios | 2 semanas |
| 6 | Primeiro Add-on | 3 semanas |
| 7 | Hardening e Lançamento | 2 semanas |

**Total estimado:** ~21 semanas (5 meses)

---

## 10. Aprovações

| Papel | Nome | Data | Assinatura |
|-------|------|------|------------|
| Product Owner | | | |
| Tech Lead | | | |
| Stakeholder | | | |

---

*Documento gerado em: 02/02/2026*
