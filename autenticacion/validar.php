<?php
session_start();

$usuario = $_POST['nnombre'];
$pass = $_POST['npassword'];

if (empty($usuario) || empty($pass)) {
    header("Location: index.php");
    exit();
}

$server = "localhost";
$user = "consulta";
$passw = "12345";
$bd = "manejocb";

$conexion = new mysqli($server, $user, $passw, $bd);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$sql = "SELECT id_usuario, usuario, password FROM usuarios WHERE usuario=? LIMIT 1";
$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $usuario, $hashed_password);
        $stmt->fetch();

        if (password_verify($pass, $hashed_password)) {
            $_SESSION['id_usuario'] = $id_usuario;
            $_SESSION['usuario'] = $usuario;
            header("Location: contenido.php");
            exit();
        } else {
            header("Location: index.php");
            exit();
        }
    } else {
        header("Location: index.php");
        exit();
    }

    $stmt->close();
} else {
    echo "Error en la preparación de la consulta: " . $conexion->error;
}

$conexion->close();
?>
