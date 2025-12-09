<?php
require_once __DIR__ . '/Conexao.php';

class Pedido {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getConexao();
    }

    // --- MÉTODOS DE VENDAS (PDV) ---

    public function criar($cliente_id, $itens, $valor_total, $forma_pagamento, $id_sessao) {
        if (empty($itens)) { throw new Exception("Venda sem itens."); }
        if ($id_sessao <= 0) { throw new Exception("Sessão inválida."); }

        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO pedidos (cliente_id, data_pedido, valor_total, forma_pagamento, id_sessao) 
                    VALUES (:cli, NOW(), :val, :pgto, :sessao)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':cli' => $cliente_id,
                ':val' => $valor_total,
                ':pgto' => $forma_pagamento,
                ':sessao' => $id_sessao
            ]);
            $pedidoId = $this->pdo->lastInsertId();

            $sqlItem = "INSERT INTO pedido_items (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
            $stmtItem = $this->pdo->prepare($sqlItem);
            
            $sqlEstoque = "UPDATE produtos SET estoque = estoque - ? WHERE id = ?";
            $stmtEstoque = $this->pdo->prepare($sqlEstoque);

            foreach ($itens as $item) {
                $stmtItem->execute([ $pedidoId, $item['id'], $item['quantidade'], $item['preco'] ]);
                $stmtEstoque->execute([ $item['quantidade'], $item['id'] ]);
            }

            $this->pdo->commit();
            return $pedidoId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // --- MÉTODO PARA O RECIBO (Este era o que faltava!) ---
    public function buscarPedidoComItens($pedidoId) {
        try {
            // 1. Busca os dados principais do pedido + nome do cliente
            $sqlPedido = "SELECT p.id, p.valor_total, p.data_pedido, p.forma_pagamento, c.nome AS cliente_nome
                          FROM pedidos p
                          LEFT JOIN clientes c ON p.cliente_id = c.id
                          WHERE p.id = :id";
            $stmt = $this->pdo->prepare($sqlPedido);
            $stmt->execute([':id' => $pedidoId]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) return null;

            // 2. Busca os itens do pedido
            // Nota: Usa 'pedido_items' (com M), conforme corrigimos antes
            $sqlItens = "SELECT pi.quantidade, pi.preco_unitario, pr.nome AS produto_nome
                         FROM pedido_items pi
                         JOIN produtos pr ON pi.produto_id = pr.id
                         WHERE pi.pedido_id = :id";
            $stmtItens = $this->pdo->prepare($sqlItens);
            $stmtItens->execute([':id' => $pedidoId]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            $pedido['itens'] = $itens;
            return $pedido;

        } catch (PDOException $e) {
            error_log("Erro ao buscar recibo: " . $e->getMessage());
            return null;
        }
    }

    // --- MÉTODOS DO DASHBOARD ---

    public function getFaturamentoHoje() {
        $sql = "SELECT SUM(valor_total) FROM pedidos WHERE DATE(data_pedido) = CURDATE()";
        return (float) $this->pdo->query($sql)->fetchColumn();
    }

    public function getTotalPedidosHoje() {
        $sql = "SELECT COUNT(id) FROM pedidos WHERE DATE(data_pedido) = CURDATE()";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function getUltimasVendas($limite = 5) {
        $sql = "SELECT p.id, c.nome as cliente, p.valor_total, p.data_pedido 
                FROM pedidos p 
                LEFT JOIN clientes c ON p.cliente_id = c.id 
                ORDER BY p.data_pedido DESC LIMIT :limite";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVendasUltimos7Dias() {
        $sql = "SELECT DATE(data_pedido) as data, SUM(valor_total) as total 
                FROM pedidos 
                WHERE data_pedido >= DATE(NOW()) - INTERVAL 7 DAY 
                GROUP BY DATE(data_pedido) 
                ORDER BY data ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodos() {
        $sql = "SELECT p.*, c.nome as cliente_nome 
                FROM pedidos p 
                LEFT JOIN clientes c ON p.cliente_id = c.id 
                ORDER BY p.data_pedido DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>