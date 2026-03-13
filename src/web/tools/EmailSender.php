<?php
declare(strict_types=1);

class EmailSender
{
    private string $noReplyAddress;

    public function __construct(string $noReplyAddress)
    {
        $this->noReplyAddress = $noReplyAddress;
    }

    public function sendAccountActivationEmail(string $toEmail, string $toName, string $activationLink): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($appTitle . '-Konto aktivieren')
            ->setTextBody(
                "Hallo {$toName},\r\n\r\n"
                . "Vielen Dank für Ihre Registrierung bei {$appTitle}.\r\n\r\n"
                . "Klicken Sie auf den folgenden Link, um Ihr Konto zu aktivieren (gültig für 24 Stunden):\r\n"
                . $activationLink . "\r\n\r\n"
                . "Falls Sie sich nicht registriert haben, können Sie diese E-Mail ignorieren.\r\n"
            )
            ->send();
    }

    public function sendEventCreatedEmail(string $toEmail, string $toName, string $eventTitle, string $eventDate, string $eventLink, EventDto $event): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject('Veranstaltung erstellt: ' . $eventTitle)
            ->setTextBody(
                "Hallo {$toName},\r\n\r\n"
                . "Ihre Veranstaltung wurde erfolgreich erstellt.\r\n\r\n"
                . "Titel: {$eventTitle}\r\n"
                . "Datum: {$eventDate}\r\n\r\n"
                . "Link zur Veranstaltung:\r\n"
                . $eventLink . "\r\n\r\n"
                . "-- {$appTitle}\r\n"
            )
            ->addAttachment(IcsGenerator::generate($event), 'veranstaltung.ics', 'text/calendar')
            ->send();
    }

    public function sendEventDeletedEmail(string $toEmail, string $toName, string $eventTitle): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject('Veranstaltung gelöscht: ' . $eventTitle)
            ->setTextBody(
                "Hallo {$toName},\r\n\r\n"
                . "Ihre Veranstaltung \"{$eventTitle}\" wurde gelöscht.\r\n\r\n"
                . "-- {$appTitle}\r\n"
            )
            ->send();
    }

    public function sendEnrolledEmail(string $toEmail, string $toName, string $enrolleeName, bool $isSelf, string $eventTitle, string $eventDate, string $eventLink, EventDto $event): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();
        $who      = $isSelf ? 'Sie wurden' : "\"{$enrolleeName}\" wurde";

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject('Anmeldung bestätigt: ' . $eventTitle)
            ->setTextBody(
                "Hallo {$toName},\r\n\r\n"
                . "{$who} erfolgreich für die folgende Veranstaltung angemeldet:\r\n\r\n"
                . "Titel: {$eventTitle}\r\n"
                . "Datum: {$eventDate}\r\n\r\n"
                . "Link zur Veranstaltung:\r\n"
                . $eventLink . "\r\n\r\n"
                . "-- {$appTitle}\r\n"
            )
            ->addAttachment(IcsGenerator::generate($event), 'veranstaltung.ics', 'text/calendar')
            ->send();
    }

    public function sendUnenrolledEmail(string $toEmail, string $toName, string $enrolleeName, bool $isSelf, string $eventTitle): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();
        $who      = $isSelf ? 'Sie wurden' : "\"{$enrolleeName}\" wurde";

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject('Abmeldung: ' . $eventTitle)
            ->setTextBody(
                "Hallo {$toName},\r\n\r\n"
                . "{$who} von der folgenden Veranstaltung abgemeldet:\r\n\r\n"
                . "Titel: {$eventTitle}\r\n\r\n"
                . "-- {$appTitle}\r\n"
            )
            ->send();
    }

    public function sendProfileDeletedEmail(string $toEmail, string $toName): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($appTitle . '-Profil gelöscht')
            ->setTextBody(
                "Hallo {$toName},\r\n\r\n"
                . "Ihr Profil bei {$appTitle} wurde gelöscht.\r\n\r\n"
                . "Alle Ihre Daten wurden entfernt.\r\n\r\n"
                . "-- {$appTitle}\r\n"
            )
            ->send();
    }

    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($appTitle . '-Passwort zurücksetzen')
            ->setTextBody(
                "Hallo {$toName},\r\n\r\n"
                . "Sie haben eine Passwortzurücksetzung für Ihr {$appTitle}-Konto angefordert.\r\n\r\n"
                . "Klicken Sie auf den folgenden Link, um ein neues Passwort festzulegen (gültig für 1 Stunde):\r\n"
                . $resetLink . "\r\n\r\n"
                . "Falls Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.\r\n"
            )
            ->send();
    }
}
