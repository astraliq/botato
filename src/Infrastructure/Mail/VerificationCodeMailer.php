<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

final class VerificationCodeMailer
{
    private readonly MailerInterface $mailer;

    public function __construct(
        string $mailerDsn,
        private readonly string $fromEmail,
    ) {
        $this->mailer = new Mailer(Transport::fromDsn($mailerDsn));
    }

    public function send(string $email, string $code): void
    {
        $message = (new Email())
            ->from($this->fromEmail)
            ->to($email)
            ->subject('Код подтверждения — Botato')
            ->text("Ваш код подтверждения: {$code}\n\nКод действителен 10 минут.")
            ->html(
                '<p>Ваш код подтверждения: <strong>' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                . '<p>Код действителен 10 минут.</p>',
            );

        $this->mailer->send($message);
    }
}
