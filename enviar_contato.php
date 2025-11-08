<?php

/**
 * Função segura para ler config.env
 */
$env_path = __DIR__ . '/config.env';
if (!file_exists($env_path)) resp('erro', 'Configuração ausente. Contate o suporte.');

function read_env_file($path)
{
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $env = [];
  foreach ($lines as $line) {
    $line = trim($line);
    if (!$line || $line[0] === '#') continue;
    [$key, $value] = array_map('trim', explode('=', $line, 2));
    $env[$key] = $value;
  }
  return $env;
}

$env = read_env_file($env_path);

$recaptcha_secret = $env['RECAPTCHA_SECRET'] ?? '';
$smtp_host        = $env['SMTP_HOST'] ?? 'smtp.hostinger.com';
$smtp_user        = $env['SMTP_USER'] ?? '';
$smtp_pass        = $env['SMTP_PASS'] ?? '';
$smtp_port        = $env['SMTP_PORT'] ?? 587;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/assets/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/assets/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/assets/vendor/phpmailer/src/SMTP.php';

function resp($status, $msg, $debug = [])
{
  echo json_encode(['status' => $status, 'msg' => $msg, 'debug' => $debug], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp('erro', 'Método inválido.');

$nome  = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$assunto = trim($_POST['assunto'] ?? 'Contato pelo site');
$mensagem = trim($_POST['mensagem'] ?? '');
$token = $_POST['recaptcha_token'] ?? '';

if (!$nome || !$email || !$mensagem) resp('erro', 'Preencha todos os campos obrigatórios.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) resp('erro', 'E-mail inválido.');
if (!$token || !$recaptcha_secret) resp('erro', 'reCAPTCHA ausente.');

// Validação reCAPTCHA
$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query(['secret' => $recaptcha_secret, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '']),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
if ($response === false) resp('erro', 'Falha cURL: ' . curl_error($ch));
curl_close($ch);

$resp_obj = json_decode($response, true);
if (!$resp_obj) resp('erro', 'Resposta inválida do reCAPTCHA', ['raw_response' => $response]);

// Debug seguro
$debug = ['recaptcha' => $resp_obj];

if (empty($resp_obj['success'])) {
  $errors = implode(',', $resp_obj['error-codes'] ?? []);
  resp('erro', 'Falha na validação reCAPTCHA [' . $errors . ']', $debug);
}
if (($resp_obj['action'] ?? '') !== 'contato') resp('erro', 'Ação reCAPTCHA inválida.', $debug);
if (($resp_obj['score'] ?? 0) < 0.5) resp('erro', 'Score baixo, possível bot.', $debug);

// Envio e-mail
try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = $smtp_host;
  $mail->SMTPAuth   = true;
  $mail->Username   = $smtp_user;
  $mail->Password   = $smtp_pass;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = 587;
  $mail->CharSet    = 'UTF-8';


  $mail->setFrom('contato@anphaweb.com.br', 'Anpha Web');
  $mail->addAddress('contato@anphaweb.com.br', 'Anpha Web');

  $mail->isHTML(true);
  $mail->Subject = "📩 Novo contato: " . $assunto;
  $mail->Body = "
        <h3>Mensagem recebida</h3>
        <p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>
        <p><strong>E-mail:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>
        <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
        <hr>
        <p style='font-size:0.8rem;color:#666'>IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'n/d') . "</p>
    ";
  $mail->AltBody = "Nome: $nome\nE-mail: $email\nAssunto: $assunto\nMensagem:\n$mensagem";

  $mail->send();
  resp('sucesso', 'Mensagem enviada com sucesso!', $debug);
} catch (Exception $e) {
  resp('erro', 'Erro ao enviar: ' . ($mail->ErrorInfo ?? $e->getMessage()), $debug);
}
