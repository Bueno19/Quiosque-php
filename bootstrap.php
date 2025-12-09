<?php
// bootstrap.php

// --- INICIALIZAÇÃO DA APLICAÇÃO ---
ob_start();
session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. CONFIGURAÇÃO DE AMBIENTE E ERROS
error_reporting(E_ALL);
ini_set('display_errors', 1); // Em produção, alterar para 0

// 2. DEFINIÇÃO DE CAMINHOS
define('ROOT_PATH', __DIR__);

// 3. CONFIGURAÇÃO DO BANCO DE DADOS
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quiosque');

// 4. CONFIGURAÇÃO DE IDIOMA E FUSO HORÁRIO
mb_internal_encoding('UTF-8');
date_default_timezone_set('America/Sao_Paulo');

// 5. AUTOLOADER DE CLASSES
spl_autoload_register(function ($className) {
    $file = ROOT_PATH . '/models/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 6. CONEXÃO INICIAL E CRIAÇÃO/VERIFICAÇÃO DA ESTRUTURA DO BANCO
try {
    // Conecta sem selecionar DB para poder criar
    $pdoTemp = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdoTemp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdoTemp->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdoTemp = null; 

    // Conecta ao banco de dados específico
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // --- CRIAÇÃO DAS TABELAS (IF NOT EXISTS) ---

    // Clientes, Produtos, Utilizadores, Permissoes, Utilizador_Permissoes, Pedidos, Pedido_Items, Configuracoes (inalterados)
    $sqlClientes = "CREATE TABLE IF NOT EXISTS `clientes` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `nome` VARCHAR(255) NOT NULL, `cpf` VARCHAR(20), `email` VARCHAR(255) UNIQUE, `telefone` VARCHAR(30), `logradouro` VARCHAR(255), `cep` VARCHAR(10), `numero` VARCHAR(20), `bairro` VARCHAR(100), `cidade` VARCHAR(100), `uf` VARCHAR(2), `ativo` TINYINT(1) NOT NULL DEFAULT 1, `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlClientes);
    $sqlProdutos = "CREATE TABLE IF NOT EXISTS `produtos` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `nome` VARCHAR(255) NOT NULL, `descricao` TEXT, `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00, `estoque` INT NOT NULL DEFAULT 0, `ativo` TINYINT(1) NOT NULL DEFAULT 1, `imagem` VARCHAR(255), `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlProdutos);
    $sqlUtilizadores = "CREATE TABLE IF NOT EXISTS `utilizadores` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `nome` VARCHAR(100) NOT NULL, `login` VARCHAR(50) NOT NULL UNIQUE, `senha_hash` VARCHAR(255) NOT NULL, `cargo` VARCHAR(50) NULL, `ativo` BOOLEAN NOT NULL DEFAULT TRUE, `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlUtilizadores);
    $sqlPermissoes = "CREATE TABLE IF NOT EXISTS `permissoes` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `nome_permissao` VARCHAR(50) NOT NULL UNIQUE, `descricao` VARCHAR(255) NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlPermissoes);
    $sqlUserPerm = "CREATE TABLE IF NOT EXISTS `utilizador_permissoes` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `utilizador_id` INT NOT NULL, `permissao_id` INT NOT NULL, FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores`(`id`) ON DELETE CASCADE, FOREIGN KEY (`permissao_id`) REFERENCES `permissoes`(`id`) ON DELETE CASCADE, UNIQUE KEY (`utilizador_id`, `permissao_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlUserPerm);
    $sqlPedidos = "CREATE TABLE IF NOT EXISTS `pedidos` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `cliente_id` INT, `data_pedido` DATETIME DEFAULT CURRENT_TIMESTAMP, `valor_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00, `forma_pagamento` VARCHAR(50) DEFAULT 'Não definido', FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlPedidos);
    $sqlPedidoItems = "CREATE TABLE IF NOT EXISTS `pedido_items` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `pedido_id` INT, `produto_id` INT, `quantidade` INT NOT NULL DEFAULT 1, `preco_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00, FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE, FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlPedidoItems);
    $sqlConfiguracoes = "CREATE TABLE IF NOT EXISTS `configuracoes` ( `chave` VARCHAR(50) NOT NULL PRIMARY KEY, `valor` TEXT ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlConfiguracoes);

    // Fornecedores (inalterado)
    $sqlFornecedores = "CREATE TABLE IF NOT EXISTS `fornecedores` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `nome` VARCHAR(255) NOT NULL, `cpf_cnpj` VARCHAR(20) NULL, `telefone` VARCHAR(30) NULL, `email` VARCHAR(255) NULL, `endereco` VARCHAR(255) NULL, `ativo` TINYINT(1) NOT NULL DEFAULT 1, `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlFornecedores);

    // Caixa_Sessoes (inalterado)
    $sqlCaixaSessoes = "CREATE TABLE IF NOT EXISTS `caixa_sessoes` ( `id` INT NOT NULL AUTO_INCREMENT, `id_utilizador_abertura` INT NOT NULL, `data_abertura` DATETIME NOT NULL, `valor_abertura` DECIMAL(10,2) NOT NULL, `id_utilizador_fechamento` INT NULL, `data_fechamento` DATETIME NULL, `total_apurado_sistema` DECIMAL(10,2) NULL, `total_contado_dinheiro` DECIMAL(10,2) NULL, `total_contado_cartao` DECIMAL(10,2) NULL, `total_contado_outros` DECIMAL(10,2) NULL, `diferenca` DECIMAL(10,2) NULL, `observacoes` TEXT NULL, `status` ENUM('ABERTO', 'FECHADO') NOT NULL DEFAULT 'ABERTO', PRIMARY KEY (`id`), FOREIGN KEY (`id_utilizador_abertura`) REFERENCES `utilizadores`(`id`) ON DELETE RESTRICT, FOREIGN KEY (`id_utilizador_fechamento`) REFERENCES `utilizadores`(`id`) ON DELETE RESTRICT ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlCaixaSessoes);
    
    // Caixa_Movimentacoes (inalterado)
    $sqlCaixaMovimentacoes = "CREATE TABLE IF NOT EXISTS `caixa_movimentacoes` ( `id` INT NOT NULL AUTO_INCREMENT, `id_sessao` INT NOT NULL COMMENT 'Link para a sessão de caixa ativa', `id_utilizador` INT NOT NULL COMMENT 'Quem registou a movimentação', `tipo` ENUM('SUPRIMENTO', 'SANGRIA') NOT NULL COMMENT 'Tipo de movimentação', `valor` DECIMAL(10,2) NOT NULL COMMENT 'Valor da movimentação (sempre positivo)', `motivo` VARCHAR(255) NULL COMMENT 'Descrição/Justificativa (Opcional)', `data_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), FOREIGN KEY (`id_sessao`) REFERENCES `caixa_sessoes`(`id`) ON DELETE CASCADE, FOREIGN KEY (`id_utilizador`) REFERENCES `utilizadores`(`id`) ON DELETE RESTRICT ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlCaixaMovimentacoes);

    // --- Mesas e Mesa_Items (COM LÓGICA ATUALIZADA) ---
    // A: Garante que a coluna 'ativa' existe
    try {
        $stmtCheckColAtiva = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = 'mesas' AND COLUMN_NAME = 'ativa'");
        $stmtCheckColAtiva->execute(['db_name' => DB_NAME]);
        if ($stmtCheckColAtiva->fetchColumn() == 0) {
            $pdo->exec("ALTER TABLE `mesas` ADD COLUMN `ativa` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Visível/Ativa, 0 = Oculta/Inativa' AFTER `descricao`");
        }
    } catch (PDOException $eAlter) {
        // Ignora erro se a alteração falhar (ex: coluna já existe com outro nome/tipo - raro)
        error_log("Aviso ao tentar adicionar coluna 'ativa' em mesas: " . $eAlter->getMessage());
    }

    // B: Recria a tabela incluindo 'ativa' se ela não existir 
    $sqlMesas = "CREATE TABLE IF NOT EXISTS `mesas` ( 
        `id` INT AUTO_INCREMENT PRIMARY KEY, 
        `numero` INT NOT NULL UNIQUE, 
        `status` ENUM('livre', 'ocupada', 'em_fechamento', 'reatendimento') NOT NULL DEFAULT 'livre', 
        `descricao` VARCHAR(100) DEFAULT NULL,
        `ativa` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Visível/Ativa, 0 = Oculta/Inativa' 
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlMesas);
    // Adiciona índice (ignora erro se já existir)
    try { $pdo->exec("ALTER TABLE `mesas` ADD INDEX `idx_ativa` (`ativa`)"); } catch (PDOException $eIndex) { /* Ignora */ }

    // C: Tabela Mesa_Items (sem alteração)
    $sqlMesaItems = "CREATE TABLE IF NOT EXISTS `mesa_items` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `mesa_id` INT, `produto_id` INT, `quantidade` INT NOT NULL DEFAULT 1, `adicionado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (`mesa_id`) REFERENCES `mesas`(`id`) ON DELETE CASCADE, FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $pdo->exec($sqlMesaItems);


    // --- ALTERAÇÕES SEGURAS EM TABELAS EXISTENTES (CONTINUAÇÃO) ---
    // (Produtos, Pedidos, Caixa_Sessoes - inalterados)
    $stmtCheckCol = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'fornecedor_id'");
    $stmtCheckCol->execute(['db_name' => DB_NAME]); if ($stmtCheckCol->fetchColumn() == 0) { $pdo->exec("ALTER TABLE `produtos` ADD COLUMN `fornecedor_id` INT NULL DEFAULT NULL AFTER `imagem`"); }
    $stmtCheckFk = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = :db_name AND TABLE_NAME = 'produtos' AND CONSTRAINT_NAME = 'fk_produto_fornecedor'");
    $stmtCheckFk->execute(['db_name' => DB_NAME]); if ($stmtCheckFk->fetchColumn() == 0) { $pdo->exec("ALTER TABLE `produtos` ADD CONSTRAINT `fk_produto_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores`(`id`) ON DELETE SET NULL ON UPDATE CASCADE"); }
    $stmtCheckColSessao = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'id_sessao'");
    $stmtCheckColSessao->execute(['db_name' => DB_NAME]); if ($stmtCheckColSessao->fetchColumn() == 0) { $pdo->exec("ALTER TABLE `pedidos` ADD COLUMN `id_sessao` INT NULL DEFAULT NULL"); }
    $stmtCheckFkSessao = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = :db_name AND TABLE_NAME = 'pedidos' AND CONSTRAINT_NAME = 'fk_pedido_sessao'");
    $stmtCheckFkSessao->execute(['db_name' => DB_NAME]); if ($stmtCheckFkSessao->fetchColumn() == 0) { $pdo->exec("ALTER TABLE `pedidos` ADD CONSTRAINT `fk_pedido_sessao` FOREIGN KEY (`id_sessao`) REFERENCES `caixa_sessoes`(`id`) ON DELETE SET NULL ON UPDATE CASCADE"); }
    $stmtCheckColSup = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = 'caixa_sessoes' AND COLUMN_NAME = 'total_suprimentos'");
    $stmtCheckColSup->execute(['db_name' => DB_NAME]); if ($stmtCheckColSup->fetchColumn() == 0) { $pdo->exec("ALTER TABLE `caixa_sessoes` ADD COLUMN `total_suprimentos` DECIMAL(10,2) NULL DEFAULT 0.00 AFTER `valor_abertura`"); }
    $stmtCheckColSan = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = 'caixa_sessoes' AND COLUMN_NAME = 'total_sangrias'");
    $stmtCheckColSan->execute(['db_name' => DB_NAME]); if ($stmtCheckColSan->fetchColumn() == 0) { $pdo->exec("ALTER TABLE `caixa_sessoes` ADD COLUMN `total_sangrias` DECIMAL(10,2) NULL DEFAULT 0.00 AFTER `total_suprimentos`"); }

    // --- INSERÇÃO DE DADOS PADRÃO (com verificações/IGNORE) ---
    // (Configuracoes, Permissoes, Admin, Permissoes Admin, Cliente Padrão - inalterados)
    $configuracoesPadrao = [ 'nome_sistema' => 'Sistema Quiosque', 'moeda_simbolo' => 'R$', 'impressora_nome' => '', 'impressao_cabecalho' => 'Recibo de Venda', 'impressao_rodape' => 'Obrigado e volte sempre!', 'taxa_servico_padrao' => '0', 'cliente_padrao_pdv' => '1', 'numero_mesas' => '10', 'alerta_estoque_baixo' => '5', 'permitir_venda_sem_estoque' => 'nao' ];
    $sqlInsertConfig = "INSERT IGNORE INTO `configuracoes` (chave, valor) VALUES (:chave, :valor)"; $stmtConfig = $pdo->prepare($sqlInsertConfig); foreach ($configuracoesPadrao as $chave => $valor) { $stmtConfig->execute(['chave' => $chave, 'valor' => $valor]); }
    $permissoesPadrao = [ ['acessar_pdv', '...'], ['acessar_dashboard', '...'], ['gerenciar_mesas', '...'], ['visualizar_pedidos', '...'], ['gerenciar_produtos', '...'], ['gerenciar_clientes', '...'], ['visualizar_relatorios', '...'], ['gerenciar_utilizadores', '...'], ['acessar_configuracoes', '...'] ];
    $sqlInsertPerm = "INSERT IGNORE INTO `permissoes` (nome_permissao, descricao) VALUES (:nome, :desc)"; $stmtPerm = $pdo->prepare($sqlInsertPerm); foreach ($permissoesPadrao as $perm) { if (isset($perm[0]) && isset($perm[1])) { $stmtPerm->execute(['nome' => $perm[0], 'desc' => $perm[1]]); } else { error_log("Formato inválido: " . print_r($perm, true)); } }
    $adminSenhaHash = password_hash('admin', PASSWORD_DEFAULT); $sqlAdmin = "INSERT IGNORE INTO `utilizadores` (`id`, `nome`, `login`, `senha_hash`, `cargo`, `ativo`) VALUES (1, 'Administrador', 'admin', :senha_hash, 'Admin', TRUE)"; $stmtAdmin = $pdo->prepare($sqlAdmin); $stmtAdmin->execute(['senha_hash' => $adminSenhaHash]);
    $sqlLinkPerm = "INSERT IGNORE INTO `utilizador_permissoes` (utilizador_id, permissao_id) SELECT 1, id FROM permissoes"; $pdo->exec($sqlLinkPerm);
    $sqlClientePadrao = "INSERT IGNORE INTO `clientes` (`id`, `nome`, `ativo`) VALUES (1, 'Consumidor Final', 1)"; $pdo->exec($sqlClientePadrao);

    // **LÓGICA ATUALIZADA COMPLETA: Gerenciar Mesas Ativas**
    try {
        // C: Buscar configuração e estado atual
        $stmtGetNumMesas = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'numero_mesas'");
        $stmtGetNumMesas->execute();
        $numMesasConfigStr = $stmtGetNumMesas->fetchColumn();
        $numMesasConfig = (int)($numMesasConfigStr ?: 10); 
        if ($numMesasConfig <= 0) $numMesasConfig = 10; 

        $stmtGetMaxMesa = $pdo->query("SELECT MAX(numero) as max_numero FROM mesas");
        $maxNumeroExistente = (int)($stmtGetMaxMesa->fetchColumn() ?: 0);

        // D: Adicionar mesas faltantes (se necessário)
        if ($numMesasConfig > $maxNumeroExistente) {
            $pdo->beginTransaction(); 
            try {
                // Novas mesas já são criadas como ativas por causa do DEFAULT 1
                $sqlInsertMesa = "INSERT IGNORE INTO mesas (numero, ativa) VALUES (:numero, 1)"; // Garante ativa = 1
                $stmtInsertMesa = $pdo->prepare($sqlInsertMesa);
                for ($i = $maxNumeroExistente + 1; $i <= $numMesasConfig; $i++) {
                    $stmtInsertMesa->execute([':numero' => $i]);
                }
                $pdo->commit(); 
            } catch (PDOException $eInsert) {
                $pdo->rollBack(); 
                error_log("Erro ao inserir mesas faltantes: " . $eInsert->getMessage()); 
            }
        }
        
        // E: Atualizar status 'ativa' das mesas existentes
        // Desativa mesas com número MAIOR que o configurado
        $sqlUpdateDesativar = "UPDATE mesas SET ativa = 0 WHERE numero > :numMesasConfig";
        $stmtUpdateDesativar = $pdo->prepare($sqlUpdateDesativar);
        $stmtUpdateDesativar->execute([':numMesasConfig' => $numMesasConfig]);

        // Reativa mesas com número MENOR OU IGUAL ao configurado (caso tenham sido desativadas antes)
        $sqlUpdateAtivar = "UPDATE mesas SET ativa = 1 WHERE numero <= :numMesasConfig";
        $stmtUpdateAtivar = $pdo->prepare($sqlUpdateAtivar);
        $stmtUpdateAtivar->execute([':numMesasConfig' => $numMesasConfig]);

    } catch (PDOException $eMesaConfig) {
        error_log("Erro ao verificar/atualizar mesas: " . $eMesaConfig->getMessage());
    }
    // --- FIM DA LÓGICA DE MESAS ---


} catch (PDOException $e) {
    error_log("Erro Crítico Bootstrap DB: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    die("Erro ao conectar ou inicializar o banco de dados. Contacte o suporte."); 
}

// Disponibiliza a conexão PDO global
$GLOBALS['pdo'] = $pdo;

?>