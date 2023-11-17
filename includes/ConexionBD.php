<?php
class ConexionBD {
    private static $conexion;

    public static function obtenerConexion() {
        if (!self::$conexion) {
            self::$conexion = new mysqli("localhost", "consulta", "12345", "manejocb");
            if (self::$conexion->connect_error) {
                die("Error de conexión: " . self::$conexion->connect_error);
            }
            self::$conexion->set_charset("utf8"); // Establecer la codificación de caracteres
        }
        return self::$conexion;
    }
}
?>
