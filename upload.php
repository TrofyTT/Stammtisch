<?php
require_once 'config.php';
requireAuth();

header('Content-Type: application/json');

// Nur Admin kann Profilbilder für andere hochladen
$target_user_id = intval($_POST['user_id'] ?? $_SESSION['user_id']);
if ($target_user_id != $_SESSION['user_id'] && !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Keine Datei hochgeladen']);
    exit;
}

$file = $_FILES['avatar'];

// Validierung
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5 MB

if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nur Bilder (JPEG, PNG, GIF, WebP) erlaubt']);
    exit;
}

if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datei zu groß (max. 5 MB)']);
    exit;
}

// Prüfe ob es wirklich ein Bild ist
$image_info = @getimagesize($file['tmp_name']);
if ($image_info === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Bilddatei']);
    exit;
}

// Altes Profilbild löschen (falls vorhanden)
$db = getDB();
$stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$target_user_id]);
$old_avatar = $stmt->fetchColumn();

if ($old_avatar && file_exists(__DIR__ . '/uploads/avatars/' . $old_avatar)) {
    unlink(__DIR__ . '/uploads/avatars/' . $old_avatar);
}

// Dateiname generieren
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('avatar_', true) . '_' . $target_user_id . '.' . $extension;
$target_path = __DIR__ . '/uploads/avatars/' . $filename;

// Bild speichern
if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Fehler beim Speichern']);
    exit;
}

// Bild ist bereits 512x512 und rund vom Cropper, daher direkt speichern
// Falls das Bild bereits die richtige Größe hat (vom Cropper), nicht nochmal verkleinern
$already_cropped = ($image_info[0] == 512 && $image_info[1] == 512);

// Bild optimieren/verkleinern falls zu groß (nur wenn nicht bereits 512x512)
$max_dimension = 500;
if (!$already_cropped && ($image_info[0] > $max_dimension || $image_info[1] > $max_dimension)) {
    $img = null;
    switch ($image_info[2]) {
        case IMAGETYPE_JPEG:
            $img = imagecreatefromjpeg($target_path);
            break;
        case IMAGETYPE_PNG:
            $img = imagecreatefrompng($target_path);
            break;
        case IMAGETYPE_GIF:
            $img = imagecreatefromgif($target_path);
            break;
        case IMAGETYPE_WEBP:
            $img = imagecreatefromwebp($target_path);
            break;
    }
    
    if ($img) {
        $width = $image_info[0];
        $height = $image_info[1];
        
        // Seitenverhältnis beibehalten
        if ($width > $height) {
            $new_width = $max_dimension;
            $new_height = ($height / $width) * $max_dimension;
        } else {
            $new_height = $max_dimension;
            $new_width = ($width / $height) * $max_dimension;
        }
        
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        // Transparenz für PNG/GIF erhalten
        if ($image_info[2] == IMAGETYPE_PNG || $image_info[2] == IMAGETYPE_GIF) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
        }
        
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Als JPEG speichern (kleinere Dateigröße)
        $new_filename = str_replace($extension, 'jpg', $filename);
        $new_path = __DIR__ . '/uploads/avatars/' . $new_filename;
        
        imagejpeg($resized, $new_path, 85);
        imagedestroy($img);
        imagedestroy($resized);
        
        // Alte Datei löschen
        if ($new_filename != $filename) {
            unlink($target_path);
        }
        
        $filename = $new_filename;
        $target_path = $new_path;
    } else if ($already_cropped) {
        // Wenn bereits 512x512, direkt als JPEG speichern für Konsistenz
        $new_filename = str_replace($extension, 'jpg', $filename);
        $new_path = __DIR__ . '/uploads/avatars/' . $new_filename;
        
        // Bild laden und als JPEG speichern
        $img = null;
        switch ($image_info[2]) {
            case IMAGETYPE_JPEG:
                $img = imagecreatefromjpeg($target_path);
                break;
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($target_path);
                break;
            case IMAGETYPE_GIF:
                $img = imagecreatefromgif($target_path);
                break;
            case IMAGETYPE_WEBP:
                $img = imagecreatefromwebp($target_path);
                break;
        }
        
        if ($img) {
            imagejpeg($img, $new_path, 90);
            imagedestroy($img);
            unlink($target_path);
            $filename = $new_filename;
            $target_path = $new_path;
        }
    }
}

// In Datenbank speichern
$stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
$stmt->execute([$filename, $target_user_id]);

echo json_encode([
    'success' => true,
    'avatar' => $filename,
    'url' => 'uploads/avatars/' . $filename
]);

