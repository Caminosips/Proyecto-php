<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'prueba';
$user = 'root';
$password = 'Mimama_123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$uploadError = '';
$uploadSuccess = '';
$searchResults = [];
$searchError = '';

// Manejo de subida de archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
    $pdfs = $_FILES['pdf'];

    // Validaciones
    $allowedTypes = ['application/pdf'];
    $maxFileSize = 50 * 1024 * 1024; // 50MB

    for ($i = 0; $i < count($pdfs['name']); $i++) {
        if (!in_array($pdfs['type'][$i], $allowedTypes)) {
            $uploadError = "Solo se permiten archivos PDF.";
            break;
        } elseif ($pdfs['size'][$i] > $maxFileSize) {
            $uploadError = "Cada archivo no debe superar 50MB.";
            break;
        } else {
            // Directorio para guardar PDFs
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Nombre original del archivo como número de documento
            $fileName = basename($pdfs['name'][$i]);
            $filePath = $uploadDir . $fileName;

            // Mover archivo
            if (move_uploaded_file($pdfs['tmp_name'][$i], $filePath)) {
                // Guardar información en la base de datos
                $stmt = $pdo->prepare("INSERT INTO usuarios (documento, pdf_path) VALUES (:documento, :pdf_path)");
                $stmt->execute([
                    ':documento' => pathinfo($fileName, PATHINFO_FILENAME), // Número de documento basado en el nombre del archivo (sin extensión)
                    ':pdf_path' => $filePath
                ]);
                $uploadSuccess = "Archivo(s) subido(s) exitosamente.";
            } else {
                $uploadError = "Error al subir el archivo: " . $fileName;
                break;
            }
        }
    }
}

// Lógica de búsqueda de documentos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_documento'])) {
    $searchDocumento = $_POST['search_documento'];

    // Consulta preparada para evitar inyección SQL
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE documento = :documento");
    $stmt->bindParam(':documento', $searchDocumento, PDO::PARAM_STR);
    $stmt->execute();

    // Obtener todos los resultados como arreglo
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar si hay resultados
    if (!$searchResults || count($searchResults) === 0) {
        $searchError = "No se encontró ningún documento con ese número.";
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Documentos PDF</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Gestor de Documentos PDF</h1>
            
            <!-- Sección de Subida de Archivos -->
            <div class="section">
                <h2>Subir Documentos</h2>
                
                <?php if ($uploadError): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($uploadError) ?></div>
                <?php endif; ?>
                
                <?php if ($uploadSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($uploadSuccess) ?></div>
                <?php endif; ?>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="pdf">Seleccionar PDFs:</label>
                        <input type="file" id="pdf" name="pdf[]" accept=".pdf" multiple required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Subir Documentos</button>
                </form>
            </div>

            <!-- Sección de Búsqueda de Documentos -->
            <div class="section">
                <h2>Buscar Documentos</h2>
                
                <?php if ($searchError): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($searchError) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="search_documento">Número de Documento:</label>
                        <input type="text" id="search_documento" name="search_documento" placeholder="Número del paciente" required>
                    </div>
                    <button type="submit" class="btn btn-secondary">Buscar</button>
                </form>

                <?php if ($searchResults && count($searchResults) > 0): ?>
                    <div class="result-section">
                        <h3>Documentos encontrados:</h3>
                        <ul>
                            <?php foreach ($searchResults as $result): ?>
                                <li>
                                    <a href="<?= htmlspecialchars($result['pdf_path']) ?>" target="_blank"><?= basename($result['pdf_path']) ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
