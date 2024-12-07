<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'prueba';
$user = 'root';
$password = 'Mimama_123';

try {
    // Conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Verificar si se proporcionó un número de documento
if (isset($_GET['doc'])) {
    $documento = $_GET['doc'];

    // Consulta preparada para obtener la ruta del PDF
    $stmt = $pdo->prepare("SELECT pdf_path FROM usuarios WHERE documento = :documento");
    $stmt->bindParam(':documento', $documento, PDO::PARAM_STR);
    $stmt->execute();

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado && file_exists($resultado['pdf_path'])) {
        // Descargar el archivo
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $documento . '.pdf"');
        readfile($resultado['pdf_path']);
        exit;
    } else {
        die("Documento no encontrado o archivo no existe.");
    }
} else {
    die("No se proporcionó un número de documento.");
}