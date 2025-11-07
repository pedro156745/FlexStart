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

  if (!$nome || !$email || !$mensagem) {
    echo json_encode(['status' => 'erro', 'msg' => 'Preencha todos os campos.']);
    exit;
  }

  $mail = new PHPMailer(true);
  try {
    // Configurações do servidor
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com'; // ajuste conforme seu provedor
    $mail->SMTPAuth = true;
    $mail->Username = 'contato@anphaweb.com.br';
    $mail->Password = 'SENHA_DO_EMAIL'; // coloque aqui a senha do e-mail
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
