# PRD — Sistema de Estacionamento Rotativo e Mensal

**Produto:** ParkManager (Micro-SaaS de gestão de estacionamento)  
**Repositório:** https://github.com/Ronickbr/estacionamento  
**Versão:** v1.0 (baseada na branch `main`, commit atual)  
**Idioma:** Português do Brasil  

---

## 1. Visão Geral do Produto

O ParkManager é um micro-SaaS web para gestão completa de estacionamentos, abrangendo dois modelos de operação:

* **Rotativo:** cobrança por tempo de permanência (entrada/saída de veículos ocasionais).
* **Mensal:** cobrança recorrente para clientes fixos com contrato, vaga opcional e controle de inadimplência.

O sistema roda localmente via Docker (PHP + Apache + MySQL) e é voltado a proprietários de pequenos e médios estacionamentos que desejam substituir controles manuais (papel/Excel) por uma solução automatizada de baixo custo.

---

## 2. Personas (Usuários Alvo)

| Persona | Perfil | Necessidade Principal |
|---------|--------|-----------------------|
| **Administrador** | Dono/gerente do estacionamento | Configurar pátios, tarifas, usuários do sistema; acompanhar dashboard e relatórios financeiros. |
| **Operador** | Funcionário do caixa/recepção | Registrar entrada e saída de veículos rotativos, gerar cupons, receber pagamentos, consultar placa. |
| **Mensalista** | Cliente fixo com contrato | Ter vaga (opcional), acesso identificado por placa, receber cobrança mensal e pagar recorrência. |

---

## 3. Funcionalidades Principais (Requisitos Funcionais)

### 3.1 Autenticação e Usuários (RF-AUTH)
- Login com e-mail e senha (hash `bcrypt` via `password_hash`/`password_verify`).
- Sessão PHP nativa (`$_SESSION`).
- Dois perfis: `admin` (acesso total) e `operador` (restrito a operações diárias).
- Logout e proteção de rotas (`requireLogin`, `requireAdmin`).

### 3.2 Gestão de Estacionamentos (RF-EST)
- Cadastro, edição e listagem de múltiplos estacionamentos (pátios).
- Campos: nome, endereço, total de vagas, valor por hora, valor por fração, tempo da fração (minutos).
- Soft delete (`ativo = 1/0`) em vez de exclusão física.
- Sincronização automática de vagas conforme `total_vagas` configurado (cria/desativa vagas em lote).

### 3.3 Gestão de Vagas (RF-VAGA)
- Cadastro de vagas com número e tipo (`rotativa`, `mensal`, `ambas`).
- Controle de ocupação (`ocupada = 0/1`) em tempo real.
- Consulta de vagas disponíveis por tipo.
- Exclusão lógica impedida se a vaga estiver ocupada.

### 3.4 Estacionamento Rotativo (RF-ROT)
- Registro de entrada: placa, modelo, cor, vaga, usuário responsável.
- Geração de cupom de entrada (HTML para impressão térmica simulada) com ticket, data/hora e instruções.
- Registro de saída com cálculo automático de tarifa (TarifaManager).
- Formas de pagamento: dinheiro, cartão de crédito, cartão de débito, PIX.
- Busca rápida de veículo por placa (somente movimentações ativas).
- Relatório de movimentação por período.

### 3.5 Estacionamento Mensal (RF-MEN)
- Cadastro completo do mensalista: nome, CPF (único), telefone, e-mail, endereço, dados do veículo (placa, modelo, cor).
- Vaga fixa opcional vinculada ao mensalista.
- Valor mensal, data de início e fim do contrato.
- Status: `ativo`, `inativo`, `suspenso`.
- Geração automática de cobrança mensal no cadastro (vencimento dia 10 do mês seguinte).
- Registro de pagamento de cobrança (data, forma, status `pago`).
- Controle de inadimplência: cobranças vencidas marcadas automaticamente como `vencido`.
- Registro de acessos do mensalista (entrada/saída com data/hora e vaga).
- Relatório de mensalistas (ativos, inativos, suspensos, receita potencial).

### 3.6 Tarifas (RF-TAR)
- Sistema de tarifas por fração ou por hora (configurável).
- Faixas de tarifa personalizáveis (tempo → valor).
- Período de cortesia configurável (padrão: 15 minutos).
- Tolerância de minutos excedentes.
- Valor máximo de diária (teto).
- Desconto opcional para mensalista.

### 3.7 Relatórios (RF-REL)
- Dashboard: vagas ocupadas vs. disponíveis, receita do dia/mês, movimentações ativas, cobranças pendentes.
- Relatório de ocupação por período (entradas, saídas, tempo médio).
- Relatório financeiro consolidado (cobranças mensais por período).
- Exportação para impressão.

### 3.8 Configurações do Sistema (RF-CFG)
- Tabela chave-valor `configuracoes` para nome do sistema, CNPJ, endereço, telefone, instruções do cupom, largura do cupom, etc.
- Atualização de configurações via interface (`configuracoes.php`, `tarifas.php`).

---

## 4. Requisitos Não Funcionais (RNF)

| Categoria | Requisito |
|-----------|-----------|
| **Segurança** | Senhas hash (bcrypt), sessões PHP, separação de perfis (admin/operador), prepared statements (PDO) contra SQL injection. |
| **Performance** | Índices em `placa_veiculo`, `data_entrada`, `cpf`, `status` de cobranças; consultas otimizadas. |
| **Confiabilidade** | Transações SQL para sincronização de vagas (rollback em caso de falha). |
| **Usabilidade** | Bootstrap 5.1.3, interface responsiva, feedback visual de ações. |
| **Implantação** | Docker + Docker Compose; MySQL 8.0; phpMyAdmin para administração do banco. |
| **Manutenibilidade** | Código PHP procedural orientado a classes, sem framework; separação entre classes de negócio (`src/classes`) e páginas (`src/public`). |
| **Compatibilidade** | Navegadores Chrome, Firefox, Safari, Edge; dispositivos móveis via Bootstrap responsivo. |

---

## 5. Escopo Fora do Produto (v1.0)

Os itens abaixo estão listados como "melhorias futuras" no README e **não estão implementados**:

* API REST para integração externa (existem apenas 2 endpoints internos de API: `calcular_saida.php` e `verificar_mensalista.php`).
* App mobile.
* Integração com gateways de pagamento (PIX, cartão) — hoje o pagamento é apenas registrado manualmente no sistema.
* Reconhecimento de placas por OCR.
* Notificações por e-mail/SMS.
* Multi-tenancy (isolamento por cliente).
* Relatórios avançados com gráficos.
* Autenticação de dois fatores (2FA).
* Backup automático agendado.

---

## 6. Métricas de Sucesso (MVP)

* Reduzir o tempo de registro de entrada/saída para menos de 2 minutos por veículo.
* Eliminar erros de cálculo de tarifa (100% das saídas com valor calculado automaticamente).
* Disponibilidade do sistema local enquanto o container Docker estiver rodando.
* Baixa curva de aprendizado: operador capaz de realizar abertura e fechamento de caixa sem treinamento formal.

---

## 7. Roadmap Sugerido

1. **v1.0 (atual):** MVP rotativo + mensal com Docker local.
2. **v1.1:** API REST adequada + autenticação por token.
3. **v1.2:** Integração com pagamento PIX (QR Code) e cartão via gateway.
4. **v1.3:** App mobile para mensalistas (visualizar vagas, pagar, estender).
5. **v2.0:** Multi-tenancy e nuvem (SaaS multi-cliente).
