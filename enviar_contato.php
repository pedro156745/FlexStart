<?php
// =====================================
// Anpha Web - Envio de Contato (Segura)
// =====================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/assets/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/assets/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/assets/vendor/phpmailer/src/SMTP.php';

header('Content-Type: application/json; charset=utf-8');

// === Função de resposta rápida ===
function resp($status, $msg, $extra = [])
{
  echo json_encode(['status' => $status, 'msg' => $msg, 'debug' => $extra], JSON_UNESCAPED_UNICODE);
  exit;
}

// === Lê config.env ===
$env_path = __DIR__ . '/config.env';
if (!file_exists($env_path)) resp('erro', 'Arquivo de configuração ausente.');

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
$smtp_host = $env['SMTP_HOST'] ?? 'smtp.hostinger.com';
$smtp_user = $env['SMTP_USER'] ?? '';
$smtp_pass = $env['SMTP_PASS'] ?? '';
$smtp_port = $env['SMTP_PORT'] ?? 587;

// === Validação de método ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp('erro', 'Método inválido.');

// === Coleta dos dados ===
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$assunto = trim($_POST['assunto'] ?? 'Contato pelo site');
$mensagem = trim($_POST['mensagem'] ?? '');
$token = $_POST['recaptcha_token'] ?? '';

if (!$nome || !$email || !$mensagem) resp('erro', 'Preencha todos os campos obrigatórios.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) resp('erro', 'E-mail inválido.');
if (!$token || !$recaptcha_secret) resp('erro', 'Falha ao validar reCAPTCHA.');

// === Validação reCAPTCHA ===
$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query([
    'secret' => $recaptcha_secret,
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
  ]),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
if ($response === false) resp('erro', 'Erro ao validar reCAPTCHA.');
curl_close($ch);

$resp_obj = json_decode($response, true);
if (empty($resp_obj['success'])) resp('erro', 'Falha na validação reCAPTCHA.', $resp_obj);
if (($resp_obj['score'] ?? 0) < 0.5) resp('erro', 'Ação suspeita detectada (score baixo).');
if (($resp_obj['action'] ?? '') !== 'contato') resp('erro', 'Ação inválida.', $resp_obj);

// === Envio via PHPMailer ===
try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = $smtp_host;
  $mail->SMTPAuth   = true;
  $mail->Username   = $smtp_user;
  $mail->Password   = $smtp_pass;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = $smtp_port;
  $mail->CharSet    = 'UTF-8';

  $mail->setFrom('contato@anphaweb.com.br', 'Anpha Web');
  $mail->addAddress('contato@anphaweb.com.br', 'Anpha Web');

  $mail->isHTML(true);
  $mail->Subject = "📩 Novo contato - " . htmlspecialchars($assunto);
  $mail->Body = "
    <h3>Nova mensagem recebida</h3>
    <p><b>Nome:</b> " . htmlspecialchars($nome) . "</p>
    <p><b>Email:</b> " . htmlspecialchars($email) . "</p>
    <p><b>Assunto:</b> " . htmlspecialchars($assunto) . "</p>
    <p><b>Mensagem:</b><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
    <hr><small>IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'n/d') . "</small>
  ";

  $mail->send();
  resp('sucesso', 'Mensagem enviada com sucesso!');
} catch (Exception $e) {
  resp('erro', 'Erro ao enviar: ' . $mail->ErrorInfo);
}
