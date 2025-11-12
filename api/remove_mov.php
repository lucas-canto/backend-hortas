<?php
// Carregar autoload do Composer
require_once __DIR__ . '/vendor/autoload.php';

// Carregar variáveis do .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); // apenas load(), sem overload()

// Teste rápido para verificar se as variáveis foram carregadas
echo "Verificando variáveis do .env:\n";
echo "DB_HOST: " . getenv('DB_HOST') . "\n";
echo "DB_PORT: " . getenv('DB_PORT') . "\n";
echo "DB_NAME: " . getenv('DB_NAME') . "\n";
echo "DB_USER: " . getenv('DB_USER') . "\n";
echo "DB_PASS: " . getenv('DB_PASS') . "\n";
echo "API_URL: " . getenv('API_URL') . "\n";

// Atribuir variáveis do ambiente para o script
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$api_url = getenv('API_URL');

// Validar variáveis
if (!$db_host || !$db_port || !$db_name || !$db_user || !$db_pass || !$api_url) {
    die("Erro: Uma ou mais variáveis de ambiente não estão definidas.\n");
}

// Conectar ao banco de dados
$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $db_user, $db_pass, $options);
    echo "Conexão com o banco de dados realizada com sucesso!\n";
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage() . "\n");
}
