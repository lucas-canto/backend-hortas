<?php
// Carregar autoload do Composer
require_once __DIR__ . '/vendor/autoload.php';

// Carregar o .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Função para obter variável de ambiente
function env($key) {
    if (isset($_ENV[$key])) return $_ENV[$key];
    if (getenv($key) !== false) return getenv($key);
    if (isset($_SERVER[$key])) return $_SERVER[$key];
    return null;
}

// Variáveis do ambiente
$db_host = env('DB_HOST');
$db_port = env('DB_PORT');
$db_name = env('DB_NAME');
$db_user = env('DB_USER');
$db_pass = env('DB_PASS');
$api_url = env('API_URL');

// Verificação
echo "Verificando variáveis do .env:\n";
echo "DB_HOST: $db_host\n";
echo "DB_USER: $db_user\n";
echo "API_URL: $api_url\n";

if (!$db_host || !$db_port || !$db_name || !$db_user || !$db_pass || !$api_url) {
    die("Erro: Uma ou mais variáveis de ambiente não estão definidas.\n");
}

$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Dados do usuário de teste
$test_email = 'test@example.com';
$test_pass = 'password';
$hashed_pass = password_hash($test_pass, PASSWORD_DEFAULT);
$id_produtor = null;

// Array para relatório
$report = [];

try {
    // Conectar ao banco
    $conn = new PDO($dsn, $db_user, $db_pass, $options);

    // Cleanup no shutdown
    register_shutdown_function(function() use ($conn, &$id_produtor) {
        if ($id_produtor) {
            $stmt = $conn->prepare("DELETE FROM produtor WHERE id_produtor = :id");
            $stmt->execute([':id' => $id_produtor]);
            echo "Usuário de teste removido.\n";
        }
    });

    // Inserir usuário de teste
    $sql = "INSERT INTO produtor (nome_produtor, email_produtor, hash_senha) VALUES (:nome, :email, :senha)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':nome' => 'Test User', ':email' => $test_email, ':senha' => $hashed_pass]);
    $id_produtor = $conn->lastInsertId();
    echo "Usuário de teste inserido com ID: $id_produtor.\n";

    // Teste de login
    $data = ['email' => $test_email, 'senha' => $test_pass];
    $optionsHttp = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];
    $context  = stream_context_create($optionsHttp);
    $result = @file_get_contents($api_url, false, $context);

    if ($result === FALSE) {
        echo "Teste falhou: não foi possível conectar à API.\n";
        $report[] = [
            'teste' => 'Teste de login',
            'endpoint' => $api_url,
            'status' => 'Falhou',
            'mensagem' => 'Não foi possível conectar à API'
        ];
    } else {
        $response = json_decode($result);
        if (isset($response->status) && $response->status === 'sucesso' && isset($response->token)) {
            echo "Teste de login passou.\n";
            $report[] = [
                'teste' => 'Teste de login',
                'endpoint' => $api_url,
                'status' => 'Passou',
                'mensagem' => "Usuário de teste inserido com ID: $id_produtor e removido."
            ];
        } else {
            echo "Teste de login falhou.\n";
            print_r($response);
            $report[] = [
                'teste' => 'Teste de login',
                'endpoint' => $api_url,
                'status' => 'Falhou',
                'mensagem' => json_encode($response)
            ];
        }
    }

} catch (Exception $e) {
    echo "Ocorreu um erro durante o teste: " . $e->getMessage() . "\n";
    $report[] = [
        'teste' => 'Teste de login',
        'endpoint' => $api_url,
        'status' => 'Falhou',
        'mensagem' => $e->getMessage()
    ];
}

// Gerar relatório HTML
$filename = __DIR__ . '/relatorio_testes.html';
$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Relatório de Testes - Backend Hortas</title>
<style>
body { font-family: Arial; }
table { border-collapse: collapse; width: 80%; margin: 20px auto; }
th, td { border: 1px solid #ccc; padding: 8px; }
th { background-color: #f2f2f2; }
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
</style>
</head>
<body>
<h2 style="text-align:center;">Relatório de Testes - Backend Hortas</h2>
<table>
<tr><th>Teste</th><th>Endpoint</th><th>Status</th><th>Observações</th></tr>';

foreach ($report as $r) {
    $statusClass = strtolower($r['status']) === 'passou' ? 'pass' : 'fail';
    $html .= "<tr>
        <td>{$r['teste']}</td>
        <td>{$r['endpoint']}</td>
        <td class='{$statusClass}'>{$r['status']}</td>
        <td>{$r['mensagem']}</td>
    </tr>";
}

$html .= '</table></body></html>';

file_put_contents($filename, $html);
echo "Relatório gerado em: $filename\n";
?>
