<?php
session_start();

$nuevo_usuario = $_POST['nuevo_usuario'];
$nuevo_password = $_POST['nuevo_password'];

if (empty($nuevo_usuario) || empty($nuevo_password)) {
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

$sql = "SELECT * FROM usuarios WHERE usuario=?";
$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $nuevo_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result->fetch_assoc()) {
        $hashed_password = password_hash($nuevo_password, PASSWORD_DEFAULT);
        $sql1 = "INSERT INTO usuarios (usuario, password) VALUES (?, ?)";
        $stmt1 = $conexion->prepare($sql1);

        if ($stmt1) {
            $stmt1->bind_param("ss", $nuevo_usuario, $hashed_password);
            $stmt1->execute();

            $_SESSION['id_usuario'] = $stmt1->insert_id;
            $_SESSION['usuario'] = $nuevo_usuario;

            header("Location: index.php");
        } else {
            echo "Error en la preparación de la consulta: " . $conexion->error;
        }

        $stmt1->close();
    } else {
        echo "El usuario ya existe";
    }

    $stmt->close();
} else {
    echo "Error en la preparación de la consulta: " . $conexion->error;
}

$conexion->close();
?>
