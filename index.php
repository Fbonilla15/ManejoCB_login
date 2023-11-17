<?php
session_start();

if (isset($_SESSION['usuario'])) {
    // Si hay una sesión activa, redirigir a la página de contenido
    header("Location: autenticacion/contenido.php");
    exit();
} else {
    // Si no hay sesión activa, redirigir al inicio de sesión
    header("Location: autenticacion/index.php");
    exit();
}
?>
