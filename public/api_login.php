<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Carrega configurações
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/Utilizador.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método inválido.");
    }

    // Pega os dados do login
    $login = $_POST['login'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($login) || empty($senha)) {
        throw new Exception("Preencha o login e a senha.");
    }

    $userModel = new Utilizador();
    
    // Aqui ele chama a função que agora EXISTE no modelo
    $usuario = $userModel->verificarLogin($login, $senha);

    if ($usuario) {
        // Login Sucesso: Salva na sessão
        // IMPORTANTE: Usar as mesmas chaves que o auth_check.php espera
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nome'] = $usuario['nome'];
        $_SESSION['user_cargo'] = $usuario['cargo'];
        $_SESSION['user_permissoes'] = $usuario['permissoes'];
        
        // Mantém compatibilidade com verificações antigas se existirem
        $_SESSION['utilizador_logado'] = true; 
        
        echo json_encode(['sucesso' => true, 'mensagem' => 'Bem-vindo!']);
    } else {
        throw new Exception("Utilizador ou senha incorretos.");
    }

} catch (Exception $e) {
    // Retorna erro 401 (Não autorizado) ou 500
    http_response_code(401); 
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
?>