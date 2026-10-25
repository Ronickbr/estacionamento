# Guia de Implantação — Sistema de Estacionamento

**Produto:** ParkManager  
**Repositório:** https://github.com/Ronickbr/estacionamento  
**Branch:** `main`  

---

## 1. Pré-requisitos

- Docker
- Docker Compose
- Git (opcional)

---

## 2. Instalação

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

## 3. Credenciais Padrão

### Sistema (web)
| Campo | Valor |
|-------|-------|
| Usuário (e-mail seed) | `admin@estacionamento.com` |
| Senha | `admin123` (conforme README) |

> Atenção: o seed SQL insere um administrador com e-mail `admin@estacionamento.com` e senha hash bcrypt. O README menciona login `admin` / `admin123`. Verifique qual credencial funciona após a primeira inicialização.

### Banco de dados (direto / phpMyAdmin)
| Campo | Valor |
|-------|-------|
| Host | `localhost:3306` |
| Usuário root | `root` |
| Senha root | `root123` |
| Banco | `estacionamento` |
| Usuário app | `estacionamento_user` |
| Senha app | `userpassword` (não utilizada pelo PHP atualmente) |

### phpMyAdmin
| Campo | Valor |
|-------|-------|
| Usuário | `estacionamento_user` |
| Senha | `userpassword` |
| URL | `http://localhost:8081` |

---

## 4. Portas Utilizadas

| Serviço | Porta externa | Porta interna | Finalidade |
|---------|---------------|---------------|------------|
| Sistema web | 8080 | 80 | Acesso ao ParkManager |
| phpMyAdmin | 8081 | 80 | Administração do MySQL |
| MySQL | 3306 | 3306 | Banco de dados (acesso direto) |

Para alterar, edite as seções `ports` no `docker-compose.yml`.

---

## 5. Variáveis de Ambiente (docker-compose.yml)

Edite o arquivo `docker-compose.yml` para personalizar:

```yaml
environment:
  MYSQL_DATABASE: estacionamento_db
  MYSQL_USER: estacionamento_user
  MYSQL_PASSWORD: estacionamento_pass
  MYSQL_ROOT_PASSWORD: root_password
```

O serviço `web` também lê `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` via `$_ENV` na classe `src/config/Database.php`.

> Recomendação: migrar essas variáveis para um arquivo `.env` e não versionar senhas.

---

## 6. Backup e Restauração do Banco

```bash
# Criar backup
docker exec estacionamento-mysql mysqldump -u estacionamento_user -p estacionamento_db > backup.sql

# Restaurar backup
docker exec -i estacionamento-mysql mysql -u estacionamento_user -p estacionamento_db < backup.sql
```

---

## 7. Solução de Problemas Comuns

| Problema | Comando / Ação |
|----------|----------------|
| Container não inicia | `docker-compose logs` para ver os logs; `docker-compose down && docker-compose up -d` para reiniciar. |
| Erro de conexão com banco | Aguarde alguns minutos para o MySQL inicializar; verifique `docker-compose logs mysql`. |
| Permissão de arquivos (Linux/Mac) | `sudo chown -R www-data:www-data src/` e `sudo chmod -R 755 src/`. |
| phpMyAdmin não conecta | Verifique se o serviço `db` está saudável antes de acessar o `phpmyadmin`. |

---

## 8. Verificação Pós-Instalação

1. Acesse `http://localhost:8080` e faça login.
2. Cadastre/confirme seu primeiro estacionamento.
3. Cadastre vagas (rotativas e mensais).
4. Teste uma entrada e saída de veículo rotativo.
5. Cadastre um mensalista e verifique a geração da cobrança.
