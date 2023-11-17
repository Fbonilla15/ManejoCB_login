<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class ManejadorCuenta {
    private $conexionBD;

    public function __construct($conexionBD) {
        $this->conexionBD = $conexionBD;
    }

    public function depositar($monto) {
        // Obtener el saldo actual de la base de datos
        $conexionBD = new ConexionBD();
        $conexion = $conexionBD->obtenerConexion();
        $querySaldo = "SELECT saldo FROM cuenta WHERE usuario_id = {$_SESSION['id_usuario']}"; // Ajusta según tu estructura de base de datos
        $resultadoSaldo = $conexion->query($querySaldo);
    
        if ($resultadoSaldo && $resultadoSaldo->num_rows > 0) {
            // Ya hay un registro en la tabla cuenta para el usuario
            $filaSaldo = $resultadoSaldo->fetch_assoc();
            $saldo = $filaSaldo['saldo'];
    
            // Actualizar el saldo en la base de datos
            $nuevoSaldo = $saldo + $monto;
            $queryUpdateSaldo = "UPDATE cuenta SET saldo = $nuevoSaldo WHERE usuario_id = {$_SESSION['id_usuario']}"; // Ajusta según tu estructura de base de datos
            $resultadoUpdateSaldo = $conexion->query($queryUpdateSaldo);
    
            if (!$resultadoUpdateSaldo) {
                die("Error en la consulta de actualización de saldo: " . $conexion->error);
            }
    
            // Agregar el movimiento
            $this->agregarMovimiento("Depósito", $monto);
    
            // Actualizar la sesión
            $_SESSION['saldo'] = $nuevoSaldo;
    
            
        } else {
            // No hay un registro en la tabla cuenta para el usuario, es el primer depósito
            $nuevoSaldo = $monto;
            $queryInsertSaldo = "INSERT INTO cuenta (usuario_id, saldo) VALUES ({$_SESSION['id_usuario']}, $nuevoSaldo)";
            $resultadoInsertSaldo = $conexion->query($queryInsertSaldo);
    
            if (!$resultadoInsertSaldo) {
                die("Error al insertar el primer saldo: " . $conexion->error);
            }
    
            // Agregar el movimiento
            $this->agregarMovimiento("Primer Depósito", $monto);
    
            // Actualizar la sesión
            $_SESSION['saldo'] = $nuevoSaldo;
    
           
        }
    
        // Liberar el resultado y cerrar la conexión
        $resultadoSaldo->free();
    }
    
    public function retirar($monto) {
        $conexionBD = new ConexionBD();
        $conexion = $conexionBD->obtenerConexion();
    
        // Obtener el saldo actual de la base de datos
        $querySaldo = "SELECT saldo FROM cuenta WHERE usuario_id = {$_SESSION['id_usuario']}";
        $resultadoSaldo = $conexion->query($querySaldo);
    
        if ($resultadoSaldo && $resultadoSaldo->num_rows > 0) {
            $filaSaldo = $resultadoSaldo->fetch_assoc();
            $saldo = $filaSaldo['saldo'];
    
            // Verificar si hay saldo suficiente para el retiro
            if ($monto > $saldo) {
                $_SESSION['error_saldo_insuficiente'] = true;
                $_SESSION['mensaje_error'] = "Error: Saldo insuficiente";
            } else {
                // Realizar el retiro y actualizar el saldo en la base de datos
                $nuevoSaldo = $saldo - $monto;
                $queryUpdateSaldo = "UPDATE cuenta SET saldo = $nuevoSaldo WHERE usuario_id = {$_SESSION['id_usuario']}";
                $resultadoUpdateSaldo = $conexion->query($queryUpdateSaldo);
    
                if (!$resultadoUpdateSaldo) {
                    $_SESSION['mensaje_error'] = "Error en la consulta de actualización de saldo: " . $conexion->error;
                } else {
                    // Agregar el movimiento
                    $this->agregarMovimiento("Retiro", $monto);
                    $_SESSION['mensaje_exito'] = "Retiro exitoso";
                }
            }
    
            // Liberar el resultado y cerrar la conexión
            $resultadoSaldo->free();
        } else {
            $_SESSION['mensaje_error'] = "Error en la consulta de saldo: " . $conexion->error;
        }
    
        // Redirigir a la página de contenido
        header("Location: contenido.php");
        exit();
    }
    
    
    public function transferir($monto, $cuentaDestino) {
        if ($this->verificarSesion()) {
            $conexion = $this->conexionBD->obtenerConexion();
    
            // Obtener el saldo actual de la cuenta del usuario
            $querySaldoOrigen = "SELECT saldo FROM cuenta WHERE usuario_id = {$_SESSION['id_usuario']}";
            $resultadoSaldoOrigen = $conexion->query($querySaldoOrigen);
    
            if ($resultadoSaldoOrigen && $resultadoSaldoOrigen->num_rows > 0) {
                $filaSaldoOrigen = $resultadoSaldoOrigen->fetch_assoc();
                $saldoOrigen = $filaSaldoOrigen['saldo'];
    
                // Verificar si hay saldo suficiente para la transferencia
                if ($monto > $saldoOrigen) {
                    $_SESSION['error_saldo_insuficiente'] = true;
                    $_SESSION['mensaje_error'] = "Error: Saldo insuficiente";
                } else {
                    // Realizar la transferencia y actualizar los saldos en la base de datos
                    $nuevoSaldoOrigen = $saldoOrigen - $monto;
    
                    $queryUpdateSaldoOrigen = "UPDATE cuenta SET saldo = $nuevoSaldoOrigen WHERE usuario_id = {$_SESSION['id_usuario']}";
                    $queryUpdateSaldoDestino = "UPDATE cuenta SET saldo = saldo + $monto WHERE usuario_id = $cuentaDestino";
    
                    $resultadoUpdateSaldoOrigen = $conexion->query($queryUpdateSaldoOrigen);
                    $resultadoUpdateSaldoDestino = $conexion->query($queryUpdateSaldoDestino);
    
                    if (!$resultadoUpdateSaldoOrigen || !$resultadoUpdateSaldoDestino) {
                        $_SESSION['mensaje_error'] = "Error en la consulta de actualización de saldos: " . $conexion->error;
                    } else {
                        // Agregar los movimientos a ambas cuentas
                        $this->agregarMovimiento("Transferencia a Cuenta $cuentaDestino", $monto);
                        $this->agregarMovimiento("Transferencia recibida de Cuenta {$_SESSION['id_usuario']}", $monto);
                        $_SESSION['mensaje_exito'] = "Transferencia exitosa";
                    }
                }
    
                // Liberar el resultado y cerrar la conexión
                $resultadoSaldoOrigen->free();
            } else {
                $_SESSION['mensaje_error'] = "Error en la consulta de saldo: " . $conexion->error;
            }
    
            // Redirigir a la página de contenido
            header("Location: contenido.php");
            exit();
        }
    }
    

    private function agregarMovimiento($descripcion, $monto) {
        $conexion = $this->conexionBD->obtenerConexion();
    
        try {
            $query = "INSERT INTO historial_movimientos (id_usuario, fecha_hora, descripcion, monto) VALUES ({$_SESSION['id_usuario']}, NOW(), '$descripcion', $monto)";
            $resultado = $conexion->query($query);
    
            if (!$resultado) {
                throw new Exception("Error en la consulta: " . $conexion->error);
            }
        } catch (Exception $e) {
            die("Excepción capturada: " . $e->getMessage());
        }
    }
    
    
    private function actualizarSaldoEnBaseDeDatos($nuevoSaldo, $idUsuario) {
        $conexion = $this->conexionBD->obtenerConexion();
    
        // Verificar si ya hay una fila en la tabla 'cuenta' para el usuario
        $query = "SELECT * FROM cuenta WHERE usuario_id = $idUsuario LIMIT 1";
        $resultado = $conexion->query($query);
    
        if ($resultado && $resultado->num_rows > 0) {
            // Si la fila existe, actualizar el saldo
            $queryUpdate = "UPDATE cuenta SET saldo = $nuevoSaldo WHERE usuario_id = $idUsuario";
            $resultadoUpdate = $conexion->query($queryUpdate);
    
            if (!$resultadoUpdate) {
                die("Error en la consulta de actualización de saldo: " . $conexion->error);
            }
        } else {
            // Si no hay fila, crear una nueva fila con el saldo
            $queryInsert = "INSERT INTO cuenta (saldo) VALUES ($idUsuario, $nuevoSaldo)";
            $resultadoInsert = $conexion->query($queryInsert);
    
            if (!$resultadoInsert) {
                die("Error en la consulta de inserción de saldo: " . $conexion->error);
                
            }
        }
    }
    

    private function verificarSesion() {
        if (!isset($_SESSION['id_usuario'])) {
            echo "Error: Debe iniciar sesión para realizar esta operación.";
            return false;
        }
        return true;
    }

    public function consultarSaldo($idUsuario) {
        $conexion = $this->conexionBD->obtenerConexion();
        $querySaldo = "SELECT saldo FROM cuenta WHERE usuario_id = $idUsuario";
        $resultadoSaldo = $conexion->query($querySaldo);

        if ($resultadoSaldo && $resultadoSaldo->num_rows > 0) {
            $filaSaldo = $resultadoSaldo->fetch_assoc();
            return $filaSaldo['saldo'];
        } else {
            return 0;
        }
    }
}

?>
