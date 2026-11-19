<?php
require_once __DIR__ . '/password_reset_helpers.php';

function ensureRegistrationVerificationTable(mysqli $conn): bool
{
    return (bool)$conn->query("CREATE TABLE IF NOT EXISTS registration_verifications (
        verification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        code_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY email (email),
        KEY expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function sendCareerRoadmapVerificationEmail(string $email, string $code, array $settings): bool
{
    $from = trim((string)($settings['MAIL_FROM_ADDRESS'] ?? ''));
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) return false;

    $name = trim((string)($settings['MAIL_FROM_NAME'] ?? 'CareerRoadmap'));
    $subject = 'Verify your CareerRoadmap email';
    $body = "Your CareerRoadmap verification code is: {$code}\n\n" .
        "Enter this code on the registration page within 10 minutes. You have up to 5 attempts.\n\n" .
        "If you did not request this code, you can ignore this email.";
    return careerRoadmapSendSmtpEmail($email, $subject, $body, $settings);
}
