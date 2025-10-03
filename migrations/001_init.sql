USE quiosque;

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE,
  telefone VARCHAR(30),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  preco DECIMAL(10,2) NOT NULL,
  estoque INT NOT NULL DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (nome)
);

CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pedidos_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

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