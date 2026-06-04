<?php
// Configuración de encabezados
header("Access-Control-Allow-Origin: http://127.0.0.1/Panchielito/");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Incluir la biblioteca FPDF
require('fpdf/fpdf.php');

// Incluir archivo de conexión
require_once 'conexion.php';

// Obtener el ID de los términos y condiciones
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener los términos y condiciones de la base de datos
if ($id > 0) {
    $sql = "SELECT nombre, descripcion FROM terminos_condiciones WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $terminos = $result->fetch_assoc();
    $stmt->close();
} else {
    // Si no se proporciona un ID, obtener los términos más recientes
    $sql = "SELECT nombre, descripcion FROM terminos_condiciones ORDER BY id DESC LIMIT 1";
    $result = $mysqli->query($sql);
    $terminos = $result->fetch_assoc();
}

// Si no se encuentran términos, usar valores predeterminados
if (!$terminos) {
    $terminos = [
        'nombre' => 'Términos y Condiciones',
        'descripcion' => 'No se encontraron términos y condiciones.'
    ];
}

// Crear una clase extendida de FPDF para manejar UTF-8
class PDF_UTF8 extends FPDF {
    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        $txt = utf8_decode($txt);
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        $txt = utf8_decode($txt);
        parent::MultiCell($w, $h, $txt, $border, $align, $fill);
    }
}

// Crear el PDF
$pdf = new PDF_UTF8();
$pdf->AddPage();

// Configurar fuentes
$pdf->SetFont('Arial', 'B', 16);

// Título
$pdf->Cell(0, 10, $terminos['nombre'], 0, 1, 'C');
$pdf->Ln(5);

// Fecha de descarga
$pdf->SetFont('Arial', 'I', 10);
$fecha_descarga = date("d/m/Y H:i:s");
$pdf->Cell(0, 10, 'Fecha de descarga: ' . $fecha_descarga, 0, 1, 'R');
$pdf->Ln(5);

// Contenido de los términos y condiciones
$pdf->SetFont('Arial', '', 12);

// Procesar el texto para formatear mejor en el PDF
$texto = $terminos['descripcion'];
$lineas = explode("\n", $texto);

foreach ($lineas as $linea) {
    $linea = trim($linea);
    
    // Si la línea está vacía, agregar un espacio
    if (empty($linea)) {
        $pdf->Ln(5);
        continue;
    }
    
    // Detectar encabezados (números seguidos de punto y espacio)
    if (preg_match('/^\d+\.\s+/', $linea)) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->MultiCell(0, 10, $linea);
        $pdf->SetFont('Arial', '', 12);
        continue;
    }
    
    // Detectar elementos de lista (guiones o viñetas)
    if (substr($linea, 0, 1) === '-' || substr($linea, 0, 1) === '–') {
        $pdf->Cell(10, 10, '•', 0, 0);
        $pdf->MultiCell(0, 10, substr($linea, 1));
        continue;
    }
    
    // Texto normal
    $pdf->MultiCell(0, 10, $linea);
}

// Pie de página
$pdf->SetY(-30);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Este documento es una copia de los términos y condiciones de Panchielito.', 0, 1, 'C');
$pdf->Cell(0, 10, 'Página ' . $pdf->PageNo() . '/{nb}', 0, 0, 'C');

// Nombre del archivo
$filename = 'Terminos_y_Condiciones_Panchielito_' . date('Y-m-d') . '.pdf';

// Salida del PDF
$pdf->Output('D', $filename);

$mysqli->close();
?>