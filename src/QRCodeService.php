<?php

/**
 * Service de génération de QR Codes pour les tickets
 * Utilise l'API Google Charts (pas besoin de bibliothèque externe)
 */
class QRCodeService {

    /**
     * Génère une URL de QR code via Google Charts API
     * @param string $data Les données à encoder
     * @param int $size La taille du QR code en pixels
     * @return string L'URL de l'image QR code
     */
    public static function generateQRCodeURL($data, $size = 300) {
        $data = urlencode($data);
        return "https://chart.googleapis.com/chart?cht=qr&chs={$size}x{$size}&chl={$data}&choe=UTF-8";
    }

    /**
     * Génère un code unique pour un ticket
     * @param int $userId
     * @param int $eventId
     * @param int $registrationId
     * @return string Le code unique du ticket
     */
    public static function generateTicketCode($userId, $eventId, $registrationId) {
        $data = sprintf("EVENT:%d|USER:%d|REG:%d|TIME:%d",
            $eventId,
            $userId,
            $registrationId,
            time()
        );
        return base64_encode($data);
    }

    /**
     * Décode un ticket code
     * @param string $ticketCode
     * @return array|false Les données du ticket ou false si invalide
     */
    public static function decodeTicketCode($ticketCode) {
        $decoded = base64_decode($ticketCode, true);
        if ($decoded === false) {
            return false;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 4) {
            return false;
        }

        $data = [];
        foreach ($parts as $part) {
            list($key, $value) = explode(':', $part);
            $data[strtolower($key)] = $value;
        }

        return $data;
    }

    /**
     * Génère un ticket PDF (simple HTML pour l'instant)
     * @param array $event
     * @param array $user
     * @param int $registrationId
     * @return string Le HTML du ticket
     */
    public static function generateTicketHTML($event, $user, $registrationId) {
        $ticketCode = self::generateTicketCode($user['id'], $event['id'], $registrationId);
        $qrCodeURL = self::generateQRCodeURL($ticketCode, 250);

        $eventDate = date('d/m/Y', strtotime($event['event_date']));
        $eventTime = date('H:i', strtotime($event['event_date']));

        return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - ' . htmlspecialchars($event['title']) . '</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .ticket {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .ticket-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .ticket-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .ticket-header p {
            margin: 0;
            opacity: 0.9;
        }
        .ticket-body {
            padding: 30px;
        }
        .ticket-info {
            margin-bottom: 30px;
        }
        .ticket-info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .ticket-info-row:last-child {
            border-bottom: none;
        }
        .ticket-info-label {
            font-weight: bold;
            color: #666;
        }
        .ticket-info-value {
            color: #333;
        }
        .ticket-qr {
            text-align: center;
            padding: 20px 0;
            background: #f9f9f9;
            border-radius: 10px;
        }
        .ticket-qr img {
            border: 5px solid white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ticket-footer {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 12px;
        }
        .btn-print {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px auto;
            display: block;
        }
        .btn-print:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">🖨️ Imprimer le ticket</button>

    <div class="ticket">
        <div class="ticket-header">
            <h1>🎓 BDE Info Montpellier</h1>
            <p>Billet d\'entrée</p>
        </div>

        <div class="ticket-body">
            <h2 style="text-align: center; color: #333; margin-top: 0;">' . htmlspecialchars($event['title']) . '</h2>

            <div class="ticket-info">
                <div class="ticket-info-row">
                    <span class="ticket-info-label">📅 Date</span>
                    <span class="ticket-info-value">' . $eventDate . '</span>
                </div>
                <div class="ticket-info-row">
                    <span class="ticket-info-label">🕐 Heure</span>
                    <span class="ticket-info-value">' . $eventTime . '</span>
                </div>
                <div class="ticket-info-row">
                    <span class="ticket-info-label">📍 Lieu</span>
                    <span class="ticket-info-value">' . htmlspecialchars($event['location'] ?? 'À définir') . '</span>
                </div>
                <div class="ticket-info-row">
                    <span class="ticket-info-label">👤 Participant</span>
                    <span class="ticket-info-value">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</span>
                </div>
                <div class="ticket-info-row">
                    <span class="ticket-info-label">🎫 N° Ticket</span>
                    <span class="ticket-info-value">#' . str_pad($registrationId, 6, '0', STR_PAD_LEFT) . '</span>
                </div>
            </div>

            <div class="ticket-qr">
                <p style="margin: 0 0 15px 0; color: #666; font-weight: bold;">Présentez ce QR code à l\'entrée</p>
                <img src="' . $qrCodeURL . '" alt="QR Code" width="250" height="250">
                <p style="margin: 15px 0 0 0; font-size: 11px; color: #999;">Code: ' . substr($ticketCode, 0, 20) . '...</p>
            </div>
        </div>

        <div class="ticket-footer">
            <p>BDE Informatique de Montpellier</p>
            <p>99 Av. d\'Occitanie, 34090 Montpellier</p>
            <p style="margin-top: 10px;">Ce ticket est personnel et non transférable</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <a href="/account" style="color: #667eea; text-decoration: none;">← Retour à mes événements</a>
    </div>
</body>
</html>';
    }
}

?>
