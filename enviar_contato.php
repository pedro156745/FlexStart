<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'assets/vendor/phpmailer/src/Exception.php';
require 'assets/vendor/phpmailer/src/PHPMailer.php';
require 'assets/vendor/phpmailer/src/SMTP.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nome = htmlspecialchars($_POST['nome']);
  $email = htmlspecialchars($_POST['email']);
  $assunto = htmlspecialchars($_POST['assunto']);
  $mensagem = htmlspecialchars($_POST['mensagem']);
  $recaptcha_token = $_POST['recaptcha_token'] ?? '';

  // 🔒 Validação reCAPTCHA v3
  $secret_key = '6LdRYQUsAAAAAFSiy1uDJ46HCDLOf-QlGcGyd6_f'; // coloque aqui sua chave secreta
  $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret_key&response=$recaptcha_token");
  $responseKeys = json_decode($response, true);

  if(!$responseKeys["success"] || $responseKeys["score"] < 0.5) {
    echo json_encode(['status' => 'erro', 'msg' => 'Validação reCAPTCHA falhou. Tente novamente.']);
    exit;
  }

  if (!$nome || !$email || !$mensagem) {
    echo json_encode(['status' => 'erro', 'msg' => 'Preencha todos os campos.']);
    exit;
  }

  $mail = new PHPMailer(true);
  try {
    // Configurações SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com'; // Trocar depois para o da anpha web
    $mail->SMTPAuth = true;
    $mail->Username = 'pedrinhomeim753@gmail.com';
    $mail->Password = 'pnoiurdigmbbdptm';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Remetente e destinatário
    $mail->setFrom('contato@anphaweb.com.br', 'Anpha Web');
    $mail->addAddress('contato@anphaweb.com.br', 'Anpha Web');

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = "Novo contato: $assunto";
    $mail->Body    = "
      <h3>Nova mensagem do site</h3>
      <p><b>Nome:</b> $nome</p>
      <p><b>Email:</b> $email</p>
      <p><b>Mensagem:</b></p>
      <p>$mensagem</p>
    ";

    $mail->send();
    echo json_encode(['status' => 'sucesso', 'msg' => 'Mensagem enviada com sucesso!']);
  } catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'msg' => 'Erro ao enviar: ' . $mail->ErrorInfo]);
  }
}
?>
