USE quiosque;

-- Tabela clientes
CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE,
  telefone VARCHAR(30),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela produtos
CREATE TABLE IF NOT EXISTS produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  preco DECIMAL(10,2) NOT NULL,
  estoque INT NOT NULL DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (nome)
);

-- Tabela pedidos
CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pedidos_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Tabela pedido_itens
CREATE TABLE IF NOT EXISTS pedido_itens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_itens_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_itens_produto
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Dados exemplo
INSERT INTO clientes (nome, email, telefone) VALUES
('Cliente Demo', 'demo@exemplo.com', '(38) 99999-0001')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

INSERT INTO produtos (nome, preco, estoque) VALUES
('Água de Coco', 8.90, 50),
('Suco Detox', 12.50, 30),
('Sanduíche Natural', 16.00, 20)
ON DUPLICATE KEY UPDATE preco = VALUES(preco), estoque = VALUES(estoque);

-- ===== Atualização da Tabela de Produtos =====

ALTER TABLE produtos
ADD COLUMN descricao TEXT NULL AFTER estoque,
ADD COLUMN categoria VARCHAR(100) NULL AFTER descricao,
ADD COLUMN imagem VARCHAR(255) NULL AFTER categoria,
ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER imagem,
ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER criado_em;

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL,
    descricao TEXT NULL,
    categoria VARCHAR(50) NULL,
    marca VARCHAR(50) NULL,
    codigo_barras VARCHAR(50) NULL,
    ativo TINYINT(1) DEFAULT 1,
    imagem VARCHAR(255) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

