<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$db = pdo();
$sql = "SELECT p.id AS pedido_id, p.criado_em,
               c.nome AS cliente,
               pr.nome AS produto,
               pi.quantidade, pi.preco_unitario
        FROM pedidos p
        JOIN clientes c      ON c.id = p.cliente_id
        JOIN pedido_itens pi ON pi.pedido_id = p.id
        JOIN produtos pr     ON pr.id = pi.produto_id
        ORDER BY p.id DESC, pi.id ASC";
$rows = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pedidos</title>
    <link rel="stylesheet" href="/php/Quiosque01/public/assets/style.css">
</head>
<body>
<header class="header"><div class="header-inner"><div class="logo"></div><div class="brand">Pedidos</div></div></header>
<div class="container">
    <div class="card pad">
        <h3 class="card-title">Pedidos Registrados</h3>
        <table class="table">
            <thead>
            <tr>
                <th>#</th><th>Data/Hora</th><th>Cliente</th><th>Produto</th>
                <th>Qtd</th><th>Preço Unit.</th><th>Total Item</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $pedidoAtual = null; $subtotal = 0; $tem = false;
            foreach ($rows as $r):
                $tem = true;
                if ($pedidoAtual !== null && $pedidoAtual != $r['pedido_id']) {
                    echo "<tr class='subtotal'><td colspan='6' style='text-align:right'>Subtotal do Pedido {$pedidoAtual}:</td><td>R$ ".money_fmt($subtotal)."</td></tr>";
                    $subtotal = 0;
                }
                $pedidoAtual = (int)$r['pedido_id'];
                $totalItem = (int)$r['quantidade'] * (float)$r['preco_unitario'];
                $subtotal += $totalItem;
                ?>
                <tr>
                    <td><?= (int)$r['pedido_id'] ?></td>
                    <td><?= h($r['criado_em']) ?></td>
                    <td><?= h($r['cliente']) ?></td>
                    <td><?= h($r['produto']) ?></td>
                    <td><?= (int)$r['quantidade'] ?></td>
                    <td>R$ <?= money_fmt((float)$r['preco_unitario']) ?></td>
                    <td>R$ <?= money_fmt($totalItem) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($tem): ?>
                <tr class="subtotal">
                    <td colspan="6" style="text-align:right">Subtotal do Pedido <?= $pedidoAtual ?>:</td>
                    <td>R$ <?= money_fmt($subtotal) ?></td>
                </tr>
            <?php else: ?>
                <tr><td colspan="7">Nenhum pedido encontrado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <div class="center mtop"><a class="btn btn-ghost" href="/php/Quiosque01/public/">⬅ Voltar</a></div>
    </div>
</div>
</body>
</html>