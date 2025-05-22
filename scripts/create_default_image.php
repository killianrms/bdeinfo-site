<?php
// Script pour créer une image par défaut pour les événements

// Dimensions de l'image
$width = 800;
$height = 450;

// Créer une nouvelle image
$image = imagecreatetruecolor($width, $height);

// Définir les couleurs
$background = imagecolorallocate($image, 74, 144, 226); // Couleur primaire
$textColor = imagecolorallocate($image, 255, 255, 255); // Blanc
$accentColor = imagecolorallocate($image, 80, 227, 194); // Couleur secondaire

// Remplir l'arrière-plan
imagefill($image, 0, 0, $background);

// Dessiner un motif de fond
for ($i = 0; $i < $width; $i += 20) {
    for ($j = 0; $j < $height; $j += 20) {
        imagefilledrectangle($image, $i, $j, $i + 10, $j + 10, $accentColor);
    }
}

// Ajouter un texte
$text = "BDE Informatique";
$font = 5; // Police intégrée
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);
$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;

// Dessiner un rectangle semi-transparent pour le texte
imagefilledrectangle($image, 0, $y - 20, $width, $y + $textHeight + 20, imagecolorallocatealpha($image, 0, 0, 0, 80));

// Écrire le texte
imagestring($image, $font, $x, $y, $text, $textColor);

// Ajouter un texte supplémentaire
$subtext = "Événement à venir";
$subtextWidth = imagefontwidth($font - 1) * strlen($subtext);
$x = ($width - $subtextWidth) / 2;
$y = $y + $textHeight + 10;
imagestring($image, $font - 1, $x, $y, $subtext, $textColor);

// Chemin de sauvegarde
$outputPath = __DIR__ . '/../public/images/event-default.jpg';

// Sauvegarder l'image
imagejpeg($image, $outputPath, 90);
imagedestroy($image);

echo "Image par défaut créée avec succès à : $outputPath\n";