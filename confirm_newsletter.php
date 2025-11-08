<?php
// Debug temporário
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

// =========================
// Lê config.env
// =========================
$env_path = __DIR__ . '/config.env';
if (!file_exists($env_path)) die("Arquivo config.env ausente");

function read_env($path){
    $env = [];
    foreach(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line){
        if ($line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $env[$key] = trim($value, '"');
    }
    return $env;
}

$env = read_env($env_path);

// =========================
// Conexão com banco
// =========================
$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? '';
$pass = $env['DB_PASS'] ?? '';
$db   = $env['DB_NAME'] ?? '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Erro ao conectar ao banco: ".$conn->connect_error);

// =========================
// Valida parâmetros
// =========================
if(!isset($_GET['id'], $_GET['token'])){
    die("Parâmetros inválidos.");
}

$id = intval($_GET['id']);
$token = $_GET['token'];

// =========================
// Busca token no banco
// =========================
$stmt = $conn->prepare("SELECT status, token FROM newsletter WHERE id=? LIMIT 1");
if(!$stmt) die("Erro no prepare: ".$conn->error);

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){
    if($row['status'] == 1){
        echo "Inscrição já confirmada anteriormente.";
    } elseif($row['token'] === $token){
        // Atualiza status
        $update = $conn->prepare("UPDATE newsletter SET status=1, confirmed_at=NOW() WHERE id=?");
        $update->bind_param("i", $id);
        $update->execute();
        $update->close();
        echo "Inscrição confirmada com sucesso!";
    } else {
        echo "Token inválido.";
    }
} else {
    echo "ID não encontrado.";
}

$stmt->close();
$conn->close();
?>
