# MELHORIAS.md - Sugestões de Melhorias para o Sistema

## Plugin WordPress para Gestão de Canil (SaaS Multi-tenant)

**Data:** 02/02/2026  
**Versão:** 1.0

Este documento lista sugestões de melhorias identificadas durante a análise do projeto, organizadas por categoria e prioridade.

---

## 1. Melhorias de Arquitetura

### 1.1 Implementar Cache Layer (Alta Prioridade)

**Problema:** Queries repetidas podem impactar performance.

**Sugestão:**
- Implementar camada de cache usando WordPress Transients
- Cache por tenant para dados frequentes (contadores, listas)
- Invalidação automática em mutações

```php
// Exemplo
class CacheService {
    public function get(string $key, int $tenantId): mixed {
        return get_transient("canil_{$tenantId}_{$key}");
    }
    
    public function set(string $key, int $tenantId, mixed $value, int $ttl = 3600): void {
        set_transient("canil_{$tenantId}_{$key}", $value, $ttl);
    }
    
    public function invalidate(string $pattern, int $tenantId): void {
        // Invalidar cache por padrão
    }
}
```

### 1.2 Queue System para Tarefas Assíncronas (Média Prioridade)

**Problema:** Operações pesadas (reports, exports) podem bloquear a UI.

**Sugestão:**
- Usar Action Scheduler (plugin WooCommerce) ou wp-cron aprimorado
- Processar exports grandes em background
- Enviar emails de lembrete assincronamente

### 1.3 Event Sourcing para Auditoria (Baixa Prioridade)

**Problema:** Auditoria atual é básica, não permite reconstruir estado.

**Sugestão:**
- Implementar event sourcing para entidades críticas
- Permitir "playback" de histórico
- Útil para disputes e suporte

---

## 2. Melhorias de Segurança

### 2.1 Rate Limiting por Tenant (Alta Prioridade)

**Problema:** Sem limite, um tenant pode sobrecarregar o sistema.

**Sugestão:**
```php
class RateLimiter {
    public function check(int $tenantId, string $action): bool {
        $key = "rate_limit_{$tenantId}_{$action}";
        $count = get_transient($key) ?: 0;
        
        if ($count >= $this->getLimit($action)) {
            return false; // Rate limited
        }
        
        set_transient($key, $count + 1, 60); // 1 minuto
        return true;
    }
}
```

### 2.2 Two-Factor Authentication (Média Prioridade)

**Problema:** Senhas fracas podem comprometer dados do canil.

**Sugestão:**
- Integrar com plugins de 2FA existentes
- Ou implementar 2FA simples por email
- Obrigatório para ações críticas (export de dados)

### 2.3 GDPR Compliance Tools (Média Prioridade)

**Problema:** Dados de pessoas (compradores) podem requerer conformidade GDPR.

**Sugestão:**
- Ferramenta de export de dados pessoais
- Ferramenta de anonimização/exclusão
- Logs de consentimento
- Política de retenção configurável

### 2.4 IP Allowlist para API (Baixa Prioridade)

**Problema:** Acesso API aberto a qualquer IP.

**Sugestão:**
- Configuração opcional de IPs permitidos por tenant
- Útil para integrações B2B

---

## 3. Melhorias de UX/UI

### 3.1 Dark Mode (Média Prioridade)

**Problema:** Muitos usuários preferem modo escuro.

**Sugestão:**
- Detectar preferência do sistema
- Toggle manual
- Persistir preferência por usuário

### 3.2 Atalhos de Teclado (Média Prioridade)

**Problema:** Usuários avançados preferem navegação por teclado.

**Sugestão:**
- `Ctrl+N` → Novo cão/ninhada
- `Ctrl+S` → Salvar
- `Ctrl+/` → Busca rápida
- Modal de ajuda com `?`

### 3.3 Busca Global (Alta Prioridade)

**Problema:** Buscar em várias entidades é trabalhoso.

**Sugestão:**
- Command palette estilo VS Code (`Ctrl+K`)
- Busca em: cães, ninhadas, filhotes, pessoas
- Ações rápidas: "Adicionar cão", "Ver agenda"

```
┌─────────────────────────────────────────────────┐
│  🔍 Buscar ou executar comando...              │
├─────────────────────────────────────────────────┤
│  Luna                                           │
│  ─────────────────────────────────────────────  │
│  🐕 Luna - Golden Retriever                     │
│  👶 Luna's Ninhada A                            │
│  📅 Vacina Luna - 15/02                         │
│  ─────────────────────────────────────────────  │
│  ➕ Adicionar novo cão                          │
│  📊 Ver relatórios                              │
└─────────────────────────────────────────────────┘
```

### 3.4 Drag and Drop para Fotos (Média Prioridade)

**Problema:** Upload de múltiplas fotos é trabalhoso.

**Sugestão:**
- Área de drop para arrastar fotos
- Reordenação por drag and drop
- Preview com crop/rotate

### 3.5 Templates de Eventos (Média Prioridade)

**Problema:** Registrar eventos repetitivos é tedioso.

**Sugestão:**
- Templates salvos pelo usuário
- Ex: "Protocolo vacinal padrão" → cria múltiplos eventos
- Aplicável a lote de animais

### 3.6 Dashboard Customizável (Baixa Prioridade)

**Problema:** Dashboard fixo não atende todos os perfis.

**Sugestão:**
- Widgets arrastáveis
- Opções de quais widgets mostrar
- Layouts salvos por usuário

---

## 4. Melhorias Funcionais

### 4.1 Import de Dados (Alta Prioridade)

**Problema:** Migrar de planilhas/sistemas é manual.

**Sugestão:**
- Import de CSV com mapeamento de colunas
- Import de JSON (backup/restore)
- Validação prévia com preview
- Undo de import

### 4.2 Notificações Push (Média Prioridade)

**Problema:** Lembretes só aparecem ao acessar o sistema.

**Sugestão:**
- Web Push Notifications (browser)
- Notificação mobile via PWA
- Integração Telegram/WhatsApp (add-on)

### 4.3 Compartilhamento de Perfil (Média Prioridade)

**Problema:** Mostrar cães/ninhadas para interessados requer add-on.

**Sugestão no Core:**
- Link público temporário para perfil de cão/filhote
- QR Code para compartilhar
- Expira após X dias
- Sem dados sensíveis

### 4.4 Árvore Genealógica Interativa (Média Prioridade)

**Problema:** Pedigree atual é estático.

**Sugestão:**
- Zoom e pan na árvore
- Clique para expandir ramos
- Highlight de ancestrais comuns
- Cálculo de parentesco

### 4.5 Gráficos de Crescimento (Média Prioridade)

**Problema:** Pesagens são lista, não gráfico.

**Sugestão:**
- Gráfico de linha por filhote
- Comparativo entre irmãos
- Curva de referência da raça
- Alertas de desvio

### 4.6 Contratos Digitais (Baixa Prioridade - Add-on)

**Problema:** Contratos são feitos fora do sistema.

**Sugestão:**
- Templates de contrato editáveis
- Merge com dados do filhote/comprador
- Assinatura digital básica
- Armazenamento no sistema

---

## 5. Melhorias de Performance

### 5.1 Lazy Loading de Fotos (Alta Prioridade)

**Problema:** Páginas com muitas fotos demoram a carregar.

**Sugestão:**
- Intersection Observer para lazy load
- Placeholder blur durante load
- Thumbnails otimizados

### 5.2 Virtual Scrolling para Listas Grandes (Média Prioridade)

**Problema:** Listas muito grandes afetam performance.

**Sugestão:**
- react-window ou react-virtual
- Renderizar apenas items visíveis
- Aplicar em: eventos, pesagens, pessoas

### 5.3 Service Worker / PWA (Média Prioridade)

**Problema:** Sem conexão = sem acesso.

**Sugestão:**
- Cache de assets estáticos
- Offline reading de dados básicos
- Sync quando reconectar

### 5.4 Compressão de Imagens (Alta Prioridade)

**Problema:** Fotos grandes consomem espaço e banda.

**Sugestão:**
- Compressão no upload
- Múltiplos tamanhos (thumb, medium, full)
- WebP quando suportado
- Limite de tamanho por tenant

---

## 6. Melhorias de Integração

### 6.1 Webhook System (Média Prioridade)

**Problema:** Integrações externas são difíceis.

**Sugestão:**
- Configurar webhooks por evento
- Eventos: dog.created, litter.born, puppy.sold
- Retry com exponential backoff
- Logs de delivery

```php
// Config por tenant
[
    'webhooks' => [
        [
            'url' => 'https://api.example.com/hook',
            'events' => ['puppy.sold'],
            'secret' => 'hmac-secret'
        ]
    ]
]
```

### 6.2 API Pública com OAuth (Baixa Prioridade)

**Problema:** API atual só para admin autenticado.

**Sugestão:**
- OAuth 2.0 para integrações de terceiros
- Scopes por recurso
- Rate limiting por token
- Dashboard de aplicações

### 6.3 Zapier/Make Integration (Média Prioridade)

**Problema:** Automações requerem desenvolvimento.

**Sugestão:**
- App Zapier/Make oficial
- Triggers e Actions pré-configurados
- Democratiza automações

---

## 7. Melhorias de Monetização (Add-ons)

### 7.1 Planos e Limites

**Sugestão:**
- Plano Free: 5 cães, 2 ninhadas/ano
- Plano Pro: ilimitado + add-ons
- Plano Enterprise: multi-canil + suporte

### 7.2 Marketplace de Add-ons

**Sugestão:**
- Loja de add-ons de terceiros
- Sistema de licenças
- Reviews e ratings
- Revenue share

### 7.3 White Label

**Sugestão:**
- Remover branding para agências
- CSS customizável
- Logo/cores do cliente

---

## 8. Melhorias de Internacionalização

### 8.1 Multi-idioma (i18n)

**Problema:** Interface apenas em português.

**Sugestão:**
- Usar wp-i18n para strings
- Gerar arquivos .pot
- Traduções: EN, ES inicialmente
- Contribuições da comunidade

### 8.2 Localização de Formatos

**Problema:** Datas e moedas fixas.

**Sugestão:**
- Formato de data configurável
- Moeda por tenant
- Unidades (kg/lb)
- Timezone

### 8.3 Suporte a RTL

**Problema:** Não funciona em idiomas RTL.

**Sugestão:**
- CSS com suporte RTL
- Testar com Árabe/Hebraico
- Ícones direcionais

---

## 9. Melhorias de Análise

### 9.1 Analytics Dashboard

**Problema:** Falta visão analítica do negócio.

**Sugestão:**
- KPIs: taxa de venda, tempo de reserva, filhotes/ano
- Gráficos de tendência
- Comparativo com período anterior
- Metas configuráveis

### 9.2 Reports Customizáveis

**Problema:** Relatórios são fixos.

**Sugestão:**
- Builder de relatórios
- Filtros e agrupamentos
- Salvar templates
- Agendar envio por email

### 9.3 Export Avançado

**Problema:** Export apenas CSV básico.

**Sugestão:**
- Excel com formatação
- PDF com layout profissional
- Filtros e colunas customizáveis
- Templates de export

---

## 10. Priorização Sugerida

### Fase 1 (MVP)
1. Busca Global
2. Lazy Loading de Fotos
3. Compressão de Imagens
4. Rate Limiting por Tenant
5. Import de CSV

### Fase 2 (Melhorias)
1. Cache Layer
2. Notificações Push
3. Gráficos de Crescimento
4. Dark Mode
5. Atalhos de Teclado

### Fase 3 (Avançado)
1. Webhook System
2. PWA/Offline
3. Analytics Dashboard
4. Multi-idioma
5. Reports Customizáveis

### Fase 4 (Enterprise)
1. API OAuth
2. White Label
3. Marketplace de Add-ons
4. Event Sourcing
5. Planos e Limites

---

## 11. Próximos Passos

1. Revisar sugestões com stakeholders
2. Priorizar por impacto vs esforço
3. Adicionar itens aprovados ao backlog
4. Estimar e planejar sprints
5. Implementar incrementalmente

---

*Documento gerado em: 02/02/2026*
