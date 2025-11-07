<?php
// enviar_contato.php - Versão final para Anpha Web
// Requisitos: PHPMailer (assets/vendor/phpmailer/src/...), cURL habilitado no servidor

// === DEBUG / ERROS (remova ou desative em produção) ===
error_reporting(E_ALL);
ini_set('display_errors', 0); // alterar para 0 em produção

header('Content-Type: application/json; charset=utf-8');

// Imports PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/assets/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/assets/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/assets/vendor/phpmailer/src/SMTP.php';

// Função utilitária para responder JSON e encerrar
function resp($status, $msg) {
  echo json_encode(['status' => $status, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  resp('erro', 'Método inválido.');
}

// Recebe e sanitiza
$nome = trim(filter_var($_POST['nome'] ?? '', FILTER_SANITIZE_STRING));
$email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
$assunto = trim(filter_var($_POST['assunto'] ?? 'Contato pelo site', FILTER_SANITIZE_STRING));
$mensagem = trim(filter_var($_POST['mensagem'] ?? '', FILTER_SANITIZE_STRING));
$recaptcha_token = $_POST['recaptcha_token'] ?? '';

// Validações simples
if (!$nome || !$email || !$mensagem) {
  resp('erro', 'Preencha todos os campos obrigatórios.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  resp('erro', 'E-mail inválido.');
}

// === Validação reCAPTCHA v3 via cURL ===
$recaptcha_secret = '6LdRYQUsAAAAAFSiy1uDJ46HCDLOf-QlGcGyd6_f'; // <<< substitua aqui pela sua secret key
if (!$recaptcha_token) {
  resp('erro', 'Validação reCAPTCHA ausente. Atualize a página e tente novamente.');
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
  'secret' => $recaptcha_secret,
  'response' => $recaptcha_token,
  'remoteip' => $_SERVER['REMOTE_ADDR']
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$curl_err = curl_error($ch);
curl_close($ch);

if ($curl_err) {
  resp('erro', 'Erro ao validar reCAPTCHA: '.$curl_err);
}

$resp_obj = json_decode($response, true);
if (!$resp_obj || !isset($resp_obj['success']) || $resp_obj['success'] !== true) {
  resp('erro', 'Validação reCAPTCHA falhou. Tente novamente.');
}

// score: 0.0 - 1.0 (recomendado aceitar >= 0.5)
$score_threshold = 0.5;
if (($resp_obj['score'] ?? 0) < $score_threshold) {
  resp('erro', 'Detectamos atividade suspeita. Tente novamente mais tarde.');
}

// === Envio via PHPMailer (Hostinger) ===
try {
  $mail = new PHPMailer(true);

  // Para DEBUG (temporariamente): descomente durante teste
  // $mail->SMTPDebug = 2;
  // $mail->Debugoutput = 'html';

  // Configuração SMTP
  $mail->isSMTP();
  $mail->Host = 'smtp.hostinger.com';            // SMTP Host (Hostinger)
  $mail->SMTPAuth = true;
  $mail->Username = 'contato@anphaweb.com.br';   // seu e-mail
  $mail->Password = 'SUA_SENHA_SMTP';            // <<< substitua pela senha do e-mail (painel host)
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;
  $mail->CharSet = 'UTF-8';

  // Remetente e destinatários
  $mail->setFrom('contato@anphaweb.com.br', 'Anpha Web');
  $mail->addAddress('contato@anphaweb.com.br', 'Anpha Web'); // onde você quer receber

  // Conteúdo do e-mail
  $mail->isHTML(true);
  $mail->Subject = "📩 Novo contato via site: " . ($assunto ?: 'Contato');
  $body  = "<h3>Nova mensagem - Anpha Web</h3>";
  $body .= "<p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>";
  $body .= "<p><strong>E-mail:</strong> " . htmlspecialchars($email) . "</p>";
  $body .= "<p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>";
  $body .= "<p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>";
  $body .= "<hr>";
  $body .= "<p style='font-size:0.85rem;color:#666;'>IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'n/d') . " | Página: " . ($_SERVER['HTTP_REFERER'] ?? 'n/d') . "</p>";

  $mail->Body = $body;
  $mail->AltBody = "Nome: $nome\nE-mail: $email\nAssunto: $assunto\n\nMensagem:\n$mensagem";

  // Envia
  if ($mail->send()) {
    resp('sucesso', 'Mensagem enviada com sucesso!');
  } else {
    resp('erro', 'Erro desconhecido ao enviar a mensagem.');
  }
} catch (Exception $e) {
  // Em produção não exponha detalhes sensíveis - logue em arquivo se necessário
  // error_log('PHPMailer Error: ' . $mail->ErrorInfo);
  resp('erro', 'Erro ao enviar: ' . ($mail->ErrorInfo ?? $e->getMessage()));
}
