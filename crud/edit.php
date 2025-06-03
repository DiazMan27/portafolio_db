<?php
include 'auth.php';
include 'db.php';

// Verificar si el usuario está logueado
if(!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Validar ID
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
$proyecto = $conn->query("SELECT * FROM proyectos WHERE id=$id")->fetch_assoc();

// Verificar si el proyecto existe
if(!$proyecto) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanitizar inputs
    $titulo = htmlspecialchars($_POST['titulo']);
    $descripcion = htmlspecialchars($_POST['descripcion']);
    $url_github = filter_var($_POST['url_github'], FILTER_SANITIZE_URL);
    $url_produccion = filter_var($_POST['url_produccion'], FILTER_SANITIZE_URL);
    $img_sql = "";

    // Procesar imagen si se subió una nueva
    if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['imagen']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $destination = "uploads/" . $new_filename;
            
            if(move_uploaded_file($_FILES['imagen']['tmp_name'], $destination)) {
                // Eliminar la imagen anterior si existe
                if(!empty($proyecto['imagen']) && file_exists("uploads/".$proyecto['imagen'])) {
                    unlink("uploads/".$proyecto['imagen']);
                }
                $img_sql = ", imagen='$new_filename'";
            } else {
                $error = "Error al subir la imagen";
            }
        } else {
            $error = "Formato de imagen no permitido. Use JPG, JPEG, PNG o WEBP";
        }
    }

    if(empty($error)) {
        $sql = "UPDATE proyectos SET titulo=?, descripcion=?, url_github=?, url_produccion=? $img_sql WHERE id=?";
        $stmt = $conn->prepare($sql);
        
        if($img_sql) {
            $stmt->bind_param("ssssi", $titulo, $descripcion, $url_github, $url_produccion, $id);
        } else {
            $stmt->bind_param("sssi", $titulo, $descripcion, $url_github, $url_produccion, $id);
        }
        
        if($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $error = "Error al actualizar el proyecto";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Proyecto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .img-preview {
            max-width: 200px;
            max-height: 200px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Proyecto</h3>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del Proyecto</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?= htmlspecialchars($proyecto['titulo']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" 
                                          rows="4" required><?= htmlspecialchars($proyecto['descripcion']) ?></textarea>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="url_github" class="form-label">URL de GitHub</label>
                                    <input type="url" class="form-control" id="url_github" name="url_github" 
                                           value="<?= htmlspecialchars($proyecto['url_github']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="url_produccion" class="form-label">URL de Producción</label>
                                    <input type="url" class="form-control" id="url_produccion" name="url_produccion" 
                                           value="<?= htmlspecialchars($proyecto['url_produccion']) ?>">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="imagen" class="form-label">Imagen del Proyecto</label>
                                
                                <?php if(!empty($proyecto['imagen'])): ?>
                                    <div class="mb-2">
                                        <p class="mb-1">Imagen actual:</p>
                                        <img src="uploads/<?= htmlspecialchars($proyecto['imagen']) ?>" 
                                             class="img-preview" id="imagePreview">
                                    </div>
                                <?php endif; ?>
                                
                                <input type="file" class="form-control" id="imagen" name="imagen" 
                                       accept="image/jpeg, image/png, image/webp">
                                <div class="form-text">Formatos aceptados: JPG, PNG, WEBP. Máx 2MB</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Actualizar Proyecto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar vista previa de la imagen seleccionada
        document.getElementById('imagen').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('imagePreview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'imagePreview';
                        preview.className = 'img-preview';
                        event.target.parentNode.insertBefore(preview, event.target);
                    }
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>