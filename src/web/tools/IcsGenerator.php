<?php
declare(strict_types=1);

class IcsGenerator
{
    public static function generate(EventDto $event,
        bool $includePrivateInformation = false): string
    {
        $utc      = new \DateTimeZone('UTC');
        $now      = new \DateTimeImmutable('now', $utc);
        $dtStart  = $event->eventDate->setTimezone($utc);

        if ($event->eventDurationHours !== null) {
            $seconds = (int) round($event->eventDurationHours * 3600);
            $dtEnd   = $dtStart->modify("+{$seconds} seconds");
        } else {
            $dtEnd = $dtStart->modify('+1 hour');
        }

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//EvEnSys//EvEnSys//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $event->eventGuid . '@evensys',
            'DTSTAMP:' . $now->format('Ymd\THis\Z'),
            'DTSTART:' . $dtStart->format('Ymd\THis\Z'),
            'DTEND:' . $dtEnd->format('Ymd\THis\Z'),
            'SUMMARY:' . self::escapeText($event->eventTitle),
        ];

        if ($event->eventDescription !== null && $event->eventDescription !== '') {
            $lines[] = 'DESCRIPTION:' . self::escapeText($event->eventDescription);
        }

        if ($includePrivateInformation && $event->eventLocation !== null && $event->eventLocation !== '') {
            $lines[] = 'LOCATION:' . self::escapeText($event->eventLocation);
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'foldLine'], $lines)) . "\r\n";
    }

    private static function escapeText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace(',', '\,', $text);
        $text = str_replace(["\r\n", "\r", "\n"], '\n', $text);
        return $text;
    }

    /** Fold long lines at 75 octets per RFC 5545 §3.1. */
    private static function foldLine(string $line): string
    {
        $result = '';
        while (strlen($line) > 75) {
            $result .= substr($line, 0, 75) . "\r\n ";
            $line    = substr($line, 75);
        }
        return $result . $line;
    }
}
