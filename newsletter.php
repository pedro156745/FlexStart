<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/assets/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/assets/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/assets/vendor/phpmailer/src/SMTP.php';

header('Content-Type: application/json');

// === Lê config.env ===
$env_path = __DIR__ . '/config.env';
if (!file_exists($env_path)) {
  log_event('erro', 'Arquivo config.env ausente');
  resp('erro', 'Erro interno. Contate o suporte.');
}

function read_env($path)
{
  $env = [];
  foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#') continue;
    [$key, $value] = array_map('trim', explode('=', $line, 2));
    $env[$key] = trim($value, '"');
  }
  return $env;
}

$env = read_env($env_path);

$recaptcha_secret = $env['RECAPTCHA_SECRET'] ?? '';
$smtp_host        = $env['SMTP_HOST'] ?? 'smtp.hostinger.com';
$smtp_user        = $env['SMTP_USER'] ?? '';
$smtp_pass        = $env['SMTP_PASS'] ?? '';
$smtp_port        = $env['SMTP_PORT'] ?? 587;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'E-mail inválido.']);
        exit;
    }


    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "anphaweb";


    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao conectar ao banco.']);
        exit;
    }

    // Verifica duplicidade
    $stmt = $conn->prepare("SELECT id FROM newsletter WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'E-mail já cadastrado.']);
        exit;
    }

    // Gera token de confirmação
    $token = bin2hex(random_bytes(32));

    // Insere no banco
    $stmt = $conn->prepare("INSERT INTO newsletter (email, status, created_at) VALUES (?, 0, NOW())");
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar no banco.']);
        exit;
    }
    $last_id = $conn->insert_id;

    // Monta link de confirmação
    $confirm_link = "https://anphaweb.com.br/confirm_newsletter.php?token=$token&id=$last_id";

    // Salva token no banco
    $stmt = $conn->prepare("UPDATE newsletter SET token=? WHERE id=?");
    $stmt->bind_param("si", $token, $last_id);
    $stmt->execute();

    // Envia e-mail
    $mail = new PHPMailer(true);
    try {

        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = 'tls';
        $mail->Port = $smtp_port;

        $mail->setFrom('contato@anphaweb.com.br', 'Sua Newsletter');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Confirme sua inscrição';
        $mail->Body    = "Olá! Clique no link para confirmar sua inscrição: <a href='$confirm_link'>$confirm_link</a>";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'E-mail enviado!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao enviar e-mail: ' . $mail->ErrorInfo]);
    }

    $stmt->close();
    $conn->close();
}
