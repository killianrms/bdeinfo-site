<?php

require_once __DIR__ . '/../config/email.php';

class EmailService {

    /**
     * Envoie un email avec template HTML
     */
    public static function send($to, $subject, $htmlBody, $textBody = null) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
            'Reply-To: ' . EMAIL_REPLY_TO,
            'X-Mailer: PHP/' . phpversion()
        ];

        $success = mail($to, $subject, $htmlBody, implode("\r\n", $headers));

        if (!$success) {
            error_log("Échec d'envoi d'email à $to avec sujet: $subject");
        }

        return $success;
    }

    /**
     * Template de base pour les emails
     */
    private static function getEmailTemplate($title, $content) {
        return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0; margin: -20px -20px 20px -20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 20px 0; }
        .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .button:hover { background: #764ba2; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align: center; }
        .info-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 15px; margin: 15px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 BDE Info Montpellier</h1>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p>Ce message a été envoyé par le BDE Informatique de Montpellier</p>
            <p>99 Av. d\'Occitanie, 34090 Montpellier</p>
            <p><a href="https://www.instagram.com/bde_info_mtp/">Instagram</a> | <a href="mailto:bdeinfomontpellier@gmail.com">Contact</a></p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Email de confirmation d'inscription à un événement
     */
    public static function sendEventRegistrationConfirmation($userEmail, $userName, $eventTitle, $eventDate, $eventLocation, $price, $isPaid) {
        $subject = "Confirmation d'inscription - $eventTitle";

        $statusText = $isPaid ? '✅ Paiement confirmé' : ($price > 0 ? '⏳ En attente de paiement' : '✅ Inscription confirmée');
        $priceText = $price > 0 ? number_format($price, 2, ',', ' ') . ' €' : 'Gratuit';

        $content = '
            <h2>Bonjour ' . htmlspecialchars($userName) . ',</h2>
            <p>Votre inscription à l\'événement <strong>' . htmlspecialchars($eventTitle) . '</strong> a bien été enregistrée !</p>

            <div class="info-box">
                <p><strong>📅 Date :</strong> ' . date('d/m/Y à H:i', strtotime($eventDate)) . '</p>
                <p><strong>📍 Lieu :</strong> ' . htmlspecialchars($eventLocation) . '</p>
                <p><strong>💰 Prix :</strong> ' . $priceText . '</p>
                <p><strong>📊 Statut :</strong> ' . $statusText . '</p>
            </div>

            <p>Nous avons hâte de vous voir à cet événement !</p>

            <a href="' . BASE_URL . '/account" class="button">Voir mes inscriptions</a>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                Pour toute question, n\'hésitez pas à nous contacter à bdeinfomontpellier@gmail.com
            </p>
        ';

        $html = self::getEmailTemplate($subject, $content);
        return self::send($userEmail, $subject, $html);
    }

    /**
     * Email de rappel 24h avant un événement
     */
    public static function sendEventReminder($userEmail, $userName, $eventTitle, $eventDate, $eventLocation) {
        $subject = "Rappel : $eventTitle demain !";

        $content = '
            <h2>Bonjour ' . htmlspecialchars($userName) . ',</h2>
            <p>Un petit rappel : l\'événement <strong>' . htmlspecialchars($eventTitle) . '</strong> a lieu demain !</p>

            <div class="info-box">
                <p><strong>📅 Date :</strong> ' . date('d/m/Y à H:i', strtotime($eventDate)) . '</p>
                <p><strong>📍 Lieu :</strong> ' . htmlspecialchars($eventLocation) . '</p>
            </div>

            <p>N\'oubliez pas d\'être à l\'heure ! On vous attend avec impatience 🎉</p>

            <a href="' . BASE_URL . '/account" class="button">Voir les détails</a>
        ';

        $html = self::getEmailTemplate($subject, $content);
        return self::send($userEmail, $subject, $html);
    }

    /**
     * Email de confirmation de paiement
     */
    public static function sendPaymentConfirmation($userEmail, $userName, $eventTitle, $amount, $transactionId) {
        $subject = "Paiement confirmé - $eventTitle";

        $content = '
            <h2>Bonjour ' . htmlspecialchars($userName) . ',</h2>
            <p>Votre paiement pour l\'événement <strong>' . htmlspecialchars($eventTitle) . '</strong> a été validé avec succès !</p>

            <div class="info-box">
                <p><strong>💰 Montant :</strong> ' . number_format($amount, 2, ',', ' ') . ' €</p>
                <p><strong>🔖 Référence :</strong> ' . htmlspecialchars($transactionId) . '</p>
                <p><strong>✅ Statut :</strong> Paiement confirmé</p>
            </div>

            <p>Votre place est maintenant réservée. Nous vous enverrons un rappel avant l\'événement.</p>

            <a href="' . BASE_URL . '/account" class="button">Voir mes événements</a>
        ';

        $html = self::getEmailTemplate($subject, $content);
        return self::send($userEmail, $subject, $html);
    }

    /**
     * Email de bienvenue après inscription
     */
    public static function sendWelcomeEmail($userEmail, $userName) {
        $subject = "Bienvenue au BDE Info Montpellier ! 🎉";

        $content = '
            <h2>Bienvenue ' . htmlspecialchars($userName) . ' !</h2>
            <p>Merci de vous être inscrit sur le site du BDE Info Montpellier.</p>

            <p>Avec votre compte, vous pouvez :</p>
            <ul>
                <li>📅 Découvrir et vous inscrire aux événements</li>
                <li>🎫 Adhérer au BDE pour profiter de réductions</li>
                <li>🏆 Participer au classement et gagner des points</li>
                <li>👤 Gérer votre profil et vos inscriptions</li>
            </ul>

            <a href="' . BASE_URL . '/events" class="button">Voir les événements</a>

            <p style="margin-top: 30px;">
                Suivez-nous sur <a href="https://www.instagram.com/bde_info_mtp/">Instagram</a> pour ne rien manquer !
            </p>
        ';

        $html = self::getEmailTemplate($subject, $content);
        return self::send($userEmail, $subject, $html);
    }

    /**
     * Email d'annulation d'inscription
     */
    public static function sendCancellationConfirmation($userEmail, $userName, $eventTitle) {
        $subject = "Annulation d'inscription - $eventTitle";

        $content = '
            <h2>Bonjour ' . htmlspecialchars($userName) . ',</h2>
            <p>Votre inscription à l\'événement <strong>' . htmlspecialchars($eventTitle) . '</strong> a été annulée.</p>

            <p>Si vous changez d\'avis, vous pouvez vous réinscrire à tout moment (dans la limite des places disponibles).</p>

            <a href="' . BASE_URL . '/events" class="button">Voir les événements</a>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                Cette annulation a été effectuée depuis votre compte.
            </p>
        ';

        $html = self::getEmailTemplate($subject, $content);
        return self::send($userEmail, $subject, $html);
    }
}

?>
