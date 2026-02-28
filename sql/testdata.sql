-- ============================================================
-- EveEnSys Test Data
-- 15 users, 40 events, ~120 subscriber records
-- All users have password: Test1234
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE subscriber;
TRUNCATE TABLE event;
TRUNCATE TABLE user;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- USERS (15)
-- ============================================================
INSERT INTO `user` (user_id, user_guid, user_email, user_is_active, user_role, user_name, user_passwd, user_last_login) VALUES
( 1, 'A1B2C3D4', 'anna.mueller@example.de',      1, 0, 'Anna Müller',       '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-27 09:14:00'),
( 2, 'E5F6G7H8', 'thomas.schneider@example.de',  1, 0, 'Thomas Schneider',  '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-26 17:45:00'),
( 3, 'I9J0K1L2', 'maria.schmidt@example.de',     1, 0, 'Maria Schmidt',     '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-25 11:30:00'),
( 4, 'M3N4O5P6', 'k.weber@example.de',           1, 0, 'Klaus Weber',       '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-20 08:00:00'),
( 5, 'Q7R8S9T0', 'sophie.fischer@example.de',    1, 0, 'Sophie Fischer',    '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-28 07:55:00'),
( 6, 'U1V2W3X4', 'm.wagner@example.de',          1, 0, 'Michael Wagner',    '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-22 14:10:00'),
( 7, 'Y5Z6A7B8', 'laura.becker@example.de',      1, 0, 'Laura Becker',      '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-18 19:00:00'),
( 8, 'C9D0E1F2', 'stefan.hoffmann@example.de',   1, 0, 'Stefan Hoffmann',   '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-27 20:33:00'),
( 9, 'G3H4I5J6', 'p.schulz@example.de',          1, 0, 'Petra Schulz',      '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-15 10:20:00'),
(10, 'K7L8M9N0', 'jan.meyer@example.de',         1, 0, 'Jan Meyer',         '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-28 06:45:00'),
(11, 'O1P2Q3R4', 'sabine.koch@example.de',       1, 0, 'Sabine Koch',       '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-24 16:00:00'),
(12, 'S5T6U7V8', 'andreas.bauer@example.de',     1, 0, 'Andreas Bauer',     '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-19 13:25:00'),
(13, 'W9X0Y1Z2', 'k.richter@example.de',         1, 0, 'Katharina Richter', '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-23 09:50:00'),
(14, 'A3B4C5D6', 'markus.wolf@example.de',       1, 0, 'Markus Wolf',       '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-21 18:15:00'),
(15, 'E7F8G9H0', 'julia.klein@example.de',       1, 0, 'Julia Klein',       '$2y$10$WvOc3QmNBiblCerT.q7/A.xgEukFIN30saMdxr1WchY62Dofx1jku', '2026-02-17 12:40:00');


-- ============================================================
-- EVENTS (40)
-- ============================================================
INSERT INTO `event` (event_id, event_guid, creator_user_id, event_is_visible, event_title, event_description, event_date, event_location, event_duration_hours, event_max_subscriber) VALUES

-- Past events
( 1, 'EV000001',  1, 1, 'Stadtlauf München',
   'Gemeinsamer Lauf durch die Innenstadt. Alle Distanzen willkommen – ob 5 km oder 15 km.',
   '2025-12-07 10:00:00', 'Marienplatz, München', 3.0, 50),

( 2, 'EV000002',  2, 1, 'PHP-Workshop für Einsteiger',
   'Grundlagen der PHP-Programmierung: Variablen, Schleifen, Funktionen und eine einfache Webanwendung.',
   '2025-12-14 09:00:00', 'Coworking Space Nord, Hamburg', 8.0, 15),

( 3, 'EV000003',  3, 1, 'Weihnachts-Stammtisch',
   'Gemütlicher Abend zum Jahresausklang mit Glühwein und kleinem Buffet.',
   '2025-12-20 19:00:00', 'Zum Goldenen Anker, Berlin', 4.0, 30),

( 4, 'EV000004',  4, 1, 'Fotografie-Spaziergang im Englischen Garten',
   'Winterfotografie mit Tipps zu Belichtung und Komposition. Bitte eigene Kamera mitbringen.',
   '2026-01-05 11:00:00', 'Englischer Garten, München', 2.5, 12),

( 5, 'EV000005',  5, 1, 'Volleyball-Turnier',
   'Internes Turnier in der Dreifachsporthalle. Teams à 6 Personen, Anmeldung als Team.',
   '2026-01-11 14:00:00', 'Sporthalle West, Frankfurt', 4.0, 48),

( 6, 'EV000006',  6, 1, 'Kochkurs: Italienische Küche',
   'Frische Pasta, Risotto und Tiramisu selbst zubereiten. Alle Zutaten werden gestellt.',
   '2026-01-18 17:00:00', 'Volkshochschule Mitte, Köln', 3.5, 10),

( 7, 'EV000007',  7, 1, 'Tech-Meetup: KI im Alltag',
   'Kurzvorträge und Diskussionsrunde zu praktischen KI-Anwendungen in Beruf und Freizeit.',
   '2026-01-22 18:30:00', 'Startuphaus Leipzig', 2.0, 40),

( 8, 'EV000008',  8, 1, 'Winterwanderung Tegernsee',
   'Geführte Wanderung rund um den Tegernsee, ca. 12 km. Festes Schuhwerk empfohlen.',
   '2026-01-25 09:00:00', 'Tegernsee, Bayern', 5.0, 20),

( 9, 'EV000009',  9, 1, 'Bücherclub: Lesung und Diskussion',
   'Diesen Monat: „Der Vorleser" von Bernhard Schlink. Bitte das Buch vorher lesen.',
   '2026-01-29 19:30:00', 'Stadtbibliothek, Stuttgart', 2.0, 15),

(10, 'EV000010', 10, 1, 'Radtour Elberadweg',
   'Entspannte Tagestour auf dem Elberadweg, ca. 40 km. Rennrad oder Tourenrad geeignet.',
   '2026-02-01 08:30:00', 'Elbbrücken, Hamburg', 6.0, 25),

(11, 'EV000011', 11, 1, 'Yoga im Park',
   'Morgen-Yoga für alle Level. Matte mitbringen, bei Regen findet es im nahen Café statt.',
   '2026-02-07 08:00:00', 'Stadtpark, Hamburg', 1.5, 20),

(12, 'EV000012', 12, 1, 'Netzwerktreffen IT & Digitalisierung',
   'Informeller Austausch für IT-Fachleute und digitale Macher. Getränke inklusive.',
   '2026-02-12 17:00:00', 'Skylounge, Düsseldorf', 3.0, 60),

(13, 'EV000013', 13, 1, 'Erste-Hilfe-Kurs',
   'Auffrischungskurs für alle, die ihren Führerschein schon länger haben. DRK-Zertifikat.',
   '2026-02-15 09:00:00', 'DRK-Zentrum, Nürnberg', 8.0, 16),

(14, 'EV000014', 14, 1, 'Improvisationstheater-Workshop',
   'Spielerisches Einführungsseminar: Übungen zu Spontaneität, Teamwork und Bühnenenergie.',
   '2026-02-20 15:00:00', 'Theater am Rand, Dresden', 3.0, 18),

(15, 'EV000015', 15, 1, 'Schiclub Frühlingsskifahren',
   'Letzter Skitag der Saison auf der Zugspitze. Anreise individuell, Treffpunkt Talstation 8:45 Uhr.',
   '2026-02-22 09:00:00', 'Zugspitze, Garmisch', 7.0, 30),

-- Current / Near future
(16, 'EV000016',  1, 1, 'Ostermarkt Vorbereitung: Standaufbau',
   'Gemeinsamer Aufbau der Marktstände für den Ostermarkt am nächsten Wochenende.',
   '2026-03-01 10:00:00', 'Rathausplatz, Augsburg', 4.0, 25),

(17, 'EV000017',  2, 1, 'JavaScript Deep Dive',
   'Fortgeschrittene Themen: async/await, Module, Performance-Tipps. Laptop erforderlich.',
   '2026-03-05 09:00:00', 'Coworking Space Nord, Hamburg', 7.0, 12),

(18, 'EV000018',  3, 1, 'Frühjahrsputz im Stadtpark',
   'Gemeinsames Aufräumen und Bepflanzen des Stadtparks. Werkzeug wird gestellt.',
   '2026-03-08 09:30:00', 'Stadtpark Eingang West, Berlin', 3.0, 40),

(19, 'EV000019',  4, 1, 'Porträtfotografie-Kurs',
   'Technik und Gestaltung bei Porträts. Modelle sind vorhanden, eigene Kamera mitbringen.',
   '2026-03-12 14:00:00', 'Fotostudio am Dom, Köln', 4.0, 8),

(20, 'EV000020',  5, 1, 'Badminton-Turnier',
   'Offenes Einzel- und Doppelturnier für alle Spielstärken. Schläger können ausgeliehen werden.',
   '2026-03-15 13:00:00', 'Sportzentrum Ost, Frankfurt', 5.0, 32),

(21, 'EV000021',  6, 1, 'Kochkurs: Asiatische Küche',
   'Sushi, Dim Sum und Ramen – Einführung in die asiatische Küche. Zutaten inklusive.',
   '2026-03-19 17:30:00', 'Volkshochschule Mitte, Köln', 3.5, 10),

(22, 'EV000022',  7, 1, 'Hackathon: Nachhaltige Lösungen',
   '24-Stunden-Hackathon rund um das Thema Nachhaltigkeit. Teams aus 3–5 Personen.',
   '2026-03-21 10:00:00', 'Startuphaus Leipzig', 24.0, 60),

(23, 'EV000023',  8, 1, 'Frühlingsradtour Chiemgau',
   'Genussradtour durch die Chiemgauer Landschaft, ca. 35 km. E-Bike-Verleih möglich.',
   '2026-03-28 09:00:00', 'Rosenheim Bahnhof', 6.0, 20),

(24, 'EV000024',  9, 1, 'Bücherclub: Frühlingslesung',
   'Thema: Frühlingsromane – jeder bringt eine Empfehlung mit. Offene Diskussionsrunde.',
   '2026-04-02 19:30:00', 'Stadtbibliothek, Stuttgart', 2.0, 15),

(25, 'EV000025', 10, 1, 'Triathlon-Vorbereitungstraining',
   'Gemeinsames Training: Schwimmen, Radfahren und Laufen. Für Einsteiger und Fortgeschrittene.',
   '2026-04-05 08:00:00', 'Freibad Nord, Hamburg', 3.0, 20),

(26, 'EV000026', 11, 1, 'Pilates-Workshop',
   'Intensivkurs für Wirbelsäulengesundheit und Körperhaltung. Matte und bequeme Kleidung.',
   '2026-04-09 09:00:00', 'Yoga-Zentrum am See, Hamburg', 2.5, 12),

(27, 'EV000027', 12, 1, 'Agile Methoden im Projektmanagement',
   'Scrum und Kanban in der Praxis: Hands-on Workshop mit realen Fallbeispielen.',
   '2026-04-11 10:00:00', 'IHK Tagungszentrum, Düsseldorf', 6.0, 25),

(28, 'EV000028', 13, 1, 'Keramik-Töpferkurs',
   'Einführung in das Töpfern am Drehrad. Materialkosten inklusive, keine Vorkenntnisse nötig.',
   '2026-04-16 14:00:00', 'Atelier Ton & Form, Nürnberg', 3.0, 8),

(29, 'EV000029', 14, 1, 'Theateraufführung: Midsommar',
   'Eigenproduktion des Ensembles. Einlass ab 19:30 Uhr, Beginn 20:00 Uhr.',
   '2026-04-24 20:00:00', 'Theater am Rand, Dresden', 2.5, 80),

(30, 'EV000030', 15, 1, 'Mountainbike-Tour Bayerischer Wald',
   'Technische Singletrail-Tour für geübte Fahrer. Helm und Protektion Pflicht.',
   '2026-04-26 09:00:00', 'Trailhead Bodenmais', 6.0, 15),

(31, 'EV000031',  1, 1, 'Laufgruppe: Halbmarathon-Vorbereitung',
   'Trainingsgruppe für den Stadtlauf im Juni. Wöchentliche Läufe, alle Niveaus willkommen.',
   '2026-05-03 09:00:00', 'Olympiastadion, München', 2.0, 30),

(32, 'EV000032',  2, 1, 'Docker & Kubernetes Praxistag',
   'Container-Technologien von Grund auf. Laptop mit Docker-Installation mitbringen.',
   '2026-05-07 09:00:00', 'Coworking Space Nord, Hamburg', 8.0, 14),

(33, 'EV000033',  3, 1, 'Nachbarschaftsfest Prenzlauer Berg',
   'Sommerfest mit Musik, Grill und Spielen für die ganze Familie.',
   '2026-05-17 14:00:00', 'Mauerpark, Berlin', 5.0, 100),

(34, 'EV000034',  4, 1, 'Landschaftsfotografie Alpen',
   'Ganztages-Exkursion in die Alpen. Frühaufsteher – Abfahrt 5:00 Uhr.',
   '2026-05-23 05:00:00', 'Treffpunkt Hauptbahnhof, München', 12.0, 10),

(35, 'EV000035',  5, 1, 'Beach-Volleyball Liga Sommer',
   'Kick-off der Sommersaison. Jedes Team spielt mind. 3 Spiele.',
   '2026-05-30 13:00:00', 'Beachanlage Nord, Frankfurt', 6.0, 64),

(36, 'EV000036',  6, 1, 'Grillkurs: BBQ Classics',
   'Steaks, Ribs und Gemüse vom Grill. Theorie und Praxis – inkl. Degustation.',
   '2026-06-06 15:00:00', 'Grillakademie Rhein, Köln', 4.0, 12),

(37, 'EV000037',  7, 1, 'Open Source Contributor Day',
   'Gemeinsam an Open-Source-Projekten arbeiten. Bring deine Ideen und Laptops mit.',
   '2026-06-13 10:00:00', 'Startuphaus Leipzig', 8.0, 35),

(38, 'EV000038',  8, 1, 'Klettersteig Berchtesgaden',
   'Geführte Klettersteig-Tour (Schwierigkeit B/C). Ausrüstung kann geliehen werden.',
   '2026-06-20 07:00:00', 'Berchtesgaden Kurort', 8.0, 12),

(39, 'EV000039',  9, 1, 'Sommernacht-Lesung im Garten',
   'Freiluft-Lesung bei Kerzenschein. Mitgebrachte Texte können vorgelesen werden.',
   '2026-06-27 21:00:00', 'Gartenlokal Bücherwurm, Stuttgart', 3.0, 25),

(40, 'EV000040', 10, 1, 'Hamburg Triathlon',
   'Offizieller Wettkampf – Voranmeldung über den Veranstalter erforderlich.',
   '2026-07-04 07:00:00', 'Stadtpark Hamburg', 5.0, 500);


-- ============================================================
-- SUBSCRIBERS (~120 records)
-- subscriber_is_creator = 1 → the logged-in user enrolled themselves
-- subscriber_is_creator = 0 → a guest name was enrolled by that user
-- ============================================================
INSERT INTO `subscriber` (subscriber_guid, event_id, creator_user_id, subscriber_is_creator, subscriber_name, subscriber_enroll_timestamp) VALUES

-- Event 1: Stadtlauf München (creator: user 1)
('SB000001',  1,  1, 1, NULL,                 '2025-11-20 10:00:00'),
('SB000002',  1,  2, 1, NULL,                 '2025-11-21 11:00:00'),
('SB000003',  1,  5, 1, NULL,                 '2025-11-22 09:30:00'),
('SB000004',  1,  8, 1, NULL,                 '2025-11-23 14:00:00'),
('SB000005',  1,  2, 0, 'Peter Huber',        '2025-11-24 08:00:00'),
('SB000006',  1,  3, 0, 'Brigitte Lange',     '2025-11-25 15:00:00'),

-- Event 2: PHP-Workshop (creator: user 2)
('SB000007',  2,  2, 1, NULL,                 '2025-11-28 09:00:00'),
('SB000008',  2,  7, 1, NULL,                 '2025-11-29 10:30:00'),
('SB000009',  2, 10, 1, NULL,                 '2025-11-30 11:00:00'),
('SB000010',  2, 13, 0, 'Florian Neumann',    '2025-12-01 08:45:00'),

-- Event 3: Weihnachts-Stammtisch (creator: user 3)
('SB000011',  3,  3, 1, NULL,                 '2025-12-05 19:00:00'),
('SB000012',  3,  1, 1, NULL,                 '2025-12-06 20:00:00'),
('SB000013',  3,  4, 1, NULL,                 '2025-12-07 18:30:00'),
('SB000014',  3,  6, 1, NULL,                 '2025-12-08 17:00:00'),
('SB000015',  3,  9, 1, NULL,                 '2025-12-08 17:30:00'),
('SB000016',  3, 12, 1, NULL,                 '2025-12-09 10:00:00'),
('SB000017',  3,  3, 0, 'Helga Brandt',       '2025-12-09 11:00:00'),
('SB000018',  3,  3, 0, 'Werner Fuchs',       '2025-12-09 11:05:00'),

-- Event 4: Fotografie-Spaziergang (creator: user 4)
('SB000019',  4,  4, 1, NULL,                 '2025-12-20 10:00:00'),
('SB000020',  4, 13, 1, NULL,                 '2025-12-21 09:00:00'),
('SB000021',  4, 15, 1, NULL,                 '2025-12-22 08:30:00'),
('SB000022',  4,  4, 0, 'Ute Zimmermann',     '2025-12-23 14:00:00'),

-- Event 5: Volleyball-Turnier (creator: user 5)
('SB000023',  5,  5, 1, NULL,                 '2025-12-22 13:00:00'),
('SB000024',  5,  6, 1, NULL,                 '2025-12-23 14:00:00'),
('SB000025',  5,  8, 1, NULL,                 '2025-12-24 15:00:00'),
('SB000026',  5, 11, 1, NULL,                 '2025-12-25 16:00:00'),
('SB000027',  5, 14, 1, NULL,                 '2025-12-26 17:00:00'),
('SB000028',  5,  5, 0, 'Dieter Klemm',       '2025-12-27 08:00:00'),
('SB000029',  5,  5, 0, 'Renate Stolze',      '2025-12-27 08:10:00'),

-- Event 6: Kochkurs Italienisch (creator: user 6)
('SB000030',  6,  6, 1, NULL,                 '2026-01-02 17:00:00'),
('SB000031',  6,  7, 1, NULL,                 '2026-01-03 18:00:00'),
('SB000032',  6, 11, 1, NULL,                 '2026-01-04 19:00:00'),
('SB000033',  6,  6, 0, 'Monika Seidel',      '2026-01-05 10:00:00'),

-- Event 7: Tech-Meetup KI (creator: user 7)
('SB000034',  7,  7, 1, NULL,                 '2026-01-08 18:00:00'),
('SB000035',  7,  2, 1, NULL,                 '2026-01-09 19:00:00'),
('SB000036',  7, 10, 1, NULL,                 '2026-01-10 17:30:00'),
('SB000037',  7, 12, 1, NULL,                 '2026-01-11 16:00:00'),
('SB000038',  7, 15, 1, NULL,                 '2026-01-12 15:00:00'),
('SB000039',  7,  7, 0, 'Carsten Vogt',       '2026-01-13 09:00:00'),
('SB000040',  7,  7, 0, 'Annika Lorenz',      '2026-01-13 09:10:00'),

-- Event 8: Winterwanderung Tegernsee (creator: user 8)
('SB000041',  8,  8, 1, NULL,                 '2026-01-10 09:00:00'),
('SB000042',  8,  1, 1, NULL,                 '2026-01-11 10:00:00'),
('SB000043',  8,  3, 1, NULL,                 '2026-01-12 11:00:00'),
('SB000044',  8,  5, 1, NULL,                 '2026-01-13 12:00:00'),
('SB000045',  8,  8, 0, 'Jürgen Haas',        '2026-01-14 08:00:00'),

-- Event 9: Bücherclub Januar (creator: user 9)
('SB000046',  9,  9, 1, NULL,                 '2026-01-15 19:00:00'),
('SB000047',  9, 11, 1, NULL,                 '2026-01-16 20:00:00'),
('SB000048',  9, 13, 1, NULL,                 '2026-01-17 18:30:00'),
('SB000049',  9, 15, 1, NULL,                 '2026-01-18 17:00:00'),

-- Event 10: Radtour Elberadweg (creator: user 10)
('SB000050', 10, 10, 1, NULL,                 '2026-01-18 08:00:00'),
('SB000051', 10,  2, 1, NULL,                 '2026-01-19 09:00:00'),
('SB000052', 10,  8, 1, NULL,                 '2026-01-20 10:00:00'),
('SB000053', 10, 10, 0, 'Tanja Berger',       '2026-01-21 07:30:00'),
('SB000054', 10, 10, 0, 'Gerd Köhler',        '2026-01-21 07:45:00'),

-- Event 11: Yoga im Park (creator: user 11)
('SB000055', 11, 11, 1, NULL,                 '2026-01-25 08:00:00'),
('SB000056', 11,  5, 1, NULL,                 '2026-01-26 09:00:00'),
('SB000057', 11,  9, 1, NULL,                 '2026-01-27 07:45:00'),
('SB000058', 11, 13, 1, NULL,                 '2026-01-28 08:30:00'),
('SB000059', 11, 15, 1, NULL,                 '2026-01-29 07:00:00'),

-- Event 12: Netzwerktreffen IT (creator: user 12)
('SB000060', 12, 12, 1, NULL,                 '2026-01-28 17:00:00'),
('SB000061', 12,  2, 1, NULL,                 '2026-01-29 18:00:00'),
('SB000062', 12,  7, 1, NULL,                 '2026-01-30 16:30:00'),
('SB000063', 12, 10, 1, NULL,                 '2026-01-31 15:00:00'),
('SB000064', 12, 14, 1, NULL,                 '2026-02-01 14:00:00'),
('SB000065', 12, 12, 0, 'Olaf Richter',       '2026-02-02 09:00:00'),
('SB000066', 12, 12, 0, 'Ingrid Sommer',      '2026-02-02 09:15:00'),

-- Event 13: Erste-Hilfe-Kurs (creator: user 13)
('SB000067', 13, 13, 1, NULL,                 '2026-02-01 09:00:00'),
('SB000068', 13,  4, 1, NULL,                 '2026-02-02 10:00:00'),
('SB000069', 13,  6, 1, NULL,                 '2026-02-03 11:00:00'),
('SB000070', 13,  9, 1, NULL,                 '2026-02-04 09:30:00'),

-- Event 14: Improvisationstheater (creator: user 14)
('SB000071', 14, 14, 1, NULL,                 '2026-02-06 15:00:00'),
('SB000072', 14,  3, 1, NULL,                 '2026-02-07 16:00:00'),
('SB000073', 14,  7, 1, NULL,                 '2026-02-08 14:30:00'),
('SB000074', 14, 11, 1, NULL,                 '2026-02-09 13:00:00'),
('SB000075', 14, 14, 0, 'Sandra Böhm',        '2026-02-10 10:00:00'),

-- Event 15: Schiclub Frühlingsfahren (creator: user 15)
('SB000076', 15, 15, 1, NULL,                 '2026-02-08 09:00:00'),
('SB000077', 15,  1, 1, NULL,                 '2026-02-09 10:00:00'),
('SB000078', 15,  4, 1, NULL,                 '2026-02-10 11:00:00'),
('SB000079', 15,  8, 1, NULL,                 '2026-02-11 12:00:00'),
('SB000080', 15, 15, 0, 'Ralf Engel',         '2026-02-12 08:00:00'),

-- Event 16: Ostermarkt Standaufbau (creator: user 1, future)
('SB000081', 16,  1, 1, NULL,                 '2026-02-15 10:00:00'),
('SB000082', 16,  3, 1, NULL,                 '2026-02-16 11:00:00'),
('SB000083', 16,  6, 1, NULL,                 '2026-02-17 09:00:00'),
('SB000084', 16,  9, 1, NULL,                 '2026-02-18 08:30:00'),

-- Event 17: JavaScript Deep Dive (creator: user 2, future)
('SB000085', 17,  2, 1, NULL,                 '2026-02-18 09:00:00'),
('SB000086', 17,  7, 1, NULL,                 '2026-02-19 10:00:00'),
('SB000087', 17, 10, 1, NULL,                 '2026-02-20 08:45:00'),
('SB000088', 17, 12, 1, NULL,                 '2026-02-21 09:30:00'),

-- Event 18: Frühjahrsputz (creator: user 3, future)
('SB000089', 18,  3, 1, NULL,                 '2026-02-20 09:00:00'),
('SB000090', 18,  1, 1, NULL,                 '2026-02-21 10:00:00'),
('SB000091', 18,  5, 1, NULL,                 '2026-02-22 08:30:00'),
('SB000092', 18, 11, 1, NULL,                 '2026-02-23 09:00:00'),
('SB000093', 18,  3, 0, 'Karl Mayer',         '2026-02-24 07:00:00'),

-- Event 20: Badminton-Turnier (creator: user 5, future)
('SB000094', 20,  5, 1, NULL,                 '2026-02-24 13:00:00'),
('SB000095', 20,  6, 1, NULL,                 '2026-02-25 14:00:00'),
('SB000096', 20, 14, 1, NULL,                 '2026-02-26 12:30:00'),

-- Event 22: Hackathon (creator: user 7, future)
('SB000097', 22,  7, 1, NULL,                 '2026-02-25 10:00:00'),
('SB000098', 22,  2, 1, NULL,                 '2026-02-26 11:00:00'),
('SB000099', 22, 10, 1, NULL,                 '2026-02-27 09:30:00'),
('SB000100', 22, 12, 1, NULL,                 '2026-02-27 10:00:00'),
('SB000101', 22, 15, 1, NULL,                 '2026-02-27 10:30:00'),
('SB000102', 22,  7, 0, 'Felix Günther',      '2026-02-28 08:00:00'),

-- Event 27: Agile Methoden Workshop (creator: user 12, future)
('SB000103', 27, 12, 1, NULL,                 '2026-02-28 10:00:00'),
('SB000104', 27,  2, 1, NULL,                 '2026-02-28 10:30:00'),
('SB000105', 27,  7, 1, NULL,                 '2026-02-28 11:00:00'),

-- Event 29: Theateraufführung (creator: user 14, future)
('SB000106', 29, 14, 1, NULL,                 '2026-03-01 20:00:00'),
('SB000107', 29,  3, 1, NULL,                 '2026-03-02 21:00:00'),
('SB000108', 29,  9, 1, NULL,                 '2026-03-03 19:30:00'),
('SB000109', 29, 11, 1, NULL,                 '2026-03-04 18:00:00'),
('SB000110', 29, 14, 0, 'Birgit Stern',       '2026-03-05 10:00:00'),
('SB000111', 29, 14, 0, 'Horst Damm',         '2026-03-05 10:05:00'),

-- Event 31: Laufgruppe Halbmarathon (creator: user 1, future)
('SB000112', 31,  1, 1, NULL,                 '2026-03-05 09:00:00'),
('SB000113', 31,  5, 1, NULL,                 '2026-03-06 10:00:00'),
('SB000114', 31,  8, 1, NULL,                 '2026-03-07 08:30:00'),
('SB000115', 31, 10, 1, NULL,                 '2026-03-08 09:00:00'),

-- Event 33: Nachbarschaftsfest (creator: user 3, future)
('SB000116', 33,  3, 1, NULL,                 '2026-03-10 14:00:00'),
('SB000117', 33,  1, 1, NULL,                 '2026-03-11 15:00:00'),
('SB000118', 33,  6, 1, NULL,                 '2026-03-12 13:00:00'),
('SB000119', 33,  9, 1, NULL,                 '2026-03-13 12:00:00'),
('SB000120', 33,  3, 0, 'Erika Haupt',        '2026-03-14 09:00:00'),
('SB000121', 33,  3, 0, 'Norbert Pfeil',      '2026-03-14 09:15:00'),

-- Event 40: Hamburg Triathlon (creator: user 10, future)
('SB000122', 40, 10, 1, NULL,                 '2026-03-01 07:00:00'),
('SB000123', 40, 11, 1, NULL,                 '2026-03-02 08:00:00');
