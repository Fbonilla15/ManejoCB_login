<?php
session_start();

if (isset($_POST['nnombre']) && isset($_POST['npassword'])) {
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

    $conexion = mysqli_connect($server, $user, $passw, $bd) or die("Ha sucedido un error inexperado en la conexión de la base de datos");

    $sql = "SELECT * FROM usuarios WHERE usuario=?";
    $stmt = mysqli_prepare($conexion, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $usuario);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_array($result)) {
            if (password_verify($pass, $row['password'])) {
                $_SESSION['id_usuario'] = $row['id_usuario'];
                $_SESSION['usuario'] = $usuario;
                header("Location: contenido.php");
            } else {
                header("Location: index.php");
                exit();
            }
        } else {
            header("Location: index.php");
            exit();
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Error en la preparación de la consulta: " . mysqli_error($conexion);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="../css/estilologin.css">
    <style>
        .registro-formulario {
            display: none;
        }

        .oculto {
            display: none;
        }
    </style>
</head>
<body>
    <div class="formulario">
        <center>
            <!-- Formulario de inicio de sesión -->
            <form method="POST" action="index.php" class="formulario-inicio">
                <input type="text" name="nnombre" placeholder="Usuario" />
                <br />
                <input type="password" name="npassword" placeholder="Contraseña" />
                <br />
                <button type="submit" class="boton-inicio">Iniciar Sesión</button>
            </form>

            <!-- Botón para mostrar el formulario de registro -->
            <button class="btnregistrar" onclick="mostrarFormularioRegistro()">Registrarse</button>

            <!-- Formulario de registro (inicialmente oculto) -->
            <form method="POST" action="registrar.php" class="registro-formulario">
                <input type="text" name="nuevo_usuario" placeholder="Nuevo Usuario" required />
                <br />
                <input type="password" name="nuevo_password" placeholder="Nueva Contraseña" required />
                <br />
                <button type="submit">Registrarse</button>
            </form>
        </center>

        <script>
            function mostrarFormularioRegistro() {
                var formularioInicio = document.querySelector('.formulario-inicio');
                var botonInicio = document.querySelector('.btnregistrar');
                var formularioRegistro = document.querySelector('.registro-formulario');
            

                formularioInicio.classList.add('oculto');
                botonInicio.classList.add('oculto');
                formularioRegistro.style.display = 'block';
            }
        </script>
    </div>
</body>
</html>
