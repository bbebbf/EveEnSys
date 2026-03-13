<?php
declare(strict_types=1);

function html_out(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function datetime_out(\DateTimeInterface $aDate, string $format): string
{
    return $aDate !== null ? $aDate->format($format) : '';
}

function event_date_out(\DateTimeInterface $aDate): string
{
    return datetime_out($aDate, 'd.m. \u\m H:i \U\h\r');
}

function default_datetime_out(\DateTimeInterface $aDate): string
{
    return datetime_out($aDate, 'd.m.y / H:i:s');
}
