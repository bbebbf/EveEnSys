workspace "EveEnSys" "Event-Anmeldesystem – C4-Architekturmodell" {

    model {

        # --- Personen ---

        guest = person "Gast" "Nicht angemeldeter Besucher. Sieht bevorstehende Veranstaltungen und die Kiosk-Ansicht." "External"
        user  = person "Nutzer" "Angemeldeter Benutzer. Erstellt Veranstaltungen, meldet sich an/ab und lädt Kalender-Dateien herunter."
        admin = person "Administrator" "Verwaltet Benutzer und Veranstaltungen. Hat Zugriff auf alle Funktionen."

        # --- Externe Systeme ---

        oidcProvider = softwareSystem "OIDC-Anbieter" "Optionaler externer Identity Provider (z.B. Google, Microsoft). Ermöglicht Single Sign-On." "External"
        emailClient  = softwareSystem "E-Mail-Client" "Empfängt Benachrichtigungen (Anmeldung, Abmeldung, Passwort-Reset) als Nutzer oder Administrator." "External"

        # --- EveEnSys ---

        eveEnSys = softwareSystem "EveEnSys" "Web-Applikation zur Verwaltung von Veranstaltungen und Teilnehmer-Anmeldungen." {

            webapp = container "Web-Applikation" "Verarbeitet HTTP-Anfragen, rendert HTML-Seiten und steuert die Geschäftslogik." "PHP 8.2, Apache" "WebApp" {

                router = component "Router" "Leitet eingehende HTTP-Anfragen anhand von URL-Mustern und HTTP-Methoden an den passenden Controller weiter." "PHP"
                appSession = component "AppSession" "Verwaltet Benutzer-Sitzungen (Login-Status, Benutzer-ID, Rolle) und CSRF-Tokens." "PHP"

                eventController = component "EventController" "Verarbeitet alle Anfragen zu Veranstaltungen: Anzeige, Erstellung, Bearbeitung, Löschung, An-/Abmeldung von Teilnehmern und iCal-Download." "PHP"
                authController  = component "AuthController" "Verarbeitet Authentifizierungsanfragen: Registrierung, Login, Logout, Passwort-Reset, Profilverwaltung und Benutzeradministration." "PHP"
                oidcController  = component "OidcController" "Verarbeitet den OIDC-Authentifizierungsfluss: Redirect zum Anbieter, Callback-Verarbeitung, Verknüpfung und Trennung von Identitäten." "PHP"

                eventRepo       = component "EventRepository" "Kapselt alle Datenbankzugriffe für Veranstaltungen und Teilnehmer-Anmeldungen." "PHP, MySQLi"
                userRepo        = component "UserRepository" "Kapselt alle Datenbankzugriffe für Benutzerkonten, Aktivierungs-Tokens und Passwort-Reset-Tokens." "PHP, MySQLi"
                oidcIdentityRepo = component "OidcIdentityRepository" "Kapselt alle Datenbankzugriffe für OIDC-Identitätsverknüpfungen." "PHP, MySQLi"

                emailSender  = component "EmailSender" "Erstellt und versendet HTML-E-Mails für alle systemseitigen Benachrichtigungen. Hängt ICS-Dateien bei Anmeldungen an." "PHP, SMTP"
                icsGenerator = component "IcsGenerator" "Erzeugt RFC-5545-konforme iCalendar-Dateien (VCALENDAR/VEVENT) aus Veranstaltungsdaten." "PHP"
                fileTools    = component "FileTools" "Bereinigt Dateinamen für sichere Content-Disposition-Header (entfernt Windows-Sonderzeichen und reservierte Gerätenamen)." "PHP"

                views = component "Views" "PHP-Templates (Bootstrap 5). Rendert HTML-Seiten für alle Ansichten: Veranstaltungslisten, Detailseite, Formulare, Profil, Login, Kiosk." "PHP, Bootstrap 5"
            }

            database = container "Datenbank" "Speichert Benutzerkonten, Veranstaltungen, Teilnehmer-Anmeldungen und OIDC-Identitäten." "MariaDB 10.11" "Database"

            mailpit = container "Mailpit" "SMTP-Mock-Server für die lokale Entwicklung. Fängt ausgehende E-Mails ab und stellt sie über eine Web-Oberfläche dar." "Mailpit" "MailServer"
        }

        # --- Beziehungen: Personen → System ---

        guest -> eveEnSys "Sieht bevorstehende Veranstaltungen" "HTTPS"
        user  -> eveEnSys "Verwaltet Veranstaltungen, meldet sich an/ab, lädt iCal herunter" "HTTPS"
        admin -> eveEnSys "Verwaltet Veranstaltungen und Benutzer" "HTTPS"
        eveEnSys -> emailClient "Sendet E-Mail-Benachrichtigungen" "SMTP"
        eveEnSys -> oidcProvider "Authentifiziert Benutzer (optional)" "OIDC / HTTPS"

        # --- Beziehungen: Container ---

        guest -> webapp "HTTP-Anfragen" "HTTP"
        user  -> webapp "HTTP-Anfragen" "HTTP"
        admin -> webapp "HTTP-Anfragen" "HTTP"
        webapp   -> database "Liest und schreibt Daten" "MySQLi / TCP"
        webapp   -> mailpit  "Sendet E-Mails" "SMTP"
        webapp   -> oidcProvider "OAuth2 Authorization Code Flow" "HTTPS"
        mailpit  -> emailClient "Leitet E-Mails weiter (Produktion)" "SMTP"

        # --- Beziehungen: Komponenten ---

        # Router → Controller
        router -> eventController  "Leitet Veranstaltungs-Anfragen weiter"
        router -> authController   "Leitet Auth-Anfragen weiter"
        router -> oidcController   "Leitet OIDC-Anfragen weiter"

        # EventController
        eventController -> eventRepo    "Liest/schreibt Veranstaltungen und Anmeldungen"
        eventController -> userRepo     "Liest Benutzerdaten (z.B. für CC-E-Mails)"
        eventController -> emailSender  "Sendet Anmelde-/Abmelde-/Erstellungs-E-Mails"
        eventController -> icsGenerator "Generiert ICS-Inhalt für Download"
        eventController -> fileTools    "Bereinigt Dateinamen für iCal-Download"
        eventController -> appSession   "Prüft Login-Status, Rolle und CSRF-Token"
        eventController -> views        "Rendert HTML-Seiten"

        # AuthController
        authController -> userRepo    "Liest/schreibt Benutzerkonten und Tokens"
        authController -> emailSender "Sendet Aktivierungs- und Passwort-Reset-E-Mails"
        authController -> appSession  "Verwaltet Anmeldesitzung"
        authController -> views       "Rendert HTML-Seiten"

        # OidcController
        oidcController -> oidcIdentityRepo "Liest/schreibt OIDC-Identitätsverknüpfungen"
        oidcController -> userRepo         "Liest/schreibt Benutzerdaten"
        oidcController -> appSession       "Speichert/liest State und Nonce"
        oidcController -> oidcProvider     "Redirect & Token-Austausch" "HTTPS"
        oidcController -> views            "Rendert HTML-Seiten"

        # EmailSender
        emailSender -> icsGenerator "Generiert ICS-Datei für E-Mail-Anhang"
        emailSender -> fileTools    "Bereinigt Dateinamen für E-Mail-Anhang"
        emailSender -> mailpit      "Versendet E-Mail" "SMTP"

        # Repositories → Datenbank
        eventRepo        -> database "SQL-Abfragen" "MySQLi"
        userRepo         -> database "SQL-Abfragen" "MySQLi"
        oidcIdentityRepo -> database "SQL-Abfragen" "MySQLi"
    }

    views {

        systemContext eveEnSys "Systemkontext" {
            include *
            autoLayout lr
            title "EveEnSys – Systemkontext (C4 Level 1)"
            description "Zeigt EveEnSys im Kontext seiner Nutzer und externer Systeme."
        }

        container eveEnSys "Container" {
            include *
            autoLayout lr
            title "EveEnSys – Container (C4 Level 2)"
            description "Zeigt die technischen Bausteine: Web-Applikation, Datenbank und Mail-Server."
        }

        component webapp "Komponenten" {
            include *
            autoLayout lr
            title "EveEnSys – Komponenten der Web-Applikation (C4 Level 3)"
            description "Zeigt die internen PHP-Komponenten: Controller, Repositories und Dienste."
        }

        styles {
            element "Person" {
                shape Person
                background #1168bd
                color #ffffff
            }
            element "External" {
                background #6b7280
                color #ffffff
            }
            element "Software System" {
                background #1168bd
                color #ffffff
            }
            element "Container" {
                background #438dd5
                color #ffffff
            }
            element "Component" {
                background #85bbf0
                color #1a1a1a
            }
            element "Database" {
                shape Cylinder
                background #438dd5
                color #ffffff
            }
            element "MailServer" {
                shape Pipe
                background #438dd5
                color #ffffff
            }
            element "WebApp" {
                shape WebBrowser
            }
        }
    }
}
