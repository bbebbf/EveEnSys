<?php
declare(strict_types=1);

class EventController
{
    public function __construct(
        private EventRepositoryInterface $eventRepo,
        private UserRepositoryInterface $userRepo,
        private SessionInterface $session,
        private ViewInterface $view,
        private ResponseInterface $response,
        private EmailSender $emailSender,
    ) {}

    public function kiosk(): void
    {
        $events = $this->eventRepo->findAllUpcoming(true);
        $this->view->renderStandalone('event/kiosk', ['events' => $events]);
    }

    public function home(): void
    {
        $upcomingEvents = $this->eventRepo->findUpcoming(3);
        $this->view->render('home/index', [
            'headerBannerVisible' => true,
            'pageTitle' => 'Startseite',
            'upcomingEvents' => $upcomingEvents]);
    }

    public function index(): void
    {
        $isAdmin = $this->session->isAdmin();
        $events  = $this->eventRepo->findAllUpcoming(!$isAdmin);
        $this->view->render('event/index', [
            'pageTitle' => 'Bevorstehende Veranstaltungen',
            'events'    => $events,
            'isAdmin'   => $isAdmin,
            'origin'    => 'upcoming',
        ]);
    }

    public function indexAll(): void
    {
        $this->session->requireLogin();
        $isAdmin = $this->session->isAdmin();
        $events  = $this->eventRepo->findAll(!$isAdmin);
        $this->view->render('event/index', [
            'pageTitle' => 'Alle Veranstaltungen',
            'events'    => $events,
            'isAdmin'   => $isAdmin,
            'origin'    => 'all',
        ]);
    }

    public function indexMy(): void
    {
        $this->session->requireLogin();
        $events = $this->eventRepo->findAllByUser($this->session->getUserId());
        $this->view->render('event/index', [
            'pageTitle' => 'Meine Veranstaltungen',
            'events'    => $events,
            'isAdmin'   => $this->session->isAdmin(),
            'origin'    => 'my',
        ]);
    }

    public function indexNew(): void
    {
        $this->session->requireLogin();

        $events = $this->eventRepo->findAllNew(!$this->session->isAdmin());
        $this->view->render('event/index', [
            'pageTitle' => 'Neue Veranstaltungen',
            'events'    => $events,
            'isAdmin'   => $this->session->isAdmin(),
            'origin'    => 'new',
        ]);
    }

    public function indexEnrolled(): void
    {
        $this->session->requireLogin();
        $enrollments = $this->eventRepo->findEnrolledByUser($this->session->getUserId());
        $this->view->render('event/enrolled', [
            'pageTitle'   => 'Meine Anmeldungen',
            'enrollments' => $enrollments,
        ]);
    }

    public function show(Request $req, string $guid): void
    {
        $event     = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();
        $isAdmin   = $this->session->isAdmin();
        $isCreator = $this->session->isLoggedIn() && $this->session->getUserId() === $event->creatorUserId;

        if (!$event->eventIsVisible && !$isAdmin && !$isCreator) {
            $this->response->abort404();
        }

        $subscribers      = $this->eventRepo->findSubscribersByEvent($event->eventId);
        $isEnrolledAsSelf = $this->session->isLoggedIn()
            && $this->eventRepo->isUserEnrolledAsSelf($event->eventId, $this->session->getUserId());

        $this->view->render('event/show', [
            'pageTitle'         => $event->eventTitle,
            'event'             => $event,
            'subscribers'       => $subscribers,
            'isEnrolledAsSelf'  => $isEnrolledAsSelf,
            'subscriberCount'   => count($subscribers),
            'isAdmin'           => $isAdmin,
            'isCreator'         => $isCreator,
            'origin'            => $req->get('origin', ''),
            'enrollmentAllowed' => $this->isEnrollmentForEventAllowed($event, count($subscribers)),
        ]);
    }

    public function enroll(Request $req, string $guid): void
    {
        $this->session->requireLogin();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $event  = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();
        $userId = $this->session->getUserId();

        $allowed = $this->isEnrollmentForEventAllowed($event, $this->eventRepo->countSubscribers($event->eventId));
        if ($allowed['allowed'] === false) {
            $this->session->setFlash('error', $allowed['message']);
            $this->response->redirect('/events/' . $guid);
        }

        $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $eventLink = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/events/' . $guid;
        $eventDate = event_date_out($event->eventDate);

        $creator      = $this->userRepo->findById($event->creatorUserId);
        $creatorEmail = $creator?->userEmail;
        $creatorName  = $creator?->userName;

        $type = $req->post('enroll_type', '');
        if ($type === 'self') {
            if ($this->eventRepo->isUserEnrolledAsSelf($event->eventId, $userId)) {
                $this->session->setFlash('error', 'Du bist bereits für diese Veranstaltung angemeldet.');
                $this->response->redirect('/events/' . $guid);
            }
            $this->eventRepo->createSubscriber($event->eventId, $userId, true, null);
            $ccEmail = ($creatorEmail !== null && $creatorEmail !== $this->session->getUserEmail()) ? $creatorEmail : null;
            $this->emailSender->sendEnrolledEmail(
                $this->session->getUserEmail(),
                $this->session->getUserName(),
                $this->session->getUserName(),
                true,
                $event->eventTitle,
                $eventDate,
                $eventLink,
                $event,
                $ccEmail,
                $ccEmail !== null ? $creatorName : null,
            );
            $this->session->setFlash('success', 'Du wurdest erfolgreich angemeldet.');
        } elseif ($type === 'other') {
            $name = trim($req->post('subscriber_name', ''));
            if ($name === '') {
                $this->session->setFlash('error', 'Bitte gib einen Namen ein.');
                $this->response->redirect('/events/' . $guid);
            }
            if (mb_strlen($name) > 100) {
                $this->session->setFlash('error', 'Der Name darf maximal 100 Zeichen lang sein.');
                $this->response->redirect('/events/' . $guid);
            }
            $this->eventRepo->createSubscriber($event->eventId, $userId, false, $name);
            $ccEmail = ($creatorEmail !== null && $creatorEmail !== $this->session->getUserEmail()) ? $creatorEmail : null;
            $this->emailSender->sendEnrolledEmail(
                $this->session->getUserEmail(),
                $this->session->getUserName(),
                $name,
                false,
                $event->eventTitle,
                $eventDate,
                $eventLink,
                $event,
                $ccEmail,
                $ccEmail !== null ? $creatorName : null,
            );
            $this->session->setFlash('success', $name . ' wurde erfolgreich angemeldet.');
        } else {
            $this->response->redirect('/events/' . $guid);
        }

        $this->response->redirect('/events/' . $guid);
    }

    public function unenroll(Request $req, string $guid, string $subscriberGuid): void
    {
        $this->session->requireLogin();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $event      = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();
        $userId     = $this->session->getUserId();
        $isAdmin    = $this->session->isAdmin();
        $subscriber = $this->eventRepo->findSubscriberByGuid($subscriberGuid);

        if (!$this->eventRepo->deleteSubscriber($subscriberGuid, $userId, $isAdmin)) {
            $this->session->setFlash('error', 'Anmeldung nicht gefunden oder du hast keine Berechtigung, sie zu entfernen.');
        } else {
            $unenrolledUserName = 'Unbekannt';
            if ($subscriber !== null) {
                $creator            = $this->userRepo->findById($event->creatorUserId);
                $adminActingOnOther = $isAdmin && $subscriber->creatorUserId !== $userId;
                if ($adminActingOnOther) {
                    $enrollingUser  = $this->userRepo->findById($subscriber->creatorUserId);
                    $creatorCcEmail = ($creator?->userEmail !== null && $creator->userEmail !== $enrollingUser->userEmail && $creator->userEmail !== $this->session->getUserEmail()) ? $creator->userEmail : null;
                    $unenrolledUserName = $subscriber->subscriberName ?? $enrollingUser->userName;
                    $this->emailSender->sendUnenrolledEmail(
                        $enrollingUser->userEmail,
                        $enrollingUser->userName,
                        $unenrolledUserName,
                        $subscriber->subscriberIsCreator,
                        $event->eventTitle,
                        $this->session->getUserEmail(),
                        $this->session->getUserName(),
                        $creatorCcEmail,
                        $creatorCcEmail !== null ? $creator->userName : null,
                    );
                } else {
                    $creatorCcEmail = ($creator?->userEmail !== null && $creator->userEmail !== $this->session->getUserEmail()) ? $creator->userEmail : null;
                    $unenrolledUserName = $subscriber->subscriberName ?? $this->session->getUserName();
                    $this->emailSender->sendUnenrolledEmail(
                        $this->session->getUserEmail(),
                        $this->session->getUserName(),
                        $unenrolledUserName,
                        $subscriber->subscriberIsCreator,
                        $event->eventTitle,
                        $creatorCcEmail,
                        $creatorCcEmail !== null ? $creator->userName : null,
                    );
                }
            }
            $this->session->setFlash('success', $unenrolledUserName . ' wurde erfolgreich abgemeldet.');
        }

        $redirectUrl = $req->post('source') === 'enrolled' ? '/events/enrolled' : '/events/' . $guid;
        $this->response->redirect($redirectUrl);
    }

    public function downloadIcal(string $guid): void
    {
        $event     = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();
        $isAdmin   = $this->session->isAdmin();

        if (!$event->eventIsVisible && !$isAdmin) {
            $this->response->abort404();
        }

        $ics      = IcsGenerator::generate($event);
        $filename = FileTools::sanitizeFileName($event->eventTitle . '.ics');

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($ics));
        echo $ics;
    }

    public function toggleVisible(Request $req, string $guid): void
    {
        $this->session->requireLogin();

        if (!$this->session->isAdmin()) {
            $this->response->abort403();
        }

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $event = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();
        $this->eventRepo->setVisible($event->eventId, !$event->eventIsVisible);

        $newStateText = ($event->eventIsVisible ? 'versteckt' : ($event->eventIsActivated ? '' : 'aktiviert und ') . 'sichtbar');
        $msg = 'Veranstaltung "' . $event->eventTitle . '" ist jetzt ' . $newStateText . '.';
        $this->session->setFlash('success', $msg);
        $this->response->redirect($this->getRedirectUrlFromRequest($req, $guid));
    }

    public function showCreate(): void
    {
        $this->session->requireLogin();
        $this->view->render('event/form', [
            'pageTitle'    => 'Veranstaltung erstellen',
            'event'        => null,
            'errors'       => [],
            'old'          => [],
            'minEventDate' => $this->getMinEventDateStr(),
            'maxEventDate' => $this->getMaxEventDateStr(),
        ]);
    }

    public function create(Request $req): void
    {
        $this->session->requireLogin();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        ['errors' => $errors, 'data' => $data] = $this->validateEventData($req);

        if (!empty($errors)) {
            $this->view->render('event/form', [
                'pageTitle'    => 'Veranstaltung erstellen',
                'event'        => null,
                'errors'       => $errors,
                'old'          => $_POST,
                'minEventDate' => $this->getMinEventDateStr(),
                'maxEventDate' => $this->getMaxEventDateStr(),
            ]);
            return;
        }

        $guid        = $this->eventRepo->create($this->session->getUserId(), $data);
        $createdEvent = $this->eventRepo->findByGuid($guid);

        $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $eventLink = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/events/' . $guid;
        $eventDate = event_date_out(new DateTimeImmutable($data['event_date']));
        if ($createdEvent !== null) {
            $this->emailSender->sendEventCreatedEmail(
                $this->session->getUserEmail(),
                $this->session->getUserName(),
                $data['event_title'],
                $eventDate,
                $eventLink,
                $createdEvent,
            );
        }

        $msg = 'Veranstaltung erfolgreich erstellt.';
        if ($createdEvent !== null && !$createdEvent->eventIsActivated) {
             $msg .= ' Nach einer Prüfung wird sie innerhalb der nächsten Stunden für andere sichtbar sein.';
        }

        $this->session->setFlash('success', $msg);
        $this->response->redirect('/events/' . $guid);
    }

    public function showEdit(Request $req, string $guid): void
    {
        $this->session->requireLogin();
        $event = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();

        if (!$this->session->isAdmin() && !$this->eventRepo->isOwner($event->eventId, $this->session->getUserId())) {
            $this->response->abort403();
        }

        $this->view->render('event/form', [
            'pageTitle'    => 'Veranstaltung bearbeiten',
            'event'        => $event,
            'errors'       => [],
            'old'          => [],
            'minEventDate' => $this->getMinEventDateStr(),
            'maxEventDate' => $this->getMaxEventDateStr(),
        ]);
    }

    public function update(Request $req, string $guid): void
    {
        $this->session->requireLogin();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $event = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();

        if (!$this->session->isAdmin() && !$this->eventRepo->isOwner($event->eventId, $this->session->getUserId())) {
            $this->response->abort403();
        }

        ['errors' => $errors, 'data' => $data] = $this->validateEventData($req);

        if (!empty($errors)) {
            $this->view->render('event/form', [
                'pageTitle'    => 'Veranstaltung bearbeiten',
                'event'        => $event,
                'errors'       => $errors,
                'old'          => $_POST,
                'minEventDate' => $this->getMinEventDateStr(),
                'maxEventDate' => $this->getMaxEventDateStr(),
            ]);
            return;
        }

        $this->eventRepo->update($event->eventId, $data);
        $this->session->setFlash('success', 'Veranstaltung erfolgreich aktualisiert.');
        $this->response->redirect('/events/' . $guid);
    }

    public function delete(Request $req, string $guid): void
    {
        $this->session->requireLogin();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $event = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();

        if (!$this->session->isAdmin() && !$this->eventRepo->isOwner($event->eventId, $this->session->getUserId())) {
            $this->response->abort403();
        }

        $this->eventRepo->deleteSubscribersByEvent($event->eventId);
        $this->eventRepo->delete($event->eventId);

        $adminActingOnOther = $this->session->isAdmin() && $event->creatorUserId !== $this->session->getUserId();
        if ($adminActingOnOther) {
            $creator = $this->userRepo->findById($event->creatorUserId);
            $this->emailSender->sendEventDeletedEmail(
                $creator->userEmail,
                $creator->userName,
                $event->eventTitle,
                $this->session->getUserEmail(),
                $this->session->getUserName(),
            );
        } else {
            $this->emailSender->sendEventDeletedEmail(
                $this->session->getUserEmail(),
                $this->session->getUserName(),
                $event->eventTitle,
            );
        }

        $this->session->setFlash('success', 'Veranstaltung "' . $event->eventTitle . '" gelöscht.');
        $this->response->redirect($this->getRedirectUrlFromRequest($req));
    }

    private function validateEventData(Request $req): array
    {
        $title       = trim($req->post('event_title', ''));
        $description = trim($req->post('event_description', ''));
        $dateRaw     = trim($req->post('event_date', ''));
        $location    = trim($req->post('event_location', ''));
        $responsible = trim($req->post('event_responsible', ''));
        $durationRaw = trim($req->post('event_duration_hours', ''));
        $maxSubRaw   = trim($req->post('event_max_subscriber', ''));

        $errors = [];

        if ($title === '') {
            $errors['event_title'] = 'Der Titel ist erforderlich.';
        } elseif (mb_strlen($title) > 150) {
            $errors['event_title'] = 'Der Titel darf maximal 150 Zeichen lang sein.';
        }

        if (mb_strlen($location) > 150) {
            $errors['event_location'] = 'Der Veranstaltungsort darf maximal 150 Zeichen lang sein.';
        }

        if (mb_strlen($responsible) > 150) {
            $errors['event_responsible'] = 'Die verantwortliche Person darf maximal 150 Zeichen lang sein.';
        }

        $eventDate = null;
        if ($dateRaw === '') {
            $errors['event_date'] = 'Datum und Uhrzeit sind erforderlich.';
        } else {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dateRaw)
               ?: DateTime::createFromFormat('Y-m-d H:i', $dateRaw)
               ?: DateTime::createFromFormat('Y-m-d H:i:s', $dateRaw);
            if (!$dt) {
                $errors['event_date'] = 'Bitte gib ein gültiges Datum und eine gültige Uhrzeit ein.';
            } elseif ($dt < $this->getMinEventDate()) {
                $errors['event_date'] = 'Das Datum muss mindestens ' . event_date_out($this->getMinEventDate()) . ' sein.';
            } elseif ($dt > $this->getMaxEventDate()) {
                $errors['event_date'] = 'Das Datum darf höchstens ' . event_date_out($this->getMaxEventDate()) . ' sein.';
            } else {
                $eventDate = $dt->format('Y-m-d H:i:s');
            }
        }

        $duration = null;
        if ($durationRaw !== '') {
            if (!is_numeric($durationRaw) || (float)$durationRaw <= 0) {
                $errors['event_duration_hours'] = 'Die Dauer muss eine positive Zahl sein.';
            } else {
                $duration = (float)$durationRaw;
            }
        }

        $maxSub = null;
        if ($maxSubRaw !== '') {
            if (!ctype_digit($maxSubRaw) || (int)$maxSubRaw <= 0) {
                $errors['event_max_subscriber'] = 'Die maximale Teilnehmerzahl muss eine positive ganze Zahl sein.';
            } else {
                $maxSub = (int)$maxSubRaw;
            }
        }

        return [
            'errors' => $errors,
            'data'   => [
                'event_title'           => $title,
                'event_description'     => $description !== '' ? $description : null,
                'event_date'            => $eventDate,
                'event_location'        => $location !== '' ? $location : null,
                'event_responsible'     => $responsible !== '' ? $responsible : null,
                'event_duration_hours'  => $duration,
                'event_max_subscriber'  => $maxSub,
            ],
        ];
    }

    private function isEnrollmentForEventAllowed(EventDto $event, int $subscriberCount): array
    {
        if ($event->eventMaxSubscriber !== null && $subscriberCount >= $event->eventMaxSubscriber) {
            return [
                'isFull' => true,
                'allowed' => false,
                'message' => 'Diese Veranstaltung ist ausgebucht.'
            ];
        }

        if (!$event->eventIsVisible) {
            return [
                'isFull' => false,
                'allowed' => false,
                'message' => 'Anmeldungen für versteckte Veranstaltungen sind nicht möglich.'
            ];
        }

        if ($event->eventDate < APP_CONFIG->getDelayedCurrentDateTime()) {
            return [
                'isFull' => false,
                'allowed' => false,
                'message' => 'Anmeldungen für vergangene Veranstaltungen sind nicht möglich.'
            ];
        }

        $allowed = APP_CONFIG->isEnrollmentWindowOpen();
        if ($allowed['open'] === false) {
            return [
                'isFull' => false,
                'allowed' => false,
                'message' => $allowed['message'],
            ];
        }

        return [
            'isFull' => false,
            'allowed' => true,
        ];
    }

    private function getMinEventDate(): DateTime
    {
        $dt = new DateTime();
        $eventDateFrom = APP_CONFIG->getEventDateRangeFrom();
        if ($eventDateFrom !== null && $eventDateFrom > $dt) {
            $dt = $eventDateFrom;
        }
        return $dt;
    }

    private function getMinEventDateStr(): string
    {
        $dt = $this->getMinEventDate();
        return $dt !== null ? $dt->format('Y-m-d\TH:i') : '';
    }

    private function getMaxEventDate(): DateTime
    {
        $dt = new DateTime();
        $dt->modify('+6 months');
        $eventDateTo = APP_CONFIG->getEventDateRangeTo();
        if ($eventDateTo !== null && $eventDateTo < $dt) {
            $dt = $eventDateTo;
        }
        return $dt;
    }

    private function getMaxEventDateStr(): string
    {
        $dt = $this->getMaxEventDate();
        return $dt !== null ? $dt->format('Y-m-d\TH:i') : '';
    }

    private function getRedirectUrlFromRequest($req, $eventGuid = ''): string
    {         
        return match($req->post('origin')) {
            'upcoming' => '/events',
            'all'      => '/events/all',
            'new'      => '/events/new',
            'my'       => '/events/my',
            default    => '/events/' . $eventGuid,
        };
    }
}
