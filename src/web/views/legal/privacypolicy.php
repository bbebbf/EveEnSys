<?php
/** @var string $pageTitle */
$_opName   = APP_CONFIG->getOperatorName();
$_opStreet = APP_CONFIG->getOperatorStreet();
$_opCity   = APP_CONFIG->getOperatorCity();
$_opEmail  = APP_CONFIG->getOperatorEmail();
$_opResponsible = APP_CONFIG->getOperatorResponsible();
$_opMissing = $_opName === '' || $_opStreet === '' || $_opCity === '' || $_opEmail === '' || $_opResponsible === '';
?>

<h1 class="mb-4">Datenschutzerklärung</h1>

<div class="row">
  <div class="col-lg-9">

    <!-- 1. Verantwortlicher -->
    <section class="mb-5">
      <h2 class="h4">1. Verantwortlicher</h2>
      <?php if ($_opMissing): ?>
        <div class="alert alert-warning">
          <strong>Hinweis:</strong> Die Betreiberdaten sind noch nicht vollständig konfiguriert.
          Bitte tragen Sie <code>OperatorName</code>, <code>OperatorStreet</code>,
          <code>OperatorCity</code> und <code>OperatorEmail</code> in der
          <code>app-config.json</code> ein.
        </div>
      <?php endif; ?>
      <p>
        Verantwortlicher im Sinne der Datenschutz-Grundverordnung (DSGVO) für den Betrieb dieser
        Webanwendung ist:
      </p>
      <address>
        <strong><?= html_out($_opName ?: '[Name des Betreibers]') ?></strong><br>
        <?= html_out($_opStreet ?: '[Straße und Hausnummer]') ?>&nbsp;&middot;&nbsp;<?= html_out($_opCity ?: '[PLZ Ort]') ?><br>
        <?php if ($_opResponsible !== ''): ?>
          Verantwortlich: <?= html_out($_opResponsible) ?><br>
        <?php endif; ?>
        <?php if ($_opEmail !== ''): ?>
          E-Mail: <a href="mailto:<?= html_out($_opEmail) ?>"><?= html_out($_opEmail) ?></a>
        <?php else: ?>
          E-Mail: [E-Mail-Adresse]
        <?php endif; ?>
      </address>
    </section>

    <!-- 2. Allgemeines -->
    <section class="mb-5">
      <h2 class="h4">2. Allgemeines zur Datenverarbeitung</h2>
      <p>
        Wir verarbeiten personenbezogene Daten unserer Nutzer grundsätzlich nur, soweit dies zur
        Bereitstellung einer funktionsfähigen Webanwendung sowie unserer Inhalte und Leistungen
        erforderlich ist. Die Verarbeitung personenbezogener Daten erfolgt regelmäßig nur nach
        Einwilligung des Nutzers. Eine Ausnahme gilt in solchen Fällen, in denen eine vorherige
        Einholung einer Einwilligung aus tatsächlichen Gründen nicht möglich ist und die Verarbeitung
        der Daten durch gesetzliche Vorschriften gestattet ist.
      </p>
    </section>

    <!-- 3. Server-Logfiles -->
    <section class="mb-5">
      <h2 class="h4">3. Server-Logdateien</h2>
      <p>
        Der Webhoster, auf dem diese Anwendung betrieben wird, erhebt und speichert automatisch
        Informationen in sogenannten Server-Logdateien, die Ihr Browser automatisch übermittelt.
        Dies sind:
      </p>
      <ul>
        <li>IP-Adresse des anfragenden Geräts</li>
        <li>Datum und Uhrzeit der Anfrage</li>
        <li>Aufgerufene URL</li>
        <li>HTTP-Statuscode</li>
        <li>Übertragene Datenmenge</li>
        <li>Browsertyp und -version</li>
        <li>Betriebssystem</li>
        <li>Referrer-URL (die zuvor besuchte Seite)</li>
      </ul>
      <p>
        Diese Daten sind nicht bestimmten Personen zuordenbar und werden nicht mit anderen
        Datenquellen zusammengeführt. Rechtsgrundlage ist Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;f DSGVO
        (berechtigtes Interesse an der technisch sicheren und stabilen Bereitstellung der Anwendung).
        Die Logdaten werden nach spätestens 30 Tagen gelöscht.
      </p>
    </section>

    <!-- 4. Session-Cookie -->
    <section class="mb-5">
      <h2 class="h4">4. Session-Cookie</h2>
      <p>
        Diese Anwendung verwendet ausschließlich einen technisch notwendigen Session-Cookie
        (<code>PHPSESSID</code>). Dieser Cookie speichert keine personenbezogenen Daten, sondern
        lediglich eine zufällig generierte Sitzungs-ID, die Ihnen die Nutzung der Anwendung
        ermöglicht (z.&nbsp;B. Anmeldezustand, Fehlermeldungen). Der Cookie wird beim Schließen des
        Browsers oder beim Abmelden automatisch gelöscht.
      </p>
      <p>
        Eine Einwilligung ist gemäß § 25 Abs.&nbsp;2 Nr.&nbsp;2 TTDSG nicht erforderlich, da der
        Cookie technisch zwingend notwendig ist. Rechtsgrundlage für die Verarbeitung der
        Sitzungsdaten ist Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;b DSGVO (Vertragserfüllung /
        Bereitstellung der beantragten Dienste).
      </p>
    </section>

    <!-- 5. Registrierung und Benutzerkonto -->
    <section class="mb-5">
      <h2 class="h4">5. Registrierung und Benutzerkonto</h2>
      <p>
        Zur Nutzung der Anwendung ist eine Registrierung erforderlich. Dabei erheben wir folgende
        Daten:
      </p>
      <ul>
        <li><strong>E-Mail-Adresse</strong> — zur Identifikation, Anmeldung und für systembezogene
          Benachrichtigungen (z.&nbsp;B. Passwort-Zurücksetzen)</li>
        <li><strong>Benutzername</strong> — zur Darstellung in der Anwendung</li>
        <li><strong>Passwort</strong> — wird ausschließlich als sicherer Hash (bcrypt) gespeichert;
          das Klartextpasswort wird niemals gespeichert</li>
      </ul>
      <p>
        Rechtsgrundlage ist Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;b DSGVO (Vertragserfüllung). Die
        Daten werden gelöscht, sobald das Benutzerkonto auf Ihren Wunsch hin gelöscht wird oder der
        Betrieb der Anwendung eingestellt wird. Eine Löschung des eigenen Kontos ist jederzeit über
        die Profileinstellungen möglich.
      </p>
    </section>

    <!-- 6. Veranstaltungen und Anmeldungen -->
    <section class="mb-5">
      <h2 class="h4">6. Veranstaltungen und Anmeldungen</h2>
      <p>
        Angemeldete Nutzer können Veranstaltungen erstellen sowie sich selbst oder andere Personen
        zu Veranstaltungen anmelden. Dabei werden folgende Daten verarbeitet:
      </p>
      <ul>
        <li><strong>Veranstaltungsdaten</strong> (z.B. Titel, Beschreibung, Datum, Dauer,
          max. Teilnehmerzahl) — werden dem erstellenden Nutzer zugeordnet gespeichert</li>
        <li><strong>Anmeldungen</strong> — es wird gespeichert, welcher angemeldete Nutzer wen
          angemeldet hat. Bei Fremdanmeldungen wird der angegebene Name der Person gespeichert.</li>
      </ul>
      <p>
        Die Namen angemeldeter Personen sind ausschließlich für angemeldete Nutzer sichtbar.
        Rechtsgrundlage ist Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;b DSGVO. Die Daten werden mit
        Löschung der jeweiligen Veranstaltung oder des erstellenden Benutzerkontos entfernt.
      </p>
    </section>

    <!-- 7. E-Mail-Versand -->
    <section class="mb-5">
      <h2 class="h4">7. E-Mail-Versand</h2>
      <p>
        Die Anwendung versendet automatisch E-Mails an die hinterlegte E-Mail-Adresse des
        betroffenen Nutzers in folgenden Fällen:
      </p>
      <ul>
        <li><strong>Kontoaktivierung</strong> — nach der Registrierung wird ein Aktivierungslink
          versendet (gültig für 24 Stunden)</li>
        <li><strong>Passwort-Zurücksetzen</strong> — auf Anfrage wird ein Link zum Zurücksetzen
          des Passworts gesendet (gültig für 1 Stunde)</li>
        <li><strong>Veranstaltung erstellt</strong> — Bestätigung an den Ersteller, mit der
          Veranstaltung als ICS-Anhang (Kalenderdatei)</li>
        <li><strong>Veranstaltung gelöscht</strong> — Benachrichtigung an den Ersteller</li>
        <li><strong>Anmeldung zu einer Veranstaltung</strong> — Bestätigung an den anmeldenden
          Nutzer, mit der Veranstaltung als ICS-Anhang</li>
        <li><strong>Abmeldung von einer Veranstaltung</strong> — Benachrichtigung an den
          abmeldenden Nutzer</li>
        <li><strong>Profil gelöscht</strong> — Bestätigung an den betroffenen Nutzer</li>
        <li><strong>Administrator-Rechte vergeben oder entzogen</strong> — Benachrichtigung an
          den betroffenen Nutzer</li>
      </ul>
      <p>
        Rechtsgrundlage ist Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;b DSGVO (Vertragserfüllung /
        Bereitstellung der beantragten Dienste). Der E-Mail-Versand erfolgt über einen
        SMTP-Server. Dabei wird die E-Mail-Adresse des Empfängers an den Mailserver übermittelt.
        Es findet keine Weitergabe an sonstige Dritte statt. Alle E-Mails werden automatisch
        versandt und enthalten keinen Tracking-Mechanismus.
      </p>
    </section>

    <!-- 8. Anmeldung über externe Identitätsanbieter (OIDC) -->
    <section class="mb-5">
      <h2 class="h4">8. Anmeldung über externe Identitätsanbieter (OpenID Connect)</h2>
      <p>
        Optional kann die Anmeldung über externe Identitätsanbieter (z.&nbsp;B. einen
        Unternehmens-SSO-Dienst) erfolgen, sofern diese Funktion aktiviert ist. Dabei werden
        folgende Daten vom jeweiligen Anbieter an diese Anwendung übermittelt:
      </p>
      <ul>
        <li>E-Mail-Adresse</li>
        <li>Anzeigename (Name des Kontos beim Anbieter)</li>
        <li>Eindeutige Nutzer-ID beim Anbieter</li>
      </ul>
      <p>
        Die Nutzung dieser Funktion setzt voraus, dass Sie sich zuvor beim jeweiligen
        Identitätsanbieter angemeldet haben. Für die dortige Datenverarbeitung gelten die
        Datenschutzhinweise des jeweiligen Anbieters. Die Verknüpfung mit dem Identitätsanbieter
        kann jederzeit in den Profileinstellungen aufgehoben werden.
        Rechtsgrundlage ist Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;b DSGVO.
      </p>
    </section>

    <!-- 9. Externe Ressourcen (CDN) -->
    <section class="mb-5">
      <h2 class="h4">9. Externe Ressourcen (Content Delivery Network)</h2>
      <p>
        Diese Anwendung lädt beim Aufruf von Seiten folgende externe Ressourcen von einem
        Content-Delivery-Network (CDN) des Anbieters <strong>jsDelivr</strong>
        (Betreiber: Prospect One, 545&nbsp;Fifth Avenue, Suite&nbsp;1400, New York, NY 10017, USA):
      </p>
      <ul>
        <li>Bootstrap CSS (Gestaltungsrahmen)</li>
        <li>Bootstrap JavaScript (interaktive Komponenten)</li>
        <li>Bootstrap Icons (Schriftart mit Symbolen)</li>
      </ul>
      <p>
        Beim Laden dieser Ressourcen wird Ihre IP-Adresse sowie technische Browser-Informationen
        an die Server von jsDelivr übertragen. jsDelivr verwendet nach eigenen Angaben keine Cookies
        für diese Anfragen. Rechtsgrundlage ist Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;f DSGVO
        (berechtigtes Interesse an der technisch zuverlässigen und performanten Darstellung der
        Anwendung). Weitere Informationen finden Sie in der
        <a href="https://www.jsdelivr.com/privacy-policy-jsdelivr-net" target="_blank"
           rel="noopener noreferrer">Datenschutzerklärung von jsDelivr</a>.
      </p>
    </section>

    <!-- 10. Datensicherheit -->
    <section class="mb-5">
      <h2 class="h4">10. Datensicherheit</h2>
      <p>
        Diese Anwendung setzt technische und organisatorische Maßnahmen ein, um Ihre Daten gegen
        zufällige oder vorsätzliche Manipulation, Verlust, Zerstörung oder den Zugriff unberechtigter
        Personen zu schützen. Dazu gehören insbesondere:
      </p>
      <ul>
        <li>Übertragung aller Daten über eine verschlüsselte HTTPS-Verbindung (TLS)</li>
        <li>Speicherung von Passwörtern ausschließlich als bcrypt-Hash</li>
        <li>Verwendung von CSRF-Tokens zum Schutz vor Cross-Site-Request-Forgery</li>
        <li>Nicht erratbare Veranstaltungs-URLs (UUIDs statt fortlaufende Nummern)</li>
      </ul>
    </section>

    <!-- 11. Keine Weitergabe an Dritte -->
    <section class="mb-5">
      <h2 class="h4">11. Weitergabe an Dritte</h2>
      <p>
        Eine Übermittlung Ihrer personenbezogenen Daten an Dritte findet nicht statt, mit Ausnahme
        der in dieser Erklärung genannten Fälle (SMTP-Mailserver für den E-Mail-Versand,
        CDN für externe Ressourcen, ggf. externe Identitätsanbieter gemäß Abschnitt&nbsp;8).
        Eine Nutzung der Daten zu Werbezwecken oder eine Weitergabe an kommerzielle Dritte erfolgt
        nicht. Eine Datenübermittlung in Drittländer außerhalb der EU/des EWR findet nur im Rahmen
        des in Abschnitt&nbsp;9 beschriebenen CDN-Abrufs statt.
      </p>
    </section>

    <!-- 12. Keine automatisierte Entscheidungsfindung -->
    <section class="mb-5">
      <h2 class="h4">12. Automatisierte Entscheidungsfindung und Profiling</h2>
      <p>
        Es findet keine automatisierte Entscheidungsfindung oder Profiling im Sinne von
        Art.&nbsp;22 DSGVO statt.
      </p>
    </section>

    <!-- 13. Ihre Rechte -->
    <section class="mb-5">
      <h2 class="h4">13. Ihre Rechte als betroffene Person</h2>
      <p>Ihnen stehen folgende Rechte gegenüber dem Verantwortlichen zu:</p>
      <dl>
        <dt>Auskunft (Art.&nbsp;15 DSGVO)</dt>
        <dd class="mb-2">Sie haben das Recht, Auskunft über die zu Ihrer Person gespeicherten Daten
          zu erhalten.</dd>

        <dt>Berichtigung (Art.&nbsp;16 DSGVO)</dt>
        <dd class="mb-2">Sie haben das Recht, die Berichtigung unrichtiger oder Vervollständigung
          unvollständiger Daten zu verlangen. Name und E-Mail-Adresse können Sie jederzeit selbst
          in den Profileinstellungen ändern.</dd>

        <dt>Löschung (Art.&nbsp;17 DSGVO)</dt>
        <dd class="mb-2">Sie haben das Recht, die Löschung Ihrer personenbezogenen Daten zu
          verlangen, sofern keine gesetzlichen Aufbewahrungspflichten entgegenstehen. Ihr Konto
          können Sie jederzeit selbst über die Profileinstellungen löschen.</dd>

        <dt>Einschränkung der Verarbeitung (Art.&nbsp;18 DSGVO)</dt>
        <dd class="mb-2">Sie haben das Recht, unter bestimmten Voraussetzungen die Einschränkung
          der Verarbeitung Ihrer personenbezogenen Daten zu verlangen.</dd>

        <dt>Datenübertragbarkeit (Art.&nbsp;20 DSGVO)</dt>
        <dd class="mb-2">Sie haben das Recht, die Sie betreffenden Daten in einem strukturierten,
          gängigen und maschinenlesbaren Format zu erhalten oder deren Übermittlung an einen anderen
          Verantwortlichen zu verlangen.</dd>

        <dt>Widerspruch (Art.&nbsp;21 DSGVO)</dt>
        <dd class="mb-2">Sie haben das Recht, der Verarbeitung Ihrer Daten auf Grundlage von
          Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;f DSGVO (berechtigtes Interesse) jederzeit zu
          widersprechen.</dd>

        <dt>Beschwerderecht bei einer Aufsichtsbehörde (Art.&nbsp;77 DSGVO)</dt>
        <dd class="mb-2">Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu
          beschweren, insbesondere in dem Mitgliedstaat Ihres gewöhnlichen Aufenthaltsorts, Ihres
          Arbeitsplatzes oder des Orts des mutmaßlichen Verstoßes. Eine Liste der
          Aufsichtsbehörden in Deutschland finden Sie beim
          <a href="https://www.bfdi.bund.de/DE/Infothek/Anschriften_Links/anschriften_links-node.html"
             target="_blank" rel="noopener noreferrer">
            Bundesbeauftragten für den Datenschutz und die Informationsfreiheit (BfDI)</a>.</dd>
      </dl>
      <p>
        Zur Ausübung Ihrer Rechte wenden Sie sich bitte an die oben genannte Kontaktadresse des
        Verantwortlichen.
      </p>
    </section>

    <!-- 14. Aktualität -->
    <section class="mb-5">
      <h2 class="h4">14. Aktualität und Änderungen dieser Datenschutzerklärung</h2>
      <p>
        Diese Datenschutzerklärung hat den Stand <strong>März&nbsp;2026</strong>. Durch die
        Weiterentwicklung der Anwendung oder aufgrund geänderter gesetzlicher bzw. behördlicher
        Vorgaben kann es notwendig werden, diese Datenschutzerklärung anzupassen. Die jeweils
        aktuelle Fassung kann jederzeit in der Anwendung abgerufen werden.
      </p>
    </section>

  </div>
</div>
