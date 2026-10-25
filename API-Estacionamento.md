# Referência de API Interna — Sistema de Estacionamento

**Produto:** ParkManager  
**Repositório:** https://github.com/Ronickbr/estacionamento  

---

## 1. Visão Geral

O sistema **não possui uma API REST pública documentada**. Existem apenas **dois endpoints internos** localizados em `src/public/api/`, destinados a consumo por AJAX das próprias páginas PHP. Não há autenticação, versionamento nem documentação OpenAPI/Swagger.

Base URL (desenvolvimento local): `http://localhost:8080/api/`

---

## 2. Endpoints

### 2.1 Calcular Saída

**Arquivo:** `src/public/api/calcular_saida.php`  
**Método:** POST (presumível, conforme uso interno)  
**Finalidade:** Calcular o valor a pagar de uma movimentação rotativa ao registrar a saída.

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `movimentacao_id` | int | Sim | ID da movimentação rotativa ativa |
| `forma_pagamento` | string | Não | `dinheiro`, `cartao_credito`, `cartao_debito`, `pix` |

**Resposta esperada (JSON, a confirmar no código):** valor calculado, tempo de permanência, detalhes da tarifa.

> O endpoint é chamado pelo fluxo `registrarSaida()` da classe `MovimentacaoRotativa`.

---

### 2.2 Verificar Mensalista

**Arquivo:** `src/public/api/verificar_mensalista.php`  
**Método:** GET/POST (presumível)  
**Finalidade:** Verificar se uma placa de veículo pertence a um mensalista ativo (controle de acesso).

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `placa` | string | Sim | Placa do veículo |

**Resposta esperada:** dados do mensalista se encontrado e ativo, caso contrário vazio/erro.

> Utilizado pelo fluxo `buscarPorPlaca()` da classe `Mensalista`.

---

## 3. Fluxo de Requisição (interno)

```
Página PHP (ex: movimentacao.php)
        ↓ (AJAX / include)
src/classes/*.php (regra de negócio)
        ↓
src/config/Database.php (PDO)
        ↓
MySQL 8.0
```

---

## 4. Recomendações para Evolução da API

Se o produto for evoluído para integração com app mobile ou sistemas externos, é recomendado:

1. Criar uma API REST adequada (ex: Slim, Laravel Lumen ou PHP puro com roteador).
2. Adicionar autenticação por token (JWT ou API key).
3. Versionar endpoints (`/api/v1/...`).
4. Documentar com OpenAPI/Swagger.
5. Implementar HTTPS e rate limiting.

Endpoints sugeridos para v1:
- `POST /api/v1/entrada` — registrar entrada rotativa
- `POST /api/v1/saida` — registrar saída e calcular tarifa
- `GET /api/v1/mensalista/{placa}` — verificar mensalista por placa
- `GET /api/v1/vagas-disponiveis/{estacionamento_id}` — consultar vagas
- `GET /api/v1/relatorios` — relatórios de ocupação e financeiro
