<?php
declare(strict_types=1);

class EmailGenerator
{
    private string $fromAddress;
    private string $appTitleShort;
    private string $appTitleLong;
    private string $navbarColor;
    private EmailSenderInterface $emailSender;

    public function __construct(EmailSenderInterface $emailSender, string $fromAddress)
    {
        $this->emailSender    = $emailSender;
        $this->fromAddress    = $fromAddress;
        $this->appTitleShort  = htmlspecialchars(APP_CONFIG->getAppTitleShort());
        $this->appTitleLong   = htmlspecialchars(APP_CONFIG->getAppTitleLong());
        $this->navbarColor    = htmlspecialchars(APP_CONFIG->getNavbarColor());
    }

    public function sendAccountActivationEmail(string $toEmail, string $toName, string $activationLink): bool
    {
        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Vielen Dank für deine Registrierung bei {$this->appTitleShort}.")
            . $this->paragraph("Klicke auf die Schaltfläche, um dein Konto zu aktivieren. Der Link ist <strong>"
              . htmlspecialchars((string)APP_CONFIG->getActivationTokenValidityHours()) . " Stunden</strong> gültig.")
            . $this->button($activationLink, 'Konto aktivieren')
            . $this->paragraph('Falls du dich nicht registriert hast, kannst du diese E-Mail ignorieren.', true);

        $email = (new Email())
            ->setFrom($this->fromAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($this->getEmailSubject('Konto aktivieren'))
            ->setHtmlBody($this->wrapHtml('Konto aktivieren', $content));

        return $this->emailSender->send($email);
    }

    public function sendEventCreatedEmail(string $toEmail, string $toName, string $eventTitle, string $eventDate, string $eventLink, EventDto $event): bool
    {
        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph('Deine Veranstaltung wurde erfolgreich erstellt.')
            . $this->details(['Titel' => $eventTitle, 'Datum' => $eventDate])
            . $this->button($eventLink, 'Zur Veranstaltung');

        $email = (new Email())
            ->setFrom($this->fromAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($this->getEmailSubject('Veranstaltung erstellt: ' . $eventTitle))
            ->setHtmlBody($this->wrapHtml('Veranstaltung erstellt', $content))
            ->addAttachment(IcsGenerator::generate($event, true), FileTools::sanitizeFileName($eventTitle . '.ics'), 'text/calendar');

        return $this->emailSender->send($email);
    }

    public function sendEventDeletedEmail(string $toEmail, string $toName, string $eventTitle, ?string $ccEmail = null, ?string $ccName = null): bool
    {
        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Deine Veranstaltung <strong>" . htmlspecialchars($eventTitle) . "</strong> wurde gelöscht.");

        $email = (new Email())
            ->setFrom($this->fromAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($this->getEmailSubject('Veranstaltung gelöscht: ' . $eventTitle))
            ->setHtmlBody($this->wrapHtml('Veranstaltung gelöscht', $content));

        if ($ccEmail !== null) {
            $email->addCc($ccEmail, $ccName ?? '');
        }

        return $this->emailSender->send($email);
    }

    public function sendEnrolledEmail(string $toEmail, string $toName, string $enrolleeName, bool $isSelf, string $eventTitle, string $eventDate, string $eventLink, EventDto $event, ?string $ccEmail = null, ?string $ccName = null): bool
    {
        $who      = $isSelf ? 'Du wurdest' : '<strong>' . htmlspecialchars($enrolleeName) . '</strong> wurde';

        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("{$who} erfolgreich für die folgende Veranstaltung angemeldet:")
            . $this->details(['Titel' => $eventTitle, 'Datum' => $eventDate])
            . $this->button($eventLink, 'Zur Veranstaltung');

        $email = (new Email())
            ->setFrom($this->fromAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($this->getEmailSubject('Anmeldung bestätigt: ' . $eventTitle))
            ->setHtmlBody($this->wrapHtml('Anmeldung bestätigt', $content))
            ->addAttachment(IcsGenerator::generate($event, true), FileTools::sanitizeFileName($eventTitle . '.ics'), 'text/calendar');

        if ($ccEmail !== null) {
            $email->addCc($ccEmail, $ccName ?? '');
        }

        return $this->emailSender->send($email);
    }

    public function sendUnenrolledEmail(string $toEmail, string $toName, string $enrolleeName, bool $isSelf, string $eventTitle, ?string $ccEmail = null, ?string $ccName = null, ?string $cc2Email = null, ?string $cc2Name = null): bool
    {
        $who      = $isSelf ? 'Du wurdest' : '<strong>' . htmlspecialchars($enrolleeName) . '</strong> wurde';

        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("{$who} von der folgenden Veranstaltung abgemeldet:")
            . $this->details(['Titel' => $eventTitle]);

        $email = (new Email())
            ->setFrom($this->fromAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($this->getEmailSubject('Abmeldung: ' . $eventTitle))
            ->setHtmlBody($this->wrapHtml('Abmeldung', $content));

        if ($ccEmail !== null) {
            $email->addCc($ccEmail, $ccName ?? '');
        }

        if ($cc2Email !== null) {
            $email->addCc($cc2Email, $cc2Name ?? '');
        }

        return $this->emailSender->send($email);
    }

    public function sendAdminRoleGrantedEmail(string $toEmail, string $toName): bool
    {
        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Dir wurden bei <strong>{$this->appTitleShort}</strong> Administrator-Rechte erteilt.")
            . $this->paragraph('Damit hast du jetzt Zugriff auf den Administrator-Bereich.', true);

        $email = (new Email())
            ->setFrom($this->fromAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($this->getEmailSubject('Administrator-Rechte vergeben'))
            ->setHtmlBody($this->wrapHtml('Administrator-Rechte vergeben', $content));

        return $this->emailSender->send($email);
    }

    public function sendAdminRoleRevokedEmail(string $toEmail, string $toName): bool
    {
        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Dir wurden deine Administrator-Rechte bei <strong>{$this->appTitleShort}</strong> entzogen.")
            . $this->paragraph('Du hast nun keinen Zugriff mehr auf den Administrator-Bereich.', true);

        $email = (new Email())
            ->setFrom($this->fromAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($this->getEmailSubject('Administrator-Rechte entzogen'))
            ->setHtmlBody($this->wrapHtml('Administrator-Rechte entzogen', $content));

        return $this->emailSender->send($email);
    }

    public function sendProfileDeletedEmail(string $toEmail, string $toName, ?string $ccEmail = null, ?string $ccName = null): bool
    {
        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Dein Profil bei {$this->appTitleShort} wurde gelöscht. Alle deine Daten wurden entfernt.");

        $email = (new Email())
            ->setFrom($this->fromAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($this->getEmailSubject('Profil gelöscht'))
            ->setHtmlBody($this->wrapHtml('Profil gelöscht', $content));

        if ($ccEmail !== null) {
            $email->addCc($ccEmail, $ccName ?? '');
        }

        return $this->emailSender->send($email);
    }

    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool
    {
        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Du hast eine Passwortzurücksetzung für dein {$this->appTitleShort}-Konto angefordert.")
            . $this->paragraph("Klicke auf die Schaltfläche, um ein neues Passwort festzulegen. Der Link ist <strong>"
              . htmlspecialchars((string)APP_CONFIG->getPasswordResetTokenValidityHours()) . " Stunden</strong> gültig.")
            . $this->button($resetLink, 'Passwort zurücksetzen')
            . $this->paragraph('Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.', true);

        $email = (new Email())
            ->setFrom($this->fromAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($this->getEmailSubject('Passwort zurücksetzen'))
            ->setHtmlBody($this->wrapHtml('Passwort zurücksetzen', $content));

        return $this->emailSender->send($email);
    }

    private function getEmailSubject(string $baseSubject): string
    {
        return '[' . $this->appTitleShort . '] ' . $baseSubject;
    }

    // --- HTML helpers ---

    private function wrapHtml(string $heading, string $content): string
    {
        $safeHeading    = htmlspecialchars($heading);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="de">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>{$safeHeading}</title>
        </head>
        <body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:40px 16px;">
            <tr>
              <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

                  <!-- Header -->
                  <tr>
                    <td style="background-color:{$this->navbarColor};border-radius:8px 8px 0 0;padding:24px 32px;">
                      <p style="margin:0;font-size:18px;font-weight:700;color:#ffffff;letter-spacing:0.5px;">
                        {$this->appTitleLong}
                      </p>
                    </td>
                  </tr>

                  <!-- Card -->
                  <tr>
                    <td style="background-color:#ffffff;padding:32px;border-left:1px solid #e4e4e7;border-right:1px solid #e4e4e7;">
                      <h1 style="margin:0 0 24px 0;font-size:20px;font-weight:600;color:#18181b;">{$safeHeading}</h1>
                      {$content}
                    </td>
                  </tr>

                  <!-- Footer -->
                  <tr>
                    <td style="background-color:#f4f4f5;border:1px solid #e4e4e7;border-top:none;border-radius:0 0 8px 8px;padding:16px 32px;text-align:center;">
                      <p style="margin:0;font-size:12px;color:#71717a;">Diese E-Mail wurde automatisch von {$this->appTitleShort} versandt. Bitte nicht antworten.</p>
                    </td>
                  </tr>

                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>
        HTML;
    }

    private function paragraph(string $text, bool $muted = false): string
    {
        $color = $muted ? '#71717a' : '#3f3f46';
        $size  = $muted ? '13px' : '15px';
        return "<p style=\"margin:0 0 16px 0;font-size:{$size};line-height:1.6;color:{$color};\">{$text}</p>\n";
    }

    /** @param array<string, string> $rows */
    private function details(array $rows): string
    {
        $html = '<table cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px 0;border-radius:6px;overflow:hidden;border:1px solid #e4e4e7;">' . "\n";
        $first = true;
        foreach ($rows as $label => $value) {
            $borderTop = $first ? '' : 'border-top:1px solid #e4e4e7;';
            $safeLabel = htmlspecialchars($label);
            $safeValue = htmlspecialchars($value);
            $html .= "<tr>\n"
                . "  <td style=\"{$borderTop}padding:10px 14px;font-size:12px;font-weight:600;color:#71717a;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;width:1%;background-color:#fafafa;\">{$safeLabel}</td>\n"
                . "  <td style=\"{$borderTop}padding:10px 14px;font-size:14px;color:#18181b;\">{$safeValue}</td>\n"
                . "</tr>\n";
            $first = false;
        }
        $html .= "</table>\n";
        return $html;
    }

    private function button(string $url, string $label): string
    {
        $safeUrl      = htmlspecialchars($url);
        $safeLabel    = htmlspecialchars($label);
        $navbarColor  = htmlspecialchars(APP_CONFIG->getNavbarColor());
        return "<p style=\"margin:0 0 16px 0;\">"
            . "<a href=\"{$safeUrl}\" style=\"display:inline-block;padding:12px 24px;background-color:{$navbarColor};color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;border-radius:6px;letter-spacing:0.3px;\">{$safeLabel}</a>"
            . "</p>\n";
    }
}
