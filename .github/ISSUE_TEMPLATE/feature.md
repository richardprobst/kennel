---
name: Feature Request
about: Solicitar uma nova funcionalidade
title: '[FEATURE] '
labels: enhancement
assignees: ''
---

## Contexto

<!-- Descreva o contexto e o problema que esta feature resolve -->

## Objetivo

<!-- Descreva claramente o que deve ser implementado -->

## Regras SaaS

<!-- Marque os itens aplicáveis -->

- [ ] Dados são isolados por tenant (tenant_id)
- [ ] Verificar capabilities necessárias: `manage_dogs`, `manage_litters`, etc.
- [ ] Validação e sanitização de inputs

## API Esperada

<!-- Se aplicável, descreva os endpoints REST -->

**Rotas:**
- `GET /canil/v1/...`
- `POST /canil/v1/...`

**Payload de exemplo:**
```json
{
  
}
```

## UI Esperada

<!-- Descreva as telas e estados da interface -->

- [ ] Tela de listagem
- [ ] Formulário de criação/edição
- [ ] Estados: loading, empty, error

## Critérios de Aceite

<!-- Checklist do que deve funcionar -->

- [ ] 
- [ ] 
- [ ] 

## Riscos

<!-- Considere segurança, performance, etc. -->

- Segurança:
- Performance:

## Referências

<!-- Links para documentação, designs, etc. -->

- [PRD](/docs/PRD.md)
- [Arquitetura](/docs/ARQUITETURA.md)
- [API](/docs/API.md)
