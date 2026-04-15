<?php
declare(strict_types=1);

function html_out(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function datetime_out(\DateTimeInterface $aDate, string $format): string
{
    if ($aDate == null) {
        return '';
    }

    $fmt = new IntlDateFormatter(
        'de_DE',
        IntlDateFormatter::SHORT,
        IntlDateFormatter::NONE,
        $aDate->getTimezone(),
        null,
        $format
    );
    return $fmt->format($aDate);
}

function event_datetime_out(\DateTimeInterface $aDate): string
{
    return datetime_out($aDate, "EEEE, dd.MM. 'um' HH:mm 'Uhr'");
}

function enrollment_date_out(\DateTimeInterface $aDate): string
{
    return datetime_out($aDate, "dd.MM.yy");
}

function enrollment_datetime_out(\DateTimeInterface $aDate): string
{
    return datetime_out($aDate, "dd.MM.yy / HH:mm");
}

function default_datetime_out(\DateTimeInterface $aDate): string
{
    return datetime_out($aDate, "dd.MM.yy / HH:mm:ss");
}

function get_base_url(): string
{
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}