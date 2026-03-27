<div class="row">
  <div class="col-lg-8">

    <p class="lead">
      <?= html_out(APP_CONFIG->getAppTitleShort()) ?> ist ein webbasiertes Veranstaltungsanmeldesystem. Wer ein Konto besitzt,
      kann Veranstaltungen anlegen und sich selbst oder andere Personen dafür anmelden.
    </p>

    <section class="mb-4">
      <h2 class="h5">Was kann ich tun?</h2>
      <ul>
        <li>Veranstaltungen mit Titel, Beschreibung, Datum, Dauer und Teilnehmerlimit erstellen</li>
        <li>Eigene Veranstaltungen bearbeiten und löschen</li>
        <li>Sich selbst für eine Veranstaltung anmelden oder abmelden</li>
        <li>Weitere Personen (ohne eigenes Konto) namentlich anmelden</li>
        <li>Die Teilnehmerliste aller Veranstaltungen einsehen</li>
      </ul>
    </section>

    <section class="mb-4">
      <h2 class="h5">Wer kann mitmachen?</h2>
      <p>
        Das Anzeigen und Verwalten von Veranstaltungen sowie das Einsehen der Teilnehmerlisten
        ist registrierten und angemeldeten Benutzern vorbehalten.
        Ein Konto kann auf der <a href="/register">Registrierungsseite</a> angelegt werden.
      </p>
    </section>

    <section class="mb-4">
      <h2 class="h5">Kiosk-Ansicht</h2>
      <p>
        Die <a href="/kiosk">Kiosk-Ansicht</a> zeigt bevorstehende Veranstaltungen ohne Anmeldung
        und eignet sich z. B. für eine öffentliche Bildschirmanzeige.
      </p>
    </section>

    <hr/>

    <section>
      <p>Version: <?= html_out(APP_CONFIG->getAppVersion()) ?></p>
    </section>

  </div>
</div>
