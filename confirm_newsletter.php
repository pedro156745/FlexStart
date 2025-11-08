<?php
// =========================
// Conexão com Banco
// =========================
$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$db   = $env['DB_NAME'] ?? 'anphaweb';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Erro de conexão");

if(isset($_GET['id'], $_GET['token'])){
    $id = intval($_GET['id']);
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT token FROM newsletter WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($db_token);
    if($stmt->fetch() && $db_token === $token){
        $stmt->close();
        $stmt = $conn->prepare("UPDATE newsletter SET status=1, confirmed_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo "Inscrição confirmada com sucesso!";
    } else {
        echo "Token inválido ou já confirmado.";
    }
} else {
    echo "Parâmetros inválidos.";
}
$conn->close();
?>
