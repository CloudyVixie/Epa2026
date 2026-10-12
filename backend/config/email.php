
<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->safeLoad();

function enviarEmailCodigo(string $destinatario, string $codigo): bool
{
    $usuario = $_ENV['GMAIL_USER'] ?? '';
    $senhaApp = $_ENV['GMAIL_APP_PASSWORD'] ?? '';

    if ($usuario === '' || $senhaApp === '') {
        error_log('Credenciais SMTP do Gmail não configuradas.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $usuario;
        $mail->Password = str_replace(' ', '', $senhaApp);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($usuario, 'Epa2026');
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = 'Seu código de verificação - Epa2026';

        $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:480px;margin:auto">
                <h2>Confirme seu e-mail</h2>
                <p>Use o código abaixo para concluir seu cadastro:</p>
                <div style="font-size:32px;font-weight:bold;letter-spacing:8px">
                    ' . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '
                </div>
                <p>O código expira em 15 minutos.</p>
                <p>Se você não solicitou esse cadastro, ignore esta mensagem.</p>
            </div>
        ';

        $mail->AltBody =
            "Seu código de verificação Epa2026 é: {$codigo}. " .
            'Ele expira em 15 minutos.';

        $mail->send();

        return true;
    } catch (\Throwable $erro) {
        error_log('Falha ao enviar e-mail de verificação: ' . $erro->getMessage());
        return false;
    }
}
