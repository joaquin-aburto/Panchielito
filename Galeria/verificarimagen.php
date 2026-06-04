<?php
if (isset($_GET['ruta'])) {
    $ruta = $_GET['ruta'];
    if (file_exists('../imgsProductos/' . $ruta)) {
        echo 'existe';
    } else {
        echo 'no_existe';
    }
} else {
    echo 'no_existe';
}
?>
