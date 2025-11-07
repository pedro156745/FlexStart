<?php
/**
 * enviar_contato.php – Versão final e otimizada (Anpha Web)
 * Requisitos: PHP 8+, cURL habilitado, PHPMailer instalado em /assets/vendor/phpmailer/src/
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // ⚙️ altere para 1 apenas em ambiente de teste

header('Content-Type: application/json; charset=utf-8');

// ===========================================================
// 🔒 Carrega variáveis do config.env (mantido fora do GitHub)
// ===========================================================
$env_path = __DIR__ . '/config.env';
if (!file_exists($env_path)) {
  echo json_encode(['status' => 'erro', 'msg' => 'Configuração ausente. Contate o suporte.'], JSON_UNESCAPED_UNICODE);
  exit;
}

$env = parse_ini_file($env_path);

$recaptcha_secret = $env['RECAPTCHA_SECRET'] ?? '';
$smtp_host        = $env['SMTP_HOST'] ?? 'smtp.hostinger.com';
$smtp_user        = $env['SMTP_USER'] ?? '';
$smtp_pass        = $env['SMTP_PASS'] ?? '';
$smtp_port        = $env['SMTP_PORT'] ?? 587;

// ===========================================================
// 📦 Imports do PHPMailer
// ===========================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/assets/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/assets/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/assets/vendor/phpmailer/src/SMTP.php';

// ===========================================================
// 🧩 Função utilitária para resposta JSON
// ===========================================================
function resp(string $status, string $msg): void {
  echo json_encode(['status' => $status, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

// ===========================================================
// 🚫 Somente POST permitido
// ===========================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  resp('erro', 'Método inválido.');
}

// ===========================================================
// 🧹 Sanitização dos campos
// ===========================================================
$nome           = trim(filter_var($_POST['nome'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$email          = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
$assunto        = trim(filter_var($_POST['assunto'] ?? 'Contato pelo site', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$mensagem       = trim(filter_var($_POST['mensagem'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$recaptcha_token = $_POST['recaptcha_token'] ?? '';

// ===========================================================
// ✅ Validação dos campos
// ===========================================================
if (!$nome || !$email || !$mensagem) {
  resp('erro', 'Preencha todos os campos obrigatórios.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  resp('erro', 'E-mail inválido.');
}

// ===========================================================
// 🧠 Validação reCAPTCHA v3
// ===========================================================
if (!$recaptcha_token || !$recaptcha_secret) {
  resp('erro', 'Validação reCAPTCHA ausente. Atualize a página e tente novamente.');
}

$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
  CURLOPT_POST            => true,
  CURLOPT_POSTFIELDS      => http_build_query([
    'secret'   => $recaptcha_secret,
    'response' => $recaptcha_token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
  ]),
  CURLOPT_RETURNTRANSFER  => true,
  CURLOPT_TIMEOUT         => 10,
]);
$response = curl_exec($ch);
curl_close($ch);

$resp_obj = json_decode($response, true);

if (empty($resp_obj['success'])) {
  $errors = implode(', ', $resp_obj['error-codes'] ?? []);
  resp('erro', 'Falha na validação reCAPTCHA. [' . $errors . ']');
}

$score  = $resp_obj['score'] ?? 0;
$action = $resp_obj['action'] ?? '';

if ($action !== 'contato') {
  resp('erro', 'Ação reCAPTCHA inválida.');
}

if ($score < 0.5) {
  resp('erro', 'Validação reCAPTCHA indicou comportamento suspeito (score: ' . $score . ').');
}

// ===========================================================
// 📧 Envio de e-mail via PHPMailer (Hostinger)
// ===========================================================
try {
  $mail = new PHPMailer(true);

  // 🔧 Configuração SMTP
  $mail->isSMTP();
  $mail->Host       = $smtp_host;
  $mail->SMTPAuth   = true;
  $mail->Username   = $smtp_user;
  $mail->Password   = $smtp_pass;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = $smtp_port;
  $mail->CharSet    = 'UTF-8';

  // 📨 Remetente e destinatário
  $mail->setFrom('contato@anphaweb.com.br', 'Anpha Web');
  $mail->addAddress('contato@anphaweb.com.br', 'Anpha Web');

  // 💬 Corpo do e-mail
  $mail->isHTML(true);
  $mail->Subject = "📩 Novo contato via site: " . ($assunto ?: 'Contato');

  $mail->Body = "
    <h3>Nova mensagem - Anpha Web</h3>
    <p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>
    <p><strong>E-mail:</strong> " . htmlspecialchars($email) . "</p>
    <p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>
    <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
    <hr>
    <p style='font-size:0.85rem;color:#666;'>IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'n/d') . " | Página: " . ($_SERVER['HTTP_REFERER'] ?? 'n/d') . "</p>
  ";

  $mail->AltBody = "Nome: $nome\nE-mail: $email\nAssunto: $assunto\n\nMensagem:\n$mensagem";

  // 🚀 Envia
  if ($mail->send()) {
    resp('sucesso', 'Mensagem enviada com sucesso! Obrigado por entrar em contato.');
  } else {
    resp('erro', 'Erro desconhecido ao enviar a mensagem.');
  }

} catch (Exception $e) {
  // ⚠️ Em produção, prefira logar o erro em vez de exibir
  // error_log('PHPMailer Error: ' . $mail->ErrorInfo);
  resp('erro', 'Erro ao enviar: ' . ($mail->ErrorInfo ?: $e->getMessage()));
}
