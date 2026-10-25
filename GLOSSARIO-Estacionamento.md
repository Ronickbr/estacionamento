# Glossário e Guia Rápido de Uso — ParkManager

**Produto:** Sistema de Estacionamento Rotativo e Mensal  
**Repositório:** https://github.com/Ronickbr/estacionamento  

---

## 1. Glossário de Termos

| Termo | Significado |
|-------|-------------|
| **Estacionamento rotativo** | Modelo onde veículos ficam por curtos períodos e pagam pelo tempo de permanência. |
| **Mensalista** | Cliente fixo com contrato mensal, vaga opcional e cobrança recorrente. |
| **Vaga** | Espaço de estacionamento identificado por número e tipo (`rotativa`, `mensal`, `ambas`). |
| **Movimentação** | Registro de entrada e/ou saída de um veículo rotativo (`movimentacoes_rotativas`). |
| **Cupom** | Comprovante de entrada gerado para impressão térmica (dados do veículo, data, hora, ticket). |
| **Tarifa** | Valor cobrado com base no tempo de permanência (fração, hora, faixa ou diária). |
| **Cortesia** | Período inicial gratuito (padrão: 15 minutos) descontado do cálculo. |
| **Diária** | Valor máximo cobrado por dia, independentemente do tempo excedente. |
| **Cobrança mensal** | Fatura gerada para mensalistas (`cobrancas_mensais`), com vencimento e status. |
| **Inadimplência** | Cobranças mensais vencidas e não pagas (status `vencido`). |
| **Soft delete** | Exclusão lógica: registro fica inativo (`ativo = 0`) em vez de ser apagado. |
| **Micro-SaaS** | Software como serviço de nicho, de baixo custo e operação simples. |

---

## 2. Perfis de Usuário

| Perfil | Pode |
|--------|------|
| **Admin** | Acesso total: usuários, estacionamentos, vagas, tarifas, configurações, relatórios. |
| **Operador** | Operações diárias: entrada/saída de veículos, mensalistas, pagamentos. |

---

## 3. Fluxo Operacional Diário

### Abertura
1. Acesse `http://localhost:8080`.
2. Login com credenciais de admin ou operador.
3. Verifique o dashboard: vagas disponíveis e movimentações ativas.

### Entrada de veículo rotativo
1. Vá em **Movimentação**.
2. Informe a placa do veículo.
3. Selecione uma vaga disponível.
4. Confirme a entrada — um cupom será gerado para impressão.

### Saída de veículo rotativo
1. Busque o veículo pela placa ou ticket.
2. Confira o tempo de permanência e o valor calculado automaticamente.
3. Selecione a forma de pagamento (dinheiro, cartão de crédito, cartão de débito, PIX).
4. Confirme a saída — a vaga é liberada automaticamente.

### Mensalistas
1. Cadastre o cliente com CPF, dados do veículo e valor mensal.
2. Opcionalmente atribua uma vaga fixa.
3. O sistema gera a primeira cobrança automaticamente (vencimento dia 10 do mês seguinte).
4. Registre pagamentos à medida que forem efetuados.
5. Acesse relatórios para acompanhar inadimplência (cobranças vencidas).

### Relatórios
- **Ocupação:** quantas vagas estão ocupadas por estacionamento.
- **Movimentação:** entradas/saídas por período.
- **Financeiro:** receita de rotativos e cobranças mensais pagas/pendentes.

---

## 4. Estrutura de Pastas (resumo)

```
estacionamento/
├── docker-compose.yml    # Orquestração dos containers
├── Dockerfile            # Imagem PHP 8.2 + Apache
├── database/init.sql     # Schema e dados iniciais (seed)
├── PRD-Estacionamento.txt   # PRD original (em português)
├── README.md             # Guia de instalação original
├── config/apache/        # Configuração do Apache
└── src/
    ├── classes/          # Regras de negócio (Auth, Database, Estacionamento, Mensalista, MovimentacaoRotativa, TarifaManager)
    ├── config/           # Conexão com banco (Database.php)
    └── public/           # Páginas PHP (login, dashboard, movimentacao, mensalistas, relatorios, tarifas, configuracoes, usuarios, api, ...)
```
