<?php
// Script pour créer des images d'événements plus professionnelles

// Fonction pour créer une image d'événement
function createEventImage($title, $filename, $colorScheme = 'blue') {
    // Dimensions de l'image
    $width = 800;
    $height = 450;

    // Créer une nouvelle image
    $image = imagecreatetruecolor($width, $height);

    // Définir les couleurs selon le schéma
    switch ($colorScheme) {
        case 'green':
            $bgColor1 = imagecolorallocate($image, 40, 167, 69); // Vert foncé
            $bgColor2 = imagecolorallocate($image, 80, 227, 194); // Vert clair
            break;
        case 'purple':
            $bgColor1 = imagecolorallocate($image, 111, 66, 193); // Violet foncé
            $bgColor2 = imagecolorallocate($image, 189, 16, 224); // Violet clair
            break;
        case 'orange':
            $bgColor1 = imagecolorallocate($image, 253, 126, 20); // Orange foncé
            $bgColor2 = imagecolorallocate($image, 255, 193, 7); // Orange clair
            break;
        default: // blue
            $bgColor1 = imagecolorallocate($image, 0, 123, 255); // Bleu foncé
            $bgColor2 = imagecolorallocate($image, 74, 144, 226); // Bleu clair
            break;
    }
    
    $textColor = imagecolorallocate($image, 255, 255, 255); // Blanc
    $overlayColor = imagecolorallocatealpha($image, 0, 0, 0, 80); // Noir semi-transparent

    // Créer un dégradé de fond
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $r = (int)(($bgColor2 >> 16) & 0xFF) * $ratio + (int)(($bgColor1 >> 16) & 0xFF) * (1 - $ratio);
        $g = (int)(($bgColor2 >> 8) & 0xFF) * $ratio + (int)(($bgColor1 >> 8) & 0xFF) * (1 - $ratio);
        $b = (int)($bgColor2 & 0xFF) * $ratio + (int)($bgColor1 & 0xFF) * (1 - $ratio);
        
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $i, $width, $i, $color);
    }

    // Ajouter un motif
    for ($i = 0; $i < $width; $i += 40) {
        for ($j = 0; $j < $height; $j += 40) {
            imagefilledellipse($image, $i, $j, 10, 10, $overlayColor);
        }
    }

    // Ajouter une bande pour le texte
    imagefilledrectangle($image, 0, $height/3, $width, $height*2/3, $overlayColor);

    // Écrire le titre
    $font = 5; // Police intégrée
    $lines = explode(' ', wordwrap($title, 30, "\n"));
    $lineHeight = imagefontheight($font);
    $totalHeight = count($lines) * $lineHeight;
    
    $y = ($height - $totalHeight) / 2;
    
    foreach ($lines as $line) {
        $textWidth = imagefontwidth($font) * strlen($line);
        $x = ($width - $textWidth) / 2;
        imagestring($image, $font, $x, $y, $line, $textColor);
        $y += $lineHeight;
    }

    // Ajouter "BDE Informatique" en bas
    $footer = "BDE Informatique 2025-2026";
    $footerWidth = imagefontwidth($font - 1) * strlen($footer);
    $x = ($width - $footerWidth) / 2;
    $y = $height - $lineHeight - 10;
    imagestring($image, $font - 1, $x, $y, $footer, $textColor);

    // Chemin de sauvegarde
    $outputPath = __DIR__ . '/../public/images/' . $filename;

    // Sauvegarder l'image
    imagejpeg($image, $outputPath, 90);
    imagedestroy($image);

    return $outputPath;
}

// Créer des images pour différents types d'événements
$events = [
    [
        'title' => 'Soirée d\'intégration',
        'filename' => 'event-integration.jpg',
        'color' => 'blue'
    ],
    [
        'title' => 'Atelier Développement Web',
        'filename' => 'event-dev-web.jpg',
        'color' => 'green'
    ],
    [
        'title' => 'Tournoi de jeux vidéo',
        'filename' => 'event-gaming.jpg',
        'color' => 'purple'
    ],
    [
        'title' => 'Conférence Intelligence Artificielle',
        'filename' => 'event-ai.jpg',
        'color' => 'orange'
    ],
    [
        'title' => 'Hackathon BDE Info',
        'filename' => 'event-hackathon.jpg',
        'color' => 'blue'
    ]
];

// Créer chaque image
foreach ($events as $event) {
    $path = createEventImage($event['title'], $event['filename'], $event['color']);
    echo "Image créée : $path\n";
}

echo "Toutes les images d'événements ont été créées avec succès !\n";