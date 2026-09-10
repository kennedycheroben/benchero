<?php

namespace Benchero\Core\Mail;

interface MailerInterface
{
    /**
     * Send an email.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content
     * @param string $textBody Plain text fallback
     * @return bool True if successfully accepted for delivery
     */
    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool;
}
