<?php
// Script pour créer un favicon à partir de l'image du BDE

// Chemin de l'image source et de destination
$sourcePath = __DIR__ . '/../public/bde.webp';
$destPath = __DIR__ . '/../public/favicon.ico';

// Vérifier si l'image source existe
if (!file_exists($sourcePath)) {
    die("Erreur : Image source non trouvée à $sourcePath\n");
}

try {
    // Créer une image à partir du fichier source
    $sourceImage = imagecreatefromwebp($sourcePath);
    
    if (!$sourceImage) {
        die("Erreur : Impossible de créer une image à partir de $sourcePath\n");
    }
    
    // Créer une image de 32x32 pixels (taille standard pour favicon)
    $faviconImage = imagecreatetruecolor(32, 32);
    
    // Redimensionner l'image source vers la taille du favicon
    imagecopyresampled(
        $faviconImage,    // Image de destination
        $sourceImage,     // Image source
        0, 0,             // Position x,y de destination
        0, 0,             // Position x,y de source
        32, 32,           // Largeur, hauteur de destination
        imagesx($sourceImage), imagesy($sourceImage) // Largeur, hauteur de source
    );
    
    // Enregistrer l'image comme favicon (ICO)
    imageico($faviconImage, $destPath);
    
    // Libérer la mémoire
    imagedestroy($sourceImage);
    imagedestroy($faviconImage);
    
    echo "Favicon créé avec succès à $destPath\n";
    
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage() . "\n");
}

// Fonction pour créer un fichier ICO (car PHP n'a pas de fonction native pour cela)
function imageico($image, $filename) {
    // Créer un fichier temporaire PNG
    $tempFile = tempnam(sys_get_temp_dir(), 'ico');
    imagepng($image, $tempFile);
    
    // Lire le contenu du fichier PNG
    $pngData = file_get_contents($tempFile);
    
    // Créer l'en-tête ICO
    $icoData = 
        "\x00\x00" .                  // Réservé (0)
        "\x01\x00" .                  // Type ICO (1)
        "\x01\x00" .                  // Nombre d'images (1)
        
        // Répertoire d'entrée pour l'image
        pack('C', 32) .               // Largeur (32 pixels)
        pack('C', 32) .               // Hauteur (32 pixels)
        "\x00" .                      // Nombre de couleurs (0 = 256)
        "\x00" .                      // Réservé (0)
        "\x01\x00" .                  // Plans de couleur (1)
        "\x20\x00" .                  // Bits par pixel (32)
        pack('V', strlen($pngData)) . // Taille des données en octets
        pack('V', 22);                // Offset des données (22 octets)
    
    // Ajouter les données PNG
    $icoData .= $pngData;
    
    // Écrire le fichier ICO
    file_put_contents($filename, $icoData);
    
    // Supprimer le fichier temporaire
    unlink($tempFile);
    
    return true;
}