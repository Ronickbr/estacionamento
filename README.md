# Sistema de Estacionamento - Micro SaaS

Sistema completo de gerenciamento de estacionamento desenvolvido em PHP, Bootstrap e MySQL, containerizado com Docker.

## 📋 Funcionalidades

### Estacionamento Rotativo
- ✅ Registro de entrada e saída de veículos
- ✅ Cálculo automático de tarifas por tempo
- ✅ Controle de vagas disponíveis
- ✅ Busca por placa de veículo
- ✅ Relatórios de movimentação

### Mensalistas
- ✅ Cadastro de clientes mensais
- ✅ Controle de acesso
- ✅ Cobrança recorrente
- ✅ Gestão de contratos
- ✅ Vagas dedicadas (opcional)
- ✅ Relatórios financeiros

### Gerenciamento
- ✅ Múltiplos estacionamentos
- ✅ Configuração de vagas por tipo
- ✅ Dashboard com estatísticas
- ✅ Relatórios detalhados
- ✅ Sistema de autenticação

## 🚀 Tecnologias Utilizadas

- **Backend**: PHP 8.1 com Apache
- **Frontend**: Bootstrap 5.1.3 + JavaScript
- **Banco de Dados**: MySQL 8.0
- **Containerização**: Docker + Docker Compose
- **Gerenciamento**: phpMyAdmin

## 📦 Instalação

### Pré-requisitos
- Docker
- Docker Compose
- Git (opcional)

### Passos para Instalação

1. **Clone ou baixe o projeto**
   ```bash
   git clone <url-do-repositorio>
   cd Estacionamento
   ```

2. **Inicie os containers**
   ```bash
   docker-compose up -d
   ```

3. **Aguarde a inicialização**
   - O MySQL pode levar alguns minutos para inicializar completamente
   - O banco de dados será criado automaticamente com dados iniciais

4. **Acesse o sistema**
   - **Sistema Principal**: http://localhost:8080
   - **phpMyAdmin**: http://localhost:8081

### Credenciais Padrão

**Sistema:**
- Usuário: `admin`
- Senha: `admin123`

**Banco de Dados:**
- Host: `localhost:3306`
- Usuário: `estacionamento_user`
- Senha: `estacionamento_pass`
- Database: `estacionamento_db`

**phpMyAdmin:**
- Usuário: `estacionamento_user`
- Senha: `estacionamento_pass`

## 🏗️ Estrutura do Projeto

```
Estacionamento/
├── docker-compose.yml          # Configuração dos containers
├── Dockerfile                  # Imagem PHP personalizada
├── apache/
│   └── 000-default.conf       # Configuração Apache
├── mysql/
│   └── init.sql               # Script de inicialização do banco
└── src/
    ├── classes/               # Classes PHP do sistema
    │   ├── Auth.php          # Autenticação
    │   ├── Database.php      # Conexão com banco
    │   ├── Estacionamento.php # Gestão de estacionamentos
    │   ├── Mensalista.php    # Gestão de mensalistas
    │   └── MovimentacaoRotativa.php # Movimentação rotativa
    └── public/               # Arquivos públicos
        ├── index.php         # Página de login
        ├── dashboard.php     # Dashboard principal
        ├── movimentacao.php  # Gestão de movimentação
        ├── mensalistas.php   # Gestão de mensalistas
        ├── estacionamentos.php # Gestão de estacionamentos
        ├── relatorios.php    # Relatórios
        ├── logout.php        # Logout
        └── .htaccess         # Configurações Apache
```

## 🎯 Como Usar

### 1. Primeiro Acesso
1. Acesse http://localhost:8080
2. Faça login com as credenciais padrão
3. Configure seu primeiro estacionamento
4. Cadastre as vagas disponíveis

### 2. Configuração Inicial
1. **Estacionamentos**: Cadastre seus pátios de estacionamento
2. **Vagas**: Configure as vagas por tipo (comum, coberta, preferencial)
3. **Tarifas**: Defina valores por hora e diária
4. **Usuários**: Crie usuários adicionais se necessário

### 3. Operação Diária
1. **Entrada de Veículos**: Registre a entrada informando placa e vaga
2. **Saída de Veículos**: Registre a saída e calcule o valor
3. **Mensalistas**: Gerencie contratos e acessos
4. **Relatórios**: Acompanhe receitas e ocupação

## 📊 Funcionalidades Detalhadas

### Dashboard
- Estatísticas em tempo real
- Vagas ocupadas vs disponíveis
- Receita do dia/mês
- Movimentações ativas
- Cobranças pendentes

### Movimentação Rotativa
- Registro de entrada com placa e vaga
- Cálculo automático de tempo e valor
- Busca rápida por placa
- Controle de status das vagas
- Histórico de movimentações

### Mensalistas
- Cadastro completo de clientes
- Controle de período de contrato
- Vagas dedicadas opcionais
- Registro de acessos
- Cobrança automática mensal
- Controle de inadimplência

### Estacionamentos
- Múltiplos pátios
- Configuração de capacidade
- Mapa visual de vagas
- Tipos de vaga personalizáveis
- Horários de funcionamento

### Relatórios
- Movimentação por período
- Receita detalhada
- Ocupação por estacionamento
- Relatório financeiro consolidado
- Exportação para impressão

## 🔧 Configurações Avançadas

### Variáveis de Ambiente
Edite o arquivo `docker-compose.yml` para personalizar:

```yaml
environment:
  MYSQL_DATABASE: estacionamento_db
  MYSQL_USER: estacionamento_user
  MYSQL_PASSWORD: estacionamento_pass
  MYSQL_ROOT_PASSWORD: root_password
```

### Portas
Para alterar as portas de acesso, modifique no `docker-compose.yml`:

```yaml
ports:
  - "8080:80"  # Sistema principal
  - "8081:80"  # phpMyAdmin
  - "3306:3306" # MySQL
```

### Backup do Banco
```bash
# Criar backup
docker exec estacionamento-mysql mysqldump -u estacionamento_user -p estacionamento_db > backup.sql

# Restaurar backup
docker exec -i estacionamento-mysql mysql -u estacionamento_user -p estacionamento_db < backup.sql
```

## 🐛 Solução de Problemas

### Container não inicia
```bash
# Verificar logs
docker-compose logs

# Reiniciar containers
docker-compose down
docker-compose up -d
```

### Erro de conexão com banco
1. Aguarde alguns minutos para o MySQL inicializar
2. Verifique se as credenciais estão corretas
3. Verifique os logs: `docker-compose logs mysql`

### Permissões de arquivo
```bash
# No Linux/Mac, ajustar permissões
sudo chown -R www-data:www-data src/
sudo chmod -R 755 src/
```

## 📈 Melhorias Futuras

- [ ] API REST para integração
- [ ] App mobile
- [ ] Integração com sistemas de pagamento
- [ ] Reconhecimento de placas (OCR)
- [ ] Notificações por email/SMS
- [ ] Multi-tenancy
- [ ] Relatórios avançados com gráficos

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo LICENSE para mais detalhes.

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique a documentação
2. Consulte os logs dos containers
3. Abra uma issue no repositório

---

**Desenvolvido com ❤️ para facilitar a gestão de estacionamentos**