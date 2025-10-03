<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if ($nome === '') {
        $msg = "<span style='color:#f59e0b'>Informe o nome.</span>";
    } else {
        $db = pdo();
        $stmt = $db->prepare("INSERT INTO clientes (nome, email, telefone) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$nome, $email ?: null, $telefone ?: null]);
            $msg = "<span style='color:#34d399'>Cliente cadastrado!</span>";
        } catch (PDOException $e) {
            $msg = "<span style='color:#ef4444'>Erro: ".h($e->getMessage())."</span>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Clientes</title>
    <link rel="stylesheet" href="/php/Quiosque01/public/assets/style.css">
</head>
<body>
<header class="header"><div class="header-inner"><div class="logo"></div><div class="brand">Cadastro de Clientes</div></div></header>
<div class="container">
    <div class="card pad">
        <h3 class="card-title">Novo cliente</h3>
        <form method="post" class="stack">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <input type="text" name="nome" placeholder="Nome completo" required>
            <div class="row">
                <input type="email" name="email" placeholder="E-mail (opcional)">
                <input type="text" name="telefone" placeholder="Telefone (opcional)">
            </div>
            <button class="btn btn-ok mtop" type="submit">Cadastrar</button>
        </form>
        <div class="center mtop"><?= $msg ?></div>
        <div class="center mtop"><a class="btn btn-ghost" href="/php/Quiosque01/public/">⬅ Voltar</a></div>
    </div>
</div>
</body>
</html>