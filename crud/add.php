<?php
include 'auth.php';
include 'db.php';

// Verificar si el usuario está logueado
if(!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanitizar inputs
    $titulo = htmlspecialchars($_POST['titulo']);
    $descripcion = htmlspecialchars($_POST['descripcion']);
    $url_github = filter_var($_POST['url_github'], FILTER_SANITIZE_URL);
    $url_produccion = filter_var($_POST['url_produccion'], FILTER_SANITIZE_URL);

    // Validar imagen
    if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['imagen']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $destination = "uploads/" . $new_filename;
            
            if(move_uploaded_file($_FILES['imagen']['tmp_name'], $destination)) {
                // Usar consultas preparadas para seguridad
                $stmt = $conn->prepare("INSERT INTO proyectos (titulo, descripcion, url_github, url_produccion, imagen) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $titulo, $descripcion, $url_github, $url_produccion, $new_filename);
                
                if($stmt->execute()) {
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Error al guardar en la base de datos";
                }
            } else {
                $error = "Error al subir la imagen";
            }
        } else {
            $error = "Formato de imagen no permitido. Use JPG, JPEG, PNG o WEBP";
        }
    } else {
        $error = "Por favor seleccione una imagen válida";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Proyecto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Agregar Nuevo Proyecto</h3>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del Proyecto</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Ej: Nombre de tu proyecto" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                          placeholder="Describa el proyecto (máximo 200 palabras)" maxlength="200" required></textarea>
                                <div class="form-text">Máximo 200 caracteres</div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="url_github" class="form-label">URL de GitHub</label>
                                    <input type="url" class="form-control" id="url_github" name="url_github" placeholder="https://github.com/usuario/proyecto">
                                </div>
                                <div class="col-md-6">
                                    <label for="url_produccion" class="form-label">URL de Producción</label>
                                    <input type="url" class="form-control" id="url_produccion" name="url_produccion" placeholder="https://mi-proyecto.com">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="imagen" class="form-label">Imagen del Proyecto</label>
                                <input type="file" class="form-control" id="imagen" name="imagen" accept="image/jpeg, image/png, image/webp" required>
                                <div class="form-text">Formatos aceptados: JPG, PNG, WEBP</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Proyecto</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>