# TRD — Technical Requirements Document

**Produto:** ParkManager — Sistema de Estacionamento Rotativo e Mensal  
**Repositório:** https://github.com/Ronickbr/estacionamento  
**Branch:** `main`  
**Idioma:** Português do Brasil  

---

## 1. Visão Técnica

Monolito web escrito em **PHP puro (sem framework)**, servido por **Apache** dentro de contêiner Docker, com banco **MySQL 8.0** e interface em **Bootstrap 5.1.3 + JavaScript vanilla**. Orquestração via **Docker Compose** (3 serviços: web, db, phpmyadmin).

A camada de negócio está em `src/classes/` (classes PHP) e a camada de apresentação em `src/public/` (páginas PHP + 2 endpoints de API interna).

---

## 2. Stack de Tecnologia

| Camada | Tecnologia | Versão | Observações |
|--------|------------|--------|-------------|
| Runtime | PHP | 8.2 (Dockerfile) | README diz 8.1; Dockerfile usa `php:8.2-apache` |
| Servidor Web | Apache | (base imagem PHP) | `mod_rewrite`, `mod_headers` habilitados |
| Banco de Dados | MySQL | 8.0 | Imagem oficial `mysql:8.0` |
| Frontend | Bootstrap | 5.1.3 | CSS/JS via CDN ou assets locais |
| Frontend | JavaScript | Vanilla | Sem framework (React/Vue/etc.) |
| Containerização | Docker + Docker Compose | — | 3 redes/serviços |
| Gerenciador DB | phpMyAdmin | — | Porta 8081 |
| Dependência PHP | Composer | — | Copiado no Dockerfile, mas não há `composer.json` no repo |
| Extensões PHP | gd, pdo, pdo_mysql, mysqli, zip | — | Instaladas no Dockerfile |

**Inconsistência detectada:** README informa PHP 8.1, mas o Dockerfile usa `php:8.2-apache`. Ajustar um dos dois para evitar divergência em produção.

---

## 3. Arquitetura

### 3.1 Padrão
Arquitetura em camadas simples (sem MVC framework):

```
Requisição HTTP (Apache)
        ↓
src/public/*.php  (Controlador / Página)
        ↓
src/classes/*.php (Model / Regra de negócio)
        ↓
src/config/Database.php (PDO → MySQL 8.0)
```

Cada página PHP pública instancia diretamente as classes de negócio necessárias. Não há roteador, container de injeção de dependência nem template engine (HTML é misturado com PHP nas páginas).

### 3.2 Serviços Docker

| Serviço | Imagem | Porta | Volumes | Variáveis de ambiente-chave |
|---------|--------|-------|---------|------------------------------|
| `web` | build (Dockerfile) | 8080:80 | `./src` → `/var/www/html`, `./config/apache` → sites-available | `DB_HOST=db`, `DB_NAME=estacionamento`, `DB_USER=root`, `DB_PASS=root123` |
| `db` | `mysql:8.0` | 3306:3306 | `mysql_data`, `./database/init.sql` → initdb | `MYSQL_ROOT_PASSWORD=root123`, `MYSQL_DATABASE=estacionamento`, `MYSQL_USER=estacionamento_user`, `MYSQL_PASSWORD=userpassword` |
| `phpmyadmin` | `phpmyadmin/phpmyadmin` | 8081:80 | — | `PMA_HOST=db`, `PMA_USER=root`, `PMA_PASSWORD=root123` |

**Problemas de credenciais detectados:**
- O serviço `web` conecta ao MySQL como `root`/`root123`.
- O MySQL cria também `estacionamento_user`/`userpassword`, mas o PHP não usa esse usuário.
- Senhas fixas e expostas no `docker-compose.yml` (não usam `.env`).

---

## 4. Modelo de Dados (10 tabelas)

Tabelas criadas por `database/init.sql`:

1. **`usuarios`** — id, nome, email (único), senha (hash), tipo (`admin`/`operador`), ativo, timestamps.
2. **`estacionamentos`** — id, nome, endereço, total_vagas, vagas_ocupadas, valor_hora, valor_fracao, tempo_fracao, ativo.
3. **`vagas`** — id, estacionamento_id (FK), numero, tipo (`rotativa`/`mensal`/`ambas`), ocupada, ativo.
4. **`mensalistas`** — id, nome, cpf (único), telefone, email, endereço, placa_veiculo, modelo_veiculo, cor_veiculo, estacionamento_id (FK), vaga_fixa_id (FK), valor_mensal, data_inicio, data_fim, status (`ativo`/`inativo`/`suspenso`).
5. **`movimentacoes_rotativas`** — id, estacionamento_id, vaga_id, placa_veiculo, modelo_veiculo, cor_veiculo, data_entrada, data_saida, tempo_permanencia (min), valor_calculado, valor_pago, forma_pagamento, status (`ativo`/`finalizado`/`cancelado`), observacoes, usuário entrada/saída.
6. **`cobrancas_mensais`** — id, mensalista_id (FK), mes_referencia, valor, data_vencimento, data_pagamento, forma_pagamento, status (`pendente`/`pago`/`vencido`/`cancelado`).
7. **`acessos_mensalistas`** — id, mensalista_id (FK), data_acesso, tipo (`entrada`/`saida`), vaga_id (FK).
8. **`configuracoes`** — id, chave (único), valor, descrição (key-value do sistema).

Índices: `placa_veiculo` e `data_entrada` em movimentações; `cpf` e `placa_veiculo` em mensalistas; `status` e `data_vencimento` em cobranças.

---

## 5. Classes PHP (Camada de Negócio)

| Classe | Arquivo | Responsabilidade |
|--------|---------|------------------|
| `Database` | `src/config/Database.php` | Conexão PDO MySQL via `$_ENV` (DB_HOST, DB_NAME, DB_USER, DB_PASS). |
| `Auth` | `src/classes/Auth.php` | Login/logout, sessão, `requireLogin`, `requireAdmin`, `createUser` (senha com `password_hash`). |
| `Estacionamento` | `src/classes/Estacionamento.php` | CRUD de estacionamentos e vagas, estatísticas de ocupação, relatório de ocupação, sincronização de vagas em transação. |
| `Mensalista` | `src/classes/Mensalista.php` | CRUD de mensalistas, busca por placa, registro de acessos, geração de cobrança mensal, registro de pagamento, cobranças pendentes/vencidas, relatórios de inadimplência. |
| `MovimentacaoRotativa` | `src/classes/MovimentacaoRotativa.php` | Entrada/saída de veículos rotativos, geração de cupom (impressão térmica), cálculo de tarifa dinâmica via TarifaManager, relatório de movimentação. |
| `TarifaManager` | `src/classes/TarifaManager.php` | Cálculo de tarifa por fração/hora/faixas, cortesia, tolerância, teto de diária, desconto mensalista, formatação de valor/tempo. |

---

## 6. Páginas Públicas (src/public)

| Arquivo | Função |
|---------|--------|
| `index.php` | Página de login. |
| `dashboard.php` | Dashboard com estatísticas. |
| `movimentacao.php` | Gestão de movimentação rotativa (entrada/saída). |
| `mensalistas.php` | Gestão de mensalistas e cobranças. |
| `relatorios.php` | Relatórios de movimentação e financeiro. |
| `tarifas.php` | Configuração de tarifas. |
| `configuracoes.php` | Configurações gerais do sistema. |
| `usuarios.php` | Gestão de usuários do sistema. |
| `logout.php` | Encerra sessão. |
| `test_db.php` | Teste de conexão com banco. |
| `.htaccess` | Configuração Apache. |

**Nota:** O README menciona `estacionamentos.php`, mas o arquivo **não existe** no diretório `src/public/`. Essa página está ausente (funcionalidade de cadastrar estacionamentos pode estar em outra página ou não implementada).

### 6.1 Endpoints de API Interna

| Endpoint | Arquivo | Função |
|----------|---------|--------|
| `api/calcular_saida.php` | `src/public/api/calcular_saida.php` | Calcula valor da saída (uso AJAX). |
| `api/verificar_mensalista.php` | `src/public/api/verificar_mensalista.php` | Verifica mensalista por placa (uso AJAX). |

Não há documentação OpenAPI/Swagger, nem autenticação em esses endpoints.

---

## 7. Segurança

| Item | Status | Observação |
|------|--------|------------|
| Hash de senha | ✅ | `password_hash`/`password_verify` (bcrypt). |
| Prepared statements | ✅ | PDO em todas as classes. |
| Controle de acesso por perfil | ✅ | `requireAdmin` restringe rotas a `admin`. |
| Proteção CSRF | ❌ | Não implementada. |
| Proteção XSS | ⚠️ Parcial | Saída de dados não é explicitamente escapada em todas as páginas. |
| HTTPS/TLS | ❌ | Apenas HTTP local (Docker). |
| Senhas fixas no compose | ⚠️ | `root123`, `userpassword` expostas no YAML; não usam `.env`. |
| Auditoria de ações | ⚠️ Parcial | `usuario_entrada_id`/`usuario_saida_id` em movimentações, mas nenhum log de auditoria genérico. |

---

## 8. Variáveis de Ambiente e Configuração

Variáveis lidas pelo PHP (`$_ENV`):

| Variável | Padrão | Uso |
|----------|--------|-----|
| `DB_HOST` | `db` | Host do MySQL (nome do serviço Docker). |
| `DB_NAME` | `estacionamento` | Nome do banco. |
| `DB_USER` | `''` | Usuário de conexão PHP (vazio no padrão). |
| `DB_PASS` | `''` | Senha de conexão PHP (vazia no padrão). |

**Conflito:** O `docker-compose.yml` define `DB_USER=root` / `DB_PASS=root123` para o serviço `web`, mas a classe `Database` espera `DB_USER`/`DB_PASS`. Como o compose passa essas variáveis, o PHP conecta como root. O usuário `estacionamento_user` criado no MySQL não é utilizado pela aplicação.

---

## 9. Fluxos de Dados Principais

### 9.1 Entrada Rotativa
1. Operador informa placa e seleciona vaga em `movimentacao.php`.
2. `MovimentacaoRotativa::registrarEntrada()` valida vaga disponível, insere em `movimentacoes_rotativas` (status `ativo`), marca vaga como ocupada, gera cupom HTML.

### 9.2 Saída Rotativa
1. Operador busca movimentação ativa por placa ou ID.
2. `MovimentacaoRotativa::registrarSaida()` calcula tempo de permanência, chama `TarifaManager::calcularTarifa()`, grava `valor_pago`, `forma_pagamento`, status `finalizado`, libera vaga.

### 9.3 Mensalista
1. Cadastro em `mensalistas.php` → `Mensalista::criar()` insere cliente e gera primeira cobrança (`cobrancas_mensais`, vencimento +1 mês, dia 10).
2. Pagamento registrado via `registrarPagamento()` → status `pago`.
3. `verificarVencimentos()` marca cobranças vencidas (rodar via cron/task externo — não há agendador no código).

---

## 10. Implantação (Resumo)

1. `docker-compose up -d` sobe os 3 containers.
2. Banco é inicializado automaticamente por `database/init.sql` (schema + seed: 1 admin, 1 estacionamento, 10 vagas, configurações).
3. Acessar `http://localhost:8080` (sistema) com `admin` / `admin123` (usuário seed no banco tem e-mail `admin@estacionamento.com`, senha hash bcrypt; credenciais README dizem `admin`/`admin123` — verificar consistência).
4. phpMyAdmin em `http://localhost:8081`.

---

## 11. Dívida Técnica e Recomendações

1. **Alinhar versão PHP:** README diz 8.1; Dockerfile usa 8.2. Padronizar.
2. **Adicionar `.env`:** Remover senhas fixas do `docker-compose.yml`.
3. **Criar `composer.json`:** Composer está instalado mas não há arquivo de dependências.
4. **Implementar `estacionamentos.php`:** Referenciado no README, ausente no repo.
5. **Adicionar CSRF e escapamento de saída (XSS).**
6. **Criar endpoint de API REST adequado** (atualmente apenas 2 endpoints internos sem autenticação).
7. **Implementar agendamento de cobranças vencidas** (cron ou comando CLI).
8. **Backup automático do volume MySQL.**
