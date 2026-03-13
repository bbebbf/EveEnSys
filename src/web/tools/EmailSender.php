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

        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Vielen Dank für deine Registrierung bei {$appTitle}.")
            . $this->paragraph("Klicke auf die Schaltfläche, um dein Konto zu aktivieren. Der Link ist <strong>24 Stunden</strong> gültig.")
            . $this->button($activationLink, 'Konto aktivieren')
            . $this->paragraph('Falls du dich nicht registriert hast, kannst du diese E-Mail ignorieren.', true);

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($appTitle . '-Konto aktivieren')
            ->setHtmlBody($this->wrapHtml($appTitle, 'Konto aktivieren', $content))
            ->send();
    }

    public function sendEventCreatedEmail(string $toEmail, string $toName, string $eventTitle, string $eventDate, string $eventLink, EventDto $event): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();

        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph('Deine Veranstaltung wurde erfolgreich erstellt.')
            . $this->details(['Titel' => $eventTitle, 'Datum' => $eventDate])
            . $this->button($eventLink, 'Zur Veranstaltung');

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject('Veranstaltung erstellt: ' . $eventTitle)
            ->setHtmlBody($this->wrapHtml($appTitle, 'Veranstaltung erstellt', $content))
            ->addAttachment(IcsGenerator::generate($event), 'veranstaltung.ics', 'text/calendar')
            ->send();
    }

    public function sendEventDeletedEmail(string $toEmail, string $toName, string $eventTitle): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();

        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Deine Veranstaltung <strong>" . htmlspecialchars($eventTitle) . "</strong> wurde gelöscht.");

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject('Veranstaltung gelöscht: ' . $eventTitle)
            ->setHtmlBody($this->wrapHtml($appTitle, 'Veranstaltung gelöscht', $content))
            ->send();
    }

    public function sendEnrolledEmail(string $toEmail, string $toName, string $enrolleeName, bool $isSelf, string $eventTitle, string $eventDate, string $eventLink, EventDto $event): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();
        $who      = $isSelf ? 'Du wurdest' : '<strong>' . htmlspecialchars($enrolleeName) . '</strong> wurde';

        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("{$who} erfolgreich für die folgende Veranstaltung angemeldet:")
            . $this->details(['Titel' => $eventTitle, 'Datum' => $eventDate])
            . $this->button($eventLink, 'Zur Veranstaltung');

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject('Anmeldung bestätigt: ' . $eventTitle)
            ->setHtmlBody($this->wrapHtml($appTitle, 'Anmeldung bestätigt', $content))
            ->addAttachment(IcsGenerator::generate($event), 'veranstaltung.ics', 'text/calendar')
            ->send();
    }

    public function sendUnenrolledEmail(string $toEmail, string $toName, string $enrolleeName, bool $isSelf, string $eventTitle): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();
        $who      = $isSelf ? 'Du wurdest' : '<strong>' . htmlspecialchars($enrolleeName) . '</strong> wurde';

        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("{$who} von der folgenden Veranstaltung abgemeldet:")
            . $this->details(['Titel' => $eventTitle]);

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject('Abmeldung: ' . $eventTitle)
            ->setHtmlBody($this->wrapHtml($appTitle, 'Abmeldung', $content))
            ->send();
    }

    public function sendProfileDeletedEmail(string $toEmail, string $toName): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();

        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Dein Profil bei {$appTitle} wurde gelöscht. Alle deine Daten wurden entfernt.");

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($appTitle . '-Profil gelöscht')
            ->setHtmlBody($this->wrapHtml($appTitle, 'Profil gelöscht', $content))
            ->send();
    }

    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool
    {
        $appTitle = APP_CONFIG->getAppTitleShort();

        $content = $this->paragraph("Hallo {$toName},")
            . $this->paragraph("Du hast eine Passwortzurücksetzung für dein {$appTitle}-Konto angefordert.")
            . $this->paragraph("Klicke auf die Schaltfläche, um ein neues Passwort festzulegen. Der Link ist <strong>1 Stunde</strong> gültig.")
            . $this->button($resetLink, 'Passwort zurücksetzen')
            . $this->paragraph('Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.', true);

        return (new Email())
            ->setFrom($this->noReplyAddress)
            ->addTo($toEmail, $toName)
            ->setSubject($appTitle . '-Passwort zurücksetzen')
            ->setHtmlBody($this->wrapHtml($appTitle, 'Passwort zurücksetzen', $content))
            ->send();
    }

    // --- HTML helpers ---

    private function wrapHtml(string $appTitle, string $heading, string $content): string
    {
        $safeTitle   = htmlspecialchars($appTitle);
        $safeHeading = htmlspecialchars($heading);

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
                    <td style="background-color:#1a1a2e;border-radius:8px 8px 0 0;padding:24px 32px;">
                      <p style="margin:0;font-size:18px;font-weight:700;color:#ffffff;letter-spacing:0.5px;">{$safeTitle}</p>
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
                      <p style="margin:0;font-size:12px;color:#71717a;">Diese E-Mail wurde automatisch von {$safeTitle} versandt. Bitte nicht antworten.</p>
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
        $safeUrl   = htmlspecialchars($url);
        $safeLabel = htmlspecialchars($label);
        return "<p style=\"margin:0 0 16px 0;\">"
            . "<a href=\"{$safeUrl}\" style=\"display:inline-block;padding:12px 24px;background-color:#1a1a2e;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;border-radius:6px;letter-spacing:0.3px;\">{$safeLabel}</a>"
            . "</p>\n";
    }
}
