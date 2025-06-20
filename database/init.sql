-- Criação do banco de dados para o sistema de estacionamento
CREATE DATABASE IF NOT EXISTS estacionamento;
USE estacionamento;

-- Tabela de usuários do sistema
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'operador') DEFAULT 'operador',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de estacionamentos
CREATE TABLE estacionamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    endereco TEXT,
    total_vagas INT NOT NULL,
    vagas_ocupadas INT DEFAULT 0,
    valor_hora DECIMAL(10,2) NOT NULL,
    valor_fracao DECIMAL(10,2),
    tempo_fracao INT DEFAULT 15, -- em minutos
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de vagas
CREATE TABLE vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estacionamento_id INT NOT NULL,
    numero VARCHAR(10) NOT NULL,
    tipo ENUM('rotativa', 'mensal', 'ambas') DEFAULT 'rotativa',
    ocupada BOOLEAN DEFAULT FALSE,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estacionamento_id) REFERENCES estacionamentos(id)
);

-- Tabela de clientes mensalistas
CREATE TABLE mensalistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    endereco TEXT,
    placa_veiculo VARCHAR(10) NOT NULL,
    modelo_veiculo VARCHAR(50),
    cor_veiculo VARCHAR(30),
    estacionamento_id INT NOT NULL,
    vaga_fixa_id INT NULL,
    valor_mensal DECIMAL(10,2) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estacionamento_id) REFERENCES estacionamentos(id),
    FOREIGN KEY (vaga_fixa_id) REFERENCES vagas(id)
);

-- Tabela de movimentações rotativas
CREATE TABLE movimentacoes_rotativas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estacionamento_id INT NOT NULL,
    vaga_id INT NOT NULL,
    placa_veiculo VARCHAR(10) NOT NULL,
    modelo_veiculo VARCHAR(50),
    cor_veiculo VARCHAR(30),
    data_entrada DATETIME NOT NULL,
    data_saida DATETIME NULL,
    tempo_permanencia INT NULL, -- em minutos
    valor_calculado DECIMAL(10,2) NULL,
    valor_pago DECIMAL(10,2) NULL,
    forma_pagamento ENUM('dinheiro', 'cartao_credito', 'cartao_debito', 'pix') NULL,
    status ENUM('ativo', 'finalizado', 'cancelado') DEFAULT 'ativo',
    observacoes TEXT,
    usuario_entrada_id INT,
    usuario_saida_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estacionamento_id) REFERENCES estacionamentos(id),
    FOREIGN KEY (vaga_id) REFERENCES vagas(id),
    FOREIGN KEY (usuario_entrada_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_saida_id) REFERENCES usuarios(id)
);

-- Tabela de cobranças mensais
CREATE TABLE cobrancas_mensais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensalista_id INT NOT NULL,
    mes_referencia DATE NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE NULL,
    forma_pagamento ENUM('dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'transferencia') NULL,
    status ENUM('pendente', 'pago', 'vencido', 'cancelado') DEFAULT 'pendente',
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mensalista_id) REFERENCES mensalistas(id)
);

-- Tabela de acessos de mensalistas
CREATE TABLE acessos_mensalistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensalista_id INT NOT NULL,
    data_acesso DATETIME NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    vaga_id INT NULL,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mensalista_id) REFERENCES mensalistas(id),
    FOREIGN KEY (vaga_id) REFERENCES vagas(id)
);

-- Tabela de configurações do sistema
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT,
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir dados iniciais
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@estacionamento.com', '$2a$12$CdR9..xuooIPCglAIj74jOWTT7alszKbcy/zBjGBk4.Uqi0j1Ssd6', 'admin');

INSERT INTO estacionamentos (nome, endereco, total_vagas, valor_hora, valor_fracao) VALUES 
('Estacionamento Central', 'Rua Principal, 123 - Centro', 50, 5.00, 2.50);

INSERT INTO vagas (estacionamento_id, numero, tipo) VALUES 
(1, 'A01', 'rotativa'),
(1, 'A02', 'rotativa'),
(1, 'A03', 'rotativa'),
(1, 'A04', 'rotativa'),
(1, 'A05', 'rotativa'),
(1, 'M01', 'mensal'),
(1, 'M02', 'mensal'),
(1, 'M03', 'mensal'),
(1, 'M04', 'mensal'),
(1, 'M05', 'mensal');

INSERT INTO configuracoes (chave, valor, descricao) VALUES 
('sistema_nome', 'ParkManager', 'Nome do sistema'),
('tolerancia_minutos', '10', 'Tolerância em minutos para cobrança'),
('backup_automatico', '1', 'Ativar backup automático'),
('notificacao_email', '1', 'Ativar notificações por email');

-- Criar índices para melhor performance
CREATE INDEX idx_movimentacoes_placa ON movimentacoes_rotativas(placa_veiculo);
CREATE INDEX idx_movimentacoes_data ON movimentacoes_rotativas(data_entrada);
CREATE INDEX idx_mensalistas_cpf ON mensalistas(cpf);
CREATE INDEX idx_mensalistas_placa ON mensalistas(placa_veiculo);
CREATE INDEX idx_cobrancas_status ON cobrancas_mensais(status);
CREATE INDEX idx_cobrancas_vencimento ON cobrancas_mensais(data_vencimento);