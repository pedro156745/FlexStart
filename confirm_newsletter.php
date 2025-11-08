<?php
// Debug temporário
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$message = '';
$alert_class = 'secondary';

if(!isset($_GET['id'], $_GET['token'])){
    $message = "Parâmetros inválidos.";
    $alert_class = 'danger';
} else {
    $id = intval($_GET['id']);
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT status, token FROM newsletter WHERE id=? LIMIT 1");
    if($stmt){
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($row = $result->fetch_assoc()){
            if($row['status'] == 1){
                $message = "Inscrição já confirmada anteriormente.";
                $alert_class = 'warning';
            } elseif($row['token'] === $token){
                $update = $conn->prepare("UPDATE newsletter SET status=1, confirmed_at=NOW() WHERE id=?");
                $update->bind_param("i", $id);
                $update->execute();
                $update->close();

                $message = "Inscrição confirmada com sucesso! Obrigado por se inscrever.";
                $alert_class = 'success';
            } else {
                $message = "Token inválido.";
                $alert_class = 'danger';
            }
        } else {
            $message = "ID não encontrado.";
            $alert_class = 'danger';
        }

        $stmt->close();
    } else {
        $message = "Erro no banco de dados.";
        $alert_class = 'danger';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação Newsletter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow p-4" style="max-width: 500px; width: 100%;">
        <div class="text-center">
            <h3 class="mb-3">Newsletter</h3>
            <div class="alert alert-<?= $alert_class ?> text-center" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
            <a href="/" class="btn btn-primary mt-3">Voltar para o site</a>
        </div>
    </div>
</div>

</body>
</html>
