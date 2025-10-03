<?php require_once __DIR__ . '/../db.php'; require_once __DIR__ . '/../helpers.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Quiosque</title>
    <link rel="stylesheet" href="/php/Quiosque01/public/assets/style.css">
</head>
<body>
<header class="header">
    <div class="header-inner">
        <div class="logo"></div>
        <div class="brand">🍹 Quiosque</div>
    </div>
</header>

<div class="container">
    <main class="grid cols-2">
        <div class="card pad">
            <h3 class="card-title">Atalhos</h3>
            <p class="card-sub">Cadastre clientes e produtos, registre pedidos e acompanhe.</p>
            <div class="row">
                <a class="btn btn-primary" href="/php/Quiosque01/public/cliente.php">Cliente</a>
                <a class="btn btn-primary" href="/php/Quiosque01/public/produto.php">Produto</a>
                <a class="btn btn-primary" href="/php/Quiosque01/public/pdv.php">PDV</a>
                <a class="btn btn-ghost"   href="/php/Quiosque01/public/pedidos.php">Pedidos</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>