<?php

namespace Benchero\Core\Mail;

class LogMailer implements MailerInterface
{
    private string $logPath;

    public function __construct(string $logPath = __DIR__ . '/../../../storage/logs/mail.log')
    {
        $this->logPath = $logPath;
    }

    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $timestamp = date('Y-m-d H:i:s');
        $divider = str_repeat('-', 40);
        
        $entry = <<<LOG
[{$timestamp}] NEW MAIL
To: {$to}
Subject: {$subject}
Text Body: 
{$textBody}
HTML Body: 
{$htmlBody}
{$divider}

LOG;

        // Ensure directory exists
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($this->logPath, $entry, FILE_APPEND) !== false;
    }
}
