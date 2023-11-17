<?php
require_once('../includes/ManejadorCuenta.php');
require_once('../includes/ConexionBD.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header("location: index.php");
}

$idUsuario = $_SESSION['id_usuario'];

$conexionBD = new ConexionBD();
$conexion = $conexionBD->obtenerConexion(); 

// Consultar el saldo actual en la base de datos
$querySaldo = "SELECT saldo FROM cuenta WHERE usuario_id = $idUsuario";
$resultadoSaldo = $conexion->query($querySaldo);

if ($resultadoSaldo && $resultadoSaldo->num_rows > 0) {
    $filaSaldo = $resultadoSaldo->fetch_assoc();
    $saldoActual = $filaSaldo['saldo'];
} else {
    $saldoActual = 0;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manejadorCuenta = new ManejadorCuenta(new ConexionBD());

    if (isset($_POST['depositar'])) {
        $monto = $_POST['monto'];
        $manejadorCuenta->depositar($monto);
    } elseif (isset($_POST['retirar'])) {
        $monto = $_POST['monto'];
        $manejadorCuenta->retirar($monto);
    } elseif (isset($_POST['transferir'])) {
        // Agregar el código para transferir saldo
        $cuentaDestino = $_POST['cuenta_destino'];
        $monto = $_POST['monto'];
        $manejadorCuenta->transferir($monto, $cuentaDestino);
    }

    // Puedes agregar más acciones según sea necesario...
}

if (isset($_POST['cerrar_sesion'])) {
    // Cerrar sesión y redirigir al inicio de sesión
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Cuenta Bancaria</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <script src="../js/script.js"></script>
    <!-- Agrega esto en la sección head de tu HTML -->
</head>
<body>
    <div class="cajero">
            <div class="cerrar" >
            <form action="contenido.php" method="post">
                <button class="btncerrar" type="submit" name="cerrar_sesion">Cerrar Sesión</button>
            </form>
            </div>
        <h1>Tu Cuenta Bancaria</h1>
        
        <div id='saldo'>
            <p>Saldo Disponible: $<span id='saldoActual'><?php echo $saldoActual; ?></span></p>
        </div>

        <?php if (isset($_SESSION['error_saldo_insuficiente']) && $_SESSION['error_saldo_insuficiente']) : ?>
            <div class='mensaje-error'>Saldo insuficiente. No se pudo realizar la operación.</div>
            <?php unset($_SESSION['error_saldo_insuficiente']); ?>
        <?php endif; ?>

        <form action="contenido.php" method="post">
            <div>
                <label for="monto">Monto:</label>
                <input type="text" name="monto" id="monto" placeholder="" required>
            </div>
            <button class="botondepositar" type="submit" name="depositar" onclick="recargarPagina()">Depositar</button>
            <button class="botonretirar" type="submit" name="retirar" >Retirar</button>

            <!-- Abrir el formulario de transferencia -->
            <button class="botontransferir" type="button" onclick="mostrarFormularioTransferencia()">Transferir</button>

            <div class="transferir" id="formularioTransferencia" style="display: none;">
                <h2>Formulario de Transferencia</h2>
                <form action="contenido.php" method="post">
                    <div>
                        <label for="cuenta_destino">Cuenta Destino:</label>
                        <input type="text" name="cuenta_destino" id="cuenta_destino" placeholder="Ingrese la cuenta destino">
                    </div>

                    <button type="submit" name="transferir">Transferir</button>
                    <button type="button" onclick="ocultarFormularioTransferencia()">Cancelar</button>
                </form>
            </div>
        </form>
           
        <div id="contenedorTabla">
            <table id="historialMovimientos">
                <caption>Historial de Movimientos</caption>
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Descripción</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <?php
                    // Consultar el historial de movimientos
                    $query = "SELECT * FROM historial_movimientos WHERE id_usuario = $idUsuario ORDER BY fecha_hora DESC";
                    $resultado = $conexion->query($query);

                    if ($resultado) {
                        while ($movimiento = $resultado->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$movimiento['fecha_hora']}</td>";
                            echo "<td>{$movimiento['descripcion']}</td>";
                            echo "<td>{$movimiento['monto']}</td>";
                            echo "</tr>";
                        }
                        $resultado->free();
                    } else {
                        die("Error en la consulta: " . $conexion->error);
                    }
                   ?>
                </tbody>
            </table>
        </div>
          
    </div>
</body>
</html>
