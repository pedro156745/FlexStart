<?php
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
$status = 'info';
$title = 'Atenção';
$message = 'Parâmetros inválidos.';

if(isset($_GET['id'], $_GET['token'])){
    $id = intval($_GET['id']);
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT status, token FROM newsletter WHERE id=? LIMIT 1");
    if($stmt){
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($row = $result->fetch_assoc()){
            if($row['status'] == 1){
                $status = 'warning';
                $title = 'Já confirmado';
                $message = 'Esta inscrição já foi confirmada anteriormente.';
            } elseif($row['token'] === $token){
                $update = $conn->prepare("UPDATE newsletter SET status=1, confirmed_at=NOW() WHERE id=?");
                $update->bind_param("i", $id);
                $update->execute();
                $update->close();

                $status = 'success';
                $title = 'Sucesso!';
                $message = 'Sua inscrição na newsletter foi confirmada com sucesso!';
            } else {
                $status = 'danger';
                $title = 'Token inválido';
                $message = 'O token fornecido é inválido.';
            }
        } else {
            $status = 'danger';
            $title = 'ID não encontrado';
            $message = 'Não foi possível encontrar esta inscrição.';
        }
        $stmt->close();
    } else {
        $status = 'danger';
        $title = 'Erro';
        $message = 'Erro no banco de dados.';
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    background: #f8f9fa;
}
.card {
    max-width: 500px;
    width: 100%;
    margin: auto;
    margin-top: 5%;
    padding: 2rem;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 12px;
}
.alert i {
    margin-right: 0.5rem;
}
</style>
</head>
<body>

<div class="card text-center">
    <h3 class="mb-3">Newsletter</h3>
    <div class="alert alert-<?= $status ?>" role="alert">
        <?php
        $icon = match($status){
            'success' => 'bi-check-circle-fill',
            'warning' => 'bi-exclamation-triangle-fill',
            'danger' => 'bi-x-circle-fill',
            default   => 'bi-info-circle-fill'
        };
        echo "<i class='bi $icon'></i><strong>$title</strong> - $message";
        ?>
    </div>
    <a href="/" class="btn btn-primary mt-3">Voltar para o site</a>
</div>

</body>
</html>
