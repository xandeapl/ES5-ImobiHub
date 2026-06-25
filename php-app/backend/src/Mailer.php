<?php

declare(strict_types=1);

namespace ImobiHub;

use RuntimeException;

final class Mailer
{
    public function __construct(private array $config)
    {
    }

    public function send(string $to, string $subject, string $textBody, ?string $htmlBody = null): void
    {
        $host = trim((string) ($this->config['host'] ?? ''));
        $port = (int) ($this->config['port'] ?? 587);
        $secure = strtolower(trim((string) ($this->config['secure'] ?? 'tls')));
        $username = (string) ($this->config['username'] ?? '');
        $password = (string) ($this->config['password'] ?? '');
        $from = trim((string) ($this->config['from'] ?? ''));
        $fromName = trim((string) ($this->config['from_name'] ?? 'ImobiHub'));

        if ($host === '' || $username === '' || $password === '' || $from === '') {
            throw new RuntimeException('Configuração de SMTP incompleta.');
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);

        $remoteHost = $secure === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @stream_socket_client(
            $remoteHost . ':' . $port,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($socket)) {
            throw new RuntimeException('Falha ao conectar no SMTP: ' . $errstr);
        }

        stream_set_timeout($socket, 15);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO localhost', [250]);

            if ($secure === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                $cryptoOk = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoOk !== true) {
                    throw new RuntimeException('Falha ao iniciar TLS no SMTP.');
                }
                $this->command($socket, 'EHLO localhost', [250]);
            }

            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($username), [334]);
            $this->command($socket, base64_encode($password), [235]);

            $this->command($socket, 'MAIL FROM:<' . $this->sanitizeEmail($from) . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $this->sanitizeEmail($to) . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $headers = [
                'From: ' . $this->formatAddress($from, $fromName),
                'To: ' . $this->formatAddress($to, $to),
                'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
                'Date: ' . gmdate('D, d M Y H:i:s O'),
                'MIME-Version: 1.0',
            ];

            if ($htmlBody !== null) {
                $boundary = 'b_' . bin2hex(random_bytes(12));
                $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

                $payload = "--{$boundary}\r\n";
                $payload .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
                $payload .= $this->normalizeBody($textBody) . "\r\n";
                $payload .= "--{$boundary}\r\n";
                $payload .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
                $payload .= $this->normalizeBody($htmlBody) . "\r\n";
                $payload .= "--{$boundary}--\r\n";
            } else {
                $headers[] = 'Content-Type: text/plain; charset=UTF-8';
                $payload = $this->normalizeBody($textBody) . "\r\n";
            }

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $payload;
            $message = str_replace("\n.", "\n..", $message);
            fwrite($socket, $message . "\r\n.\r\n");
            $this->expect($socket, [250]);

            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function command($socket, string $command, array $expectedCodes): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expectedCodes);
    }

    private function expect($socket, array $expectedCodes): void
    {
        $line = '';
        $code = 0;

        do {
            $line = fgets($socket, 1024);
            if ($line === false) {
                throw new RuntimeException('Conexão SMTP encerrada inesperadamente.');
            }

            $code = (int) substr($line, 0, 3);
        } while (isset($line[3]) && $line[3] === '-');

        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('Resposta SMTP inesperada: ' . trim($line));
        }
    }

    private function sanitizeEmail(string $email): string
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('E-mail inválido na operação SMTP.');
        }

        return $email;
    }

    private function formatAddress(string $email, string $name): string
    {
        $safeEmail = $this->sanitizeEmail($email);
        $safeName = trim(preg_replace('/[\r\n]+/', ' ', $name) ?? '');

        if ($safeName === '') {
            return '<' . $safeEmail . '>';
        }

        return '=?UTF-8?B?' . base64_encode($safeName) . '?= <' . $safeEmail . '>';
    }

    private function normalizeBody(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        return str_replace("\n", "\r\n", $body);
    }
}
