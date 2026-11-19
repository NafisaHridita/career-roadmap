<?php
function careerRoadmapEnv(): array
{
    static $settings = null;
    if ($settings === null) {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        $settings = is_file($file) ? (parse_ini_file($file, false, INI_SCANNER_RAW) ?: []) : [];
    }
    return $settings;
}

function ensurePasswordResetTable(mysqli $conn): bool
{
    return (bool)$conn->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        reset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY user_id (user_id),
        KEY token_hash (token_hash),
        KEY expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Send a plain-text message through an authenticated SMTP server. */
function careerRoadmapSendSmtpEmail(string $to, string $subject, string $body, array $settings): bool
{
    $host = trim((string)($settings['MAIL_SMTP_HOST'] ?? ''));
    $port = (int)($settings['MAIL_SMTP_PORT'] ?? 587);
    $username = trim((string)($settings['MAIL_SMTP_USERNAME'] ?? ''));
    $password = (string)($settings['MAIL_SMTP_PASSWORD'] ?? '');
    $from = trim((string)($settings['MAIL_FROM_ADDRESS'] ?? ''));
    $fromName = trim((string)($settings['MAIL_FROM_NAME'] ?? 'CareerRoadmap'));
    if ($host === '' || $port < 1 || $port > 65535 || $username === '' || $password === '' ||
        !filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;

    $security = strtolower(trim((string)($settings['MAIL_SMTP_SECURITY'] ?? 'tls')));
    $target = ($security === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($target, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) return false;
    stream_set_timeout($socket, 15);
    $read = static function () use ($socket): array {
        $response = '';
        do {
            $line = fgets($socket, 515);
            if ($line === false) return [0, $response];
            $response .= $line;
        } while (strlen($line) >= 4 && $line[3] === '-');
        return [(int)substr($line, 0, 3), $response];
    };
    $command = static function (string $line, array $expected) use ($socket, $read): bool {
        if (fwrite($socket, $line . "\r\n") === false) return false;
        [$code] = $read();
        return in_array($code, $expected, true);
    };
    [$greeting] = $read();
    $ok = $greeting === 220 && $command('EHLO careerroadmap.local', [250]);
    if ($ok && $security === 'tls') {
        $ok = $command('STARTTLS', [220]) && @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($ok) $ok = $command('EHLO careerroadmap.local', [250]);
    } elseif ($security !== 'ssl' && $security !== 'none') {
        $ok = false;
    }
    if ($ok) $ok = $command('AUTH LOGIN', [334]);
    if ($ok) $ok = $command(base64_encode($username), [334]);
    if ($ok) $ok = $command(base64_encode($password), [235]);
    if ($ok) $ok = $command('MAIL FROM:<' . $from . '>', [250]);
    if ($ok) $ok = $command('RCPT TO:<' . $to . '>', [250, 251]);
    if ($ok) $ok = $command('DATA', [354]);
    if ($ok) {
        $safeName = str_replace(["\r", "\n", '"'], '', $fromName);
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $message = "From: \"{$safeName}\" <{$from}>\r\n" .
            "To: <{$to}>\r\nSubject: {$encodedSubject}\r\n" .
            'Date: ' . date(DATE_RFC2822) . "\r\nMIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" .
            str_replace(["\r\n", "\r"], "\n", $body);
        $message = str_replace("\n", "\r\n", $message);
        $message = preg_replace('/(^|\r\n)\./', '$1..', $message) ?? $message;
        $ok = fwrite($socket, $message . "\r\n.\r\n") !== false;
        if ($ok) { [$code] = $read(); $ok = $code === 250; }
    }
    if ($ok) $command('QUIT', [221]);
    fclose($socket);
    return $ok;
}

function sendCareerRoadmapResetEmail(string $email, string $url, array $settings): bool
{
    $from = trim((string)($settings['MAIL_FROM_ADDRESS'] ?? ''));
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) return false;
    $name = trim((string)($settings['MAIL_FROM_NAME'] ?? 'CareerRoadmap'));
    $subject = 'Reset your CareerRoadmap password';
    $body = "We received a request to reset your CareerRoadmap password.\n\n" .
        "Use this one-time link within 60 minutes:\n{$url}\n\n" .
        "If you did not request this, ignore this email.";
    return careerRoadmapSendSmtpEmail($email, $subject, $body, $settings);
}
