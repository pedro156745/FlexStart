<?php
// Ativa debug temporário (remover após produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/assets/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/assets/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/assets/vendor/phpmailer/src/SMTP.php';

// =========================
// Função para responder JSON
// =========================
function resp($status, $message)
{
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// =========================
// Lê config.env
// =========================
$env_path = __DIR__ . '/config.env';
if (!file_exists($env_path)) resp('error', 'Arquivo config.env ausente');

function read_env($path)
{
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $env[$key] = trim($value, '"');
    }
    return $env;
}

$env = read_env($env_path);

// Config SMTP
$smtp_host = $env['SMTP_HOST'] ?? '';
$smtp_user = $env['SMTP_USER'] ?? '';
$smtp_pass = $env['SMTP_PASS'] ?? '';
$smtp_port = $env['SMTP_PORT'] ?? 587;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp('error', 'Método inválido');

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) resp('error', 'E-mail inválido');

// =========================
// Conexão com Banco
// =========================
$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$db   = $env['DB_NAME'] ?? 'anphaweb';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) resp('error', 'Erro ao conectar ao banco.');

// =========================
// Verifica duplicidade
// =========================
$stmt = $conn->prepare("SELECT id FROM newsletter WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    resp('error', 'E-mail já cadastrado.');
}
$stmt->close();

// =========================
// Gera token e salva
// =========================
$token = bin2hex(random_bytes(32));

$stmt = $conn->prepare("INSERT INTO newsletter (email, status, created_at, token) VALUES (?, 0, NOW(), ?)");
$stmt->bind_param("ss", $email, $token);
if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    resp('error', 'Erro ao salvar no banco.');
}
$last_id = $conn->insert_id;
$stmt->close();
$conn->close();

// =========================
// Monta link de confirmação
// =========================
$confirm_link = "https://anphaweb.com.br/confirm_newsletter.php?token=$token&id=$last_id";

// =========================
// Envia e-mail via PHPMailer
// =========================
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = 'tls';
    $mail->Port       = $smtp_port;

    $mail->setFrom($smtp_user, 'Sua Newsletter');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Confirme sua inscrição';
    $mail->Body    = "Olá! Clique no link para confirmar sua inscrição: <a href='$confirm_link'>$confirm_link</a>";

    $mail->send();
    resp('success', 'E-mail enviado! Verifique seu e-mail para confirmar a inscrição.');
} catch (Exception $e) {
    resp('error', 'Erro ao enviar e-mail: ' . $mail->ErrorInfo);
}
