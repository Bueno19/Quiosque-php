<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$db = pdo();
$clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome")->fetchAll();
$produtos = $db->query("SELECT id, nome, preco, estoque FROM produtos ORDER BY nome")->fetchAll();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? '');
    $cliente_id = (int)($_POST['cliente_id'] ?? 0);
    $produto_id = (int)($_POST['produto_id'] ?? 0);
    $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));

    try {
        $db->beginTransaction();
        $st = $db->prepare("SELECT preco, estoque FROM produtos WHERE id = ? FOR UPDATE");
        $st->execute([$produto_id]);
        $produto = $st->fetch();

        if (!$produto) throw new Exception('Produto não encontrado.');
        if ((int)$produto['estoque'] < $quantidade) throw new Exception('Estoque insuficiente.');

        $st = $db->prepare("INSERT INTO pedidos (cliente_id) VALUES (?)");
        $st->execute([$cliente_id]);
        $pedido_id = (int)$db->lastInsertId();

        $st = $db->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario)
                            VALUES (?, ?, ?, ?)");
        $st->execute([$pedido_id, $produto_id, $quantidade, (float)$produto['preco']]);

        $st = $db->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");
        $st->execute([$quantidade, $produto_id]);

        $db->commit();
        $msg = "<span style='color:#34d399'>Pedido #{$pedido_id} registrado!</span>";
    } catch (Throwable $e) {
        $db->rollBack();
        $msg = "<span style='color:#ef4444'>Erro: ".h($e->getMessage())."</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>PDV</title>
    <link rel="stylesheet" href="/php/Quiosque01/public/assets/style.css">
</head>
<body>
<header class="header"><div class="header-inner"><div class="logo"></div><div class="brand">PDV</div></div></header>
<div class="container">
    <div class="card pad">
        <h3 class="card-title">Registrar Pedido</h3>
        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <div class="row">
                <select name="cliente_id" required>
                    <option value="">Selecione um cliente</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= h($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="produto_id" required>
                    <option value="">Selecione um produto</option>
                    <?php foreach ($produtos as $p): ?>
                        <option value="<?= (int)$p['id'] ?>">
                            <?= h($p['nome']) ?> — R$ <?= money_fmt((float)$p['preco']) ?> (Est.: <?= (int)$p['estoque'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="quantidade" min="1" value="1" required>
            </div>
            <button class="btn btn-primary mtop" type="submit">Registrar</button>
        </form>
        <div class="center mtop"><?= $msg ?></div>
        <div class="center mtop"><a class="btn btn-ghost" href="/php/Quiosque01/public/">⬅ Voltar</a></div>
    </div>
</div>
</body>
</html>