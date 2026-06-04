<?php
// Este script debe ejecutarse mediante un cron job diario

// Incluir el archivo de conexión
require_once '../controladoresPrincipales/conexion.php';

// Obtener ventas pendientes con fecha de vencimiento pasada
$stmt = $mysqli->prepare("SELECT id FROM ventas WHERE estado = 'pendiente' AND metodo_pago = 'OXXO' AND fecha_pago < NOW()");
$stmt->execute();
$result = $stmt->get_result();

$ventasCanceladas = 0;

while ($venta = $result->fetch_assoc()) {
    $ventaId = $venta['id'];
    
    // Iniciar transacción
    $mysqli->begin_transaction();
    
    try {
        // Obtener los detalles de la venta para devolver el stock
        $stmt = $mysqli->prepare("SELECT producto_id, cantidad FROM detalles_venta WHERE venta_id = ?");
        $stmt->bind_param("i", $ventaId);
        $stmt->execute();
        $detalles = $stmt->get_result();
        
        // Devolver el stock de cada producto
        while ($detalle = $detalles->fetch_assoc()) {
            $stmt = $mysqli->prepare("UPDATE productos SET unidades_disponibles = unidades_disponibles + ? WHERE id = ?");
            $stmt->bind_param("ii", $detalle['cantidad'], $detalle['producto_id']);
            $stmt->execute();
        }
        
        // Actualizar el estado de la venta a cancelada
        $stmt = $mysqli->prepare("UPDATE ventas SET estado = 'cancelada' WHERE id = ?");
        $stmt->bind_param("i", $ventaId);
        $stmt->execute();
        
        // Confirmar transacción
        $mysqli->commit();
        $ventasCanceladas++;
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $mysqli->rollback();
        error_log("Error al cancelar la venta ID $ventaId: " . $e->getMessage());
    }
}

echo "Proceso completado. Se cancelaron $ventasCanceladas ventas vencidas.";
?>