<?php

namespace Benchero\Core\Mail;

class SmtpMailer implements MailerInterface
{
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;
    private string $fromAddress;
    private string $fromName;

    public function __construct(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password,
        string $fromAddress,
        string $fromName
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $protocol = ($this->encryption === 'tls' || $this->encryption === 'ssl') ? 'ssl://' : '';
        $port = $this->port;
        
        $socket = @fsockopen($protocol . $this->host, $port, $errno, $errstr, 10);
        if (!$socket) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }

        $this->getResponse($socket);

        $this->writeSmtp($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        
        if ($this->encryption === 'STARTTLS') {
            $this->writeSmtp($socket, "STARTTLS");
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->writeSmtp($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        if ($this->username && $this->password) {
            $this->writeSmtp($socket, "AUTH LOGIN");
            $this->writeSmtp($socket, base64_encode($this->username));
            $this->writeSmtp($socket, base64_encode($this->password));
        }

        $this->writeSmtp($socket, "MAIL FROM:<{$this->fromAddress}>");
        $this->writeSmtp($socket, "RCPT TO:<{$to}>");
        $this->writeSmtp($socket, "DATA");

        $boundary = md5(time());
        $headers  = "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . time() . '.' . uniqid() . "@benchero.co.ke>\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromAddress}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $message .= $textBody . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $htmlBody . "\r\n";
        $message .= "--{$boundary}--";

        fwrite($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
        $this->getResponse($socket);

        $this->writeSmtp($socket, "QUIT");
        fclose($socket);

        return true;
    }

    private function writeSmtp($socket, string $data): void
    {
        fwrite($socket, $data . "\r\n");
        $this->getResponse($socket);
    }

    protected function getResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            // In SMTP, the last line of a response has a space after the status code (e.g. "250 ")
            // while intermediate lines have a hyphen (e.g. "250-")
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }
}
