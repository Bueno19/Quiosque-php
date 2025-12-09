<?php
class Conexao
{
    private static $instance = null;

    // O construtor é privado para impedir que criem "new Conexao()" fora daqui
    private function __construct() {}

    public static function getConexao()
    {
        // Verifica se a instância já existe. Se não, cria uma nova.
        if (self::$instance === null) {
            try {
                // As constantes (DB_HOST, etc.) vêm do bootstrap.php
                // Nota: Adicionei as mesmas opções do bootstrap para garantir consistência
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Mostra erros graves
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Traz apenas dados limpos (chave => valor)
                    PDO::ATTR_EMULATE_PREPARES   => false,                // Melhor segurança contra injeção SQL
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                
            } catch (PDOException $e) {
                // Se der erro, mata o processo e mostra a mensagem (apenas em debug)
                die('Erro de conexão com o banco de dados: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}