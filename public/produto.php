<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $preco = br_money_to_float($_POST['preco'] ?? '0');
    $estoque = max(0, (int)($_POST['estoque'] ?? 0));

    if ($nome !== '' && $preco > 0) {
        $db = pdo();
        $stmt = $db->prepare("INSERT INTO produtos (nome, preco, estoque) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $preco, $estoque]);
        $msg = "<span style='color:#34d399'>Produto cadastrado!</span>";
    } else {
        $msg = "<span style='color:#f59e0b'>Preencha nome e preço válido.</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Produtos</title>
    <link rel="stylesheet" href="/php/Quiosque01/public/assets/style.css">
</head>
<body>
<header class="header"><div class="header-inner"><div class="logo"></div><div class="brand">Cadastro de Produtos</div></div></header>
<div class="container">
    <div class="card pad">
        <h3 class="card-title">Novo produto</h3>
        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <input type="text" name="nome" placeholder="Nome do produto" required>
            <div class="row">
                <input type="text" name="preco" placeholder="Preço (ex: 12,50)" required>
                <input type="number" name="estoque" min="0" value="0" placeholder="Estoque inicial" required>
            </div>
            <button class="btn btn-ok mtop" type="submit">Cadastrar</button>
        </form>
        <div class="center mtop"><?= $msg ?></div>
        <div class="center mtop"><a class="btn btn-ghost" href="/php/Quiosque01/public/">⬅ Voltar</a></div>
    </div>
</div>
</body>
</html>