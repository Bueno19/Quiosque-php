<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';

$mesaId = (int)($_GET['id'] ?? 0);
if ($mesaId === 0) {
    die("ID da mesa não fornecido.");
}

$configModel = new Configuracao();
$configuracoes = $configModel->carregarConfiguracoes();

$mesaModel = new Mesa();
$mesa = $mesaModel->buscar($mesaId);
$itens = $mesaModel->buscarItensDaMesa($mesaId);

if (!$mesa) {
    die("Mesa não encontrada.");
}

$subtotal = 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Extrato da Mesa <?php echo $mesa['numero']; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; width: 300px; margin: 0 auto; padding: 10px; }
        h1, h2 { text-align: center; margin: 5px 0; }
        h1 { font-size: 1.2em; }
        h2 { font-size: 1em; font-weight: normal; }
        hr { border: none; border-top: 1px dashed #000; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; }
        .col-qtd { width: 15%; text-align: center; }
        .col-preco, .col-total { width: 25%; text-align: right; }
        .total-geral { font-weight: bold; font-size: 1.1em; }
        .footer { text-align: center; margin-top: 10px; font-size: 0.9em; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($configuracoes['impressao_cabecalho'] ?? 'Extrato de Consumo'); ?></h1>
    <h2><?php echo date('d/m/Y H:i:s'); ?></h2>
    <hr>
    <h2>Mesa: <?php echo $mesa['numero']; ?></h2>
    <?php if (!empty($mesa['descricao'])): ?>
        <h2>Cliente: <?php echo htmlspecialchars($mesa['descricao']); ?></h2>
    <?php endif; ?>
    <hr>
    <table>
        <thead>
            <tr>
                <th class="col-qtd">Qtd</th>
                <th>Produto</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $item): 
                $totalItem = $item['preco'] * $item['quantidade'];
                $subtotal += $totalItem;
            ?>
                <tr>
                    <td class="col-qtd"><?php echo $item['quantidade']; ?></td>
                    <td><?php echo htmlspecialchars($item['nome']); ?></td>
                    <td class="col-total"><?php echo number_format($totalItem, 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <hr>
    <table>
        <tr class="total-geral">
            <td>Subtotal:</td>
            <td class="col-total"><?php echo number_format($subtotal, 2, ',', '.'); ?></td>
        </tr>
    </table>
    <hr>
    <div class="footer">
        <?php echo htmlspecialchars($configuracoes['impressao_rodape'] ?? 'Obrigado!'); ?>
    </div>
    <script>
        // Imprime automaticamente ao abrir.
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>