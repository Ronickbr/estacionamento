# 🅿️ ParkManager — Sistema de Estacionamento Rotativo e Mensal

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB3?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![Apache](https://img.shields.io/badge/Apache-HTTP_Server-2D4E59?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

---

Sistema completo de gerenciamento de estacionamento desenvolvido em **PHP**, **Bootstrap 5** e **MySQL 8**, containerizado com **Docker + Docker Compose**. Suporta dois modelos de operação: **estacionamento rotativo** (cobrança por tempo de permanência) e **mensalistas** (cobrança recorrente com contrato).

Projeto do tipo **Micro-SaaS**: leve, de baixo custo e pronto para rodar localmente com um único comando.

---

## 📋 Funcionalidades

### 🔄 Estacionamento Rotativo
- ✅ Registro de entrada e saída de veículos
- ✅ Cálculo automático de tarifas por tempo (fração, hora ou faixa)
- ✅ Controle de vagas disponíveis em tempo real
- ✅ Busca rápida por placa do veículo
- ✅ Geração de cupom de entrada (impressão térmica simulada)
- ✅ Formas de pagamento: dinheiro, cartão de crédito, cartão de débito, PIX
- ✅ Relatórios de movimentação por período

### 👥 Mensalistas
- ✅ Cadastro completo de clientes mensais (incluindo dados do veículo)
- ✅ Controle de acesso por identificação de placa
- ✅ Geração automática de cobranças mensais (vencimento dia 10)
- ✅ Gestão de contratos com data de início e fim
- ✅ Vagas dedicadas (opcional)
- ✅ Controle de inadimplência (cobranças vencidas)
- ✅ Relatórios financeiros de mensalistas

### 🏢 Gerenciamento
- ✅ Múltiplos estacionamentos (pátios)
- ✅ Configuração de vagas por tipo (rotativa, mensal, ambas)
- ✅ Dashboard com estatísticas em tempo real
- ✅ Sistema de autenticação (admin / operador)
- ✅ Relatórios detalhados de ocupação e faturamento
- ✅ Configurações do sistema (nome, CNPJ, endereço, telefone, cupom)

---

## 🚀 Tecnologias Utilizadas

| Camada | Tecnologia |
|--------|------------|
| Backend | PHP 8.2 + Apache |
| Frontend | Bootstrap 5.1.3 + JavaScript vanilla |
| Banco de Dados | MySQL 8.0 |
| Containerização | Docker + Docker Compose |
| Gerenciamento DB | phpMyAdmin |

---

## 🏗️ Estrutura do Projeto

```
estacionamento/
├── docker-compose.yml          # Orquestração dos containers
├── Dockerfile                  # Imagem PHP 8.2 + Apache personalizada
├── LICENSE                     # Licença MIT
├── PRD-Estacionamento.md       # Documento de Requisitos de Produto
├── TRD-Estacionamento.md       # Technical Requirements Document
├── DEPLOY-Estacionamento.md    # Guia de implantação
├── API-Estacionamento.md       # Referência da API interna
├── GLOSSARIO-Estacionamento.md # Glossário e guia rápido de uso
├── README.md                   # Este arquivo
├── database/
│   └── init.sql                # Schema e dados iniciais (seed)
├── config/apache/
│   └── 000-default.conf        # Configuração do Apache
└── src/
    ├── classes/                # Classes PHP de negócio
    │   ├── Auth.php            # Autenticação e sessões
    │   ├── Database.php        # Conexão PDO com MySQL
    │   ├── Estacionamento.php  # Gestão de estacionamentos e vagas
    │   ├── Mensalista.php      # Gestão de mensalistas e cobranças
    │   ├── MovimentacaoRotativa.php  # Entrada/saída rotativa
    │   └── TarifaManager.php   # Cálculo de tarifas dinâmicas
    ├── config/
    │   └── Database.php
    └── public/                 # Páginas públicas
        ├── index.php           # Login
        ├── dashboard.php       # Dashboard principal
        ├── movimentacao.php    # Gestão de movimentação rotativa
        ├── mensalistas.php     # Gestão de mensalistas
        ├── estacionamentos.php # Gestão de estacionamentos
        ├── relatorios.php      # Relatórios
        ├── tarifas.php         # Configuração de tarifas
        ├── configuracoes.php   # Configurações gerais
        ├── usuarios.php        # Gestão de usuários do sistema
        ├── logout.php          # Encerramento de sessão
        ├── test_db.php         # Teste de conexão
        ├── .htaccess           # Configuração Apache
        ├── api/                # Endpoints internos
        │   ├── calcular_saida.php
        │   └── verificar_mensalista.php
        └── assets/             # CSS, JS e imagens
```

---

## 📦 Instalação

### Pré-requisitos
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)
- Git (opcional)

### Passos

```bash
# 1. Clone o repositório
git clone https://github.com/Ronickbr/estacionamento.git
cd estacionamento

# 2. Inicie os containers
docker-compose up -d

# 3. Aguarde a inicialização (o MySQL pode demorar alguns minutos)
docker-compose logs -f db

# 4. Acesse o sistema
#    Sistema principal:  http://localhost:8080
#    phpMyAdmin:         http://localhost:8081
```

---

## 🔑 Credenciais Padrão

### Sistema (web)
| Campo | Valor |
|-------|-------|
| Usuário | `admin@estacionamento.com` |
| Senha | `admin123` |

> ⚠️ Altere a senha padrão após o primeiro acesso.

### Banco de dados
| Campo | Valor |
|-------|-------|
| Host | `localhost:3306` |
| Usuário root | `root` |
| Senha root | `root123` |
| Banco | `estacionamento` |

### phpMyAdmin
| Campo | Valor |
|-------|-------|
| URL | `http://localhost:8081` |
| Usuário | `estacionamento_user` |
| Senha | `userpassword` |

---

## 🔌 Portas Utilizadas

| Serviço | Porta Externa | Porta Interna | Finalidade |
|---------|---------------|---------------|------------|
| Sistema web | `8080` | `80` | ParkManager |
| phpMyAdmin | `8081` | `80` | Administração do MySQL |
| MySQL | `3306` | `3306` | Banco de dados |

Para alterar, edite a seção `ports` no `docker-compose.yml`.

---

## ⚙️ Configurações Avançadas

Edite o arquivo `docker-compose.yml` para personalizar variáveis de ambiente:

```yaml
environment:
  MYSQL_DATABASE: estacionamento_db
  MYSQL_USER: estacionamento_user
  MYSQL_PASSWORD: sua_senha
  MYSQL_ROOT_PASSWORD: root_password
```

> Recomendação: mova essas variáveis para um arquivo `.env` e não versão senhas em produção.

---

## 💾 Backup e Restauração

```bash
# Criar backup
docker exec estacionamento-mysql mysqldump -u estacionamento_user -p estacionamento_db > backup.sql

# Restaurar backup
docker exec -i estacionamento-mysql mysql -u estacionamento_user -p estacionamento_db < backup.sql
```

---

## 🐛 Solução de Problemas

| Problema | Solução |
|----------|---------|
| Container não inicia | `docker-compose logs` para verificar; `docker-compose down && docker-compose up -d` para reiniciar |
| Erro de conexão com banco | Aguarde alguns minutos para o MySQL inicializar; verifique `docker-compose logs mysql` |
| Permissão de arquivos (Linux/Mac) | `sudo chown -R www-data:www-data src/` e `sudo chmod -R 755 src/` |
| phpMyAdmin não conecta | Verifique se o serviço `db` está saudável antes de acessar |

---

## 🔐 Segurança

- Senhas armazenadas com hash **bcrypt** (`password_hash` / `password_verify`)
- Consultas preparadas via **PDO** (proteção contra SQL injection)
- Controle de acesso por perfil: `admin` e `operador`
- Sessões PHP nativas
- Prepared statements em todas as operações de banco

---

## 📈 Melhorias Futuras

- [ ] API REST completa com autenticação por token
- [ ] App mobile para mensalistas
- [ ] Integração com gateways de pagamento (PIX, cartão)
- [ ] Reconhecimento de placas por OCR
- [ ] Notificações por e-mail/SMS
- [ ] Multi-tenancy (SaaS multi-cliente)
- [ ] Relatórios avançados com gráficos
- [ ] Autenticação de dois fatores (2FA)
- [ ] Backup automático agendado

---

## 🤝 Como Contribuir

1. Faça um **fork** do projeto
2. Crie uma branch para sua feature: `git checkout -b feature/minha-feature`
3. Commit suas mudanças: `git commit -m 'feat: adiciona nova funcionalidade'`
4. Push para a branch: `git push origin feature/minha-feature`
5. Abra um **Pull Request**

---

## 📄 Licença

Este projeto está sob a licença [MIT](LICENSE).

---

## 📞 Suporte

Para dúvidas ou problemas:

1. Verifique a [documentação](./DEPLOY-Estacionamento.md)
2. Consulte os logs dos containers: `docker-compose logs`
3. Abra uma issue no repositório

---

**Desenvolvido com ❤️ para facilitar a gestão de estacionamentos**
