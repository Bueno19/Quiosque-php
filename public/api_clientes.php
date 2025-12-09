<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../models/Cliente.php';

// CORREÇÃO 2: Verifica se a função já existe antes de declarar
// Isso evita conflito com api_relatorios.php ou outros arquivos
if (!function_exists('json_response')) {
    function json_response($data, $sucesso = true, $erro = null) {
        echo json_encode(['sucesso' => $sucesso, 'dados' => $data, 'erro' => $erro]);
        exit;
    }
}

try {
    $clienteModel = new Cliente();
    $acao = $_REQUEST['acao'] ?? '';

    switch ($acao) {
        case 'listar':
            $termo = $_GET['termo'] ?? '';
            $clientes = $clienteModel->listar($termo);
            json_response($clientes);
            break;

        case 'detalhes':
            $id = $_GET['id'] ?? 0;
            if (!$id) throw new Exception("ID inválido.");
            $cliente = $clienteModel->buscarPorId($id);
            json_response($cliente);
            break;

        case 'salvar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método inválido.");
            
            // Monta o array com os campos do formulário
            $dados = [
                'id' => $_POST['id'] ?? null,
                'nome' => $_POST['nome'] ?? '',
                'cpf' => $_POST['cpf'] ?? '',
                'email' => $_POST['email'] ?? '',
                'telefone' => $_POST['telefone'] ?? '',
                'cep' => $_POST['cep'] ?? '',
                'logradouro' => $_POST['logradouro'] ?? '',
                'numero' => $_POST['numero'] ?? '',
                'bairro' => $_POST['bairro'] ?? '',
                'cidade' => $_POST['cidade'] ?? '',
                'uf' => $_POST['uf'] ?? ''
            ];

            if (empty($dados['nome'])) throw new Exception("O nome é obrigatório.");

            if ($clienteModel->salvar($dados)) {
                json_response(null, true);
            } else {
                throw new Exception("Erro ao salvar no banco de dados.");
            }

        case 'excluir':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método inválido.");
            $id = $_POST['id'] ?? 0;
            if ($clienteModel->excluir($id)) {
                json_response(null, true);
            } else {
                throw new Exception("Erro ao excluir cliente.");
            }

        default:
            throw new Exception("Ação inválida.");
    }

} catch (Exception $e) {
    http_response_code(500);
    // Se a função json_response não estiver disponível por algum motivo bizarro, usa echo simples
    if (function_exists('json_response')) {
        json_response(null, false, $e->getMessage());
    } else {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
}
?>