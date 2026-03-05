<?php
declare(strict_types=1);

class EventController
{
    public function __construct(
        private EventRepositoryInterface $eventRepo,
        private SessionInterface $session,
        private ViewInterface $view,
        private ResponseInterface $response,
    ) {}

    public function kiosk(): void
    {
        $events = $this->eventRepo->findAllUpcoming(true);
        $this->view->renderStandalone('event/kiosk', ['events' => $events]);
    }

    public function home(): void
    {
        if ($this->session->isLoggedIn()) {
            $this->response->redirect('/events');
        }
        $upcomingEvents = $this->eventRepo->findUpcoming(3);
        $this->view->render('home/index', ['pageTitle' => 'Startseite', 'upcomingEvents' => $upcomingEvents]);
    }

    public function index(): void
    {
        $this->session->requireLogin();
        $isAdmin = $this->session->isAdmin();
        $events  = $this->eventRepo->findAllUpcoming(!$isAdmin);
        $this->view->render('event/index', [
            'pageTitle' => 'Bevorstehende Veranstaltungen',
            'events'    => $events,
            'isAdmin'   => $isAdmin,
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

    public function show(string $guid): void
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
            'pageTitle'        => $event->eventTitle,
            'event'            => $event,
            'subscribers'      => $subscribers,
            'isEnrolledAsSelf' => $isEnrolledAsSelf,
            'subscriberCount'  => count($subscribers),
            'isAdmin'          => $isAdmin,
            'isCreator'        => $isCreator,
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

        if (!$event->eventIsVisible) {
            $this->session->setFlash('error', 'Anmeldungen für versteckte Veranstaltungen sind nicht möglich.');
            $this->response->redirect('/events/' . $guid);
        }

        $delayedCurrentDatetime = APP_CONFIG->getDelayedCurrentDateTime();
        if ($event->eventDate < $delayedCurrentDatetime) {
            $this->session->setFlash('error', 'Anmeldungen für vergangene Veranstaltungen sind nicht möglich.');
            $this->response->redirect('/events/' . $guid);
        }

        if ($event->eventMaxSubscriber !== null) {
            $count = $this->eventRepo->countSubscribers($event->eventId);
            if ($count >= $event->eventMaxSubscriber) {
                $this->session->setFlash('error', 'Diese Veranstaltung ist ausgebucht.');
                $this->response->redirect('/events/' . $guid);
            }
        }

        $type = $req->post('enroll_type', '');
        if ($type === 'self') {
            if ($this->eventRepo->isUserEnrolledAsSelf($event->eventId, $userId)) {
                $this->session->setFlash('error', 'Sie sind bereits für diese Veranstaltung angemeldet.');
                $this->response->redirect('/events/' . $guid);
            }
            $this->eventRepo->createSubscriber($event->eventId, $userId, true, null);
            $this->session->setFlash('success', 'Sie wurden erfolgreich angemeldet.');
        } elseif ($type === 'other') {
            $name = trim($req->post('subscriber_name', ''));
            if ($name === '') {
                $this->session->setFlash('error', 'Bitte geben Sie einen Namen ein.');
                $this->response->redirect('/events/' . $guid);
            }
            if (mb_strlen($name) > 100) {
                $this->session->setFlash('error', 'Der Name darf maximal 100 Zeichen lang sein.');
                $this->response->redirect('/events/' . $guid);
            }
            $this->eventRepo->createSubscriber($event->eventId, $userId, false, $name);
            $this->session->setFlash('success', $name . ' wurde erfolgreich angemeldet.');
        } else {
            $this->response->redirect('/events/' . $guid);
        }

        $this->response->redirect('/events/' . $guid);
    }

    public function showUnenroll(Request $req, string $guid, string $subscriberGuid): void
    {
        $this->session->requireLogin();
        $event      = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();
        $subscriber = $this->eventRepo->findSubscriberByGuid($subscriberGuid) ?? $this->response->abort404();

        if ($subscriber->creatorUserId !== $this->session->getUserId()) {
            $this->response->abort403();
        }

        $source    = $req->get('source') === 'enrolled' ? 'enrolled' : '';
        $cancelUrl = $source === 'enrolled' ? '/events/enrolled' : '/events/' . $event->eventGuid;

        $this->view->render('event/confirm_unenroll', [
            'pageTitle'  => 'Abmeldung bestätigen',
            'event'      => $event,
            'subscriber' => $subscriber,
            'source'     => $source,
            'cancelUrl'  => $cancelUrl,
        ]);
    }

    public function unenroll(Request $req, string $guid, string $subscriberGuid): void
    {
        $this->session->requireLogin();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $event  = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();
        $userId = $this->session->getUserId();

        if (!$this->eventRepo->deleteSubscriber($subscriberGuid, $userId)) {
            $this->session->setFlash('error', 'Anmeldung nicht gefunden oder Sie haben keine Berechtigung, sie zu entfernen.');
        } else {
            $this->session->setFlash('success', 'Anmeldung entfernt.');
        }

        $redirectUrl = $req->post('source') === 'enrolled' ? '/events/enrolled' : '/events/' . $guid;
        $this->response->redirect($redirectUrl);
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

        $msg = $event->eventIsVisible ? 'Veranstaltung ist jetzt versteckt.' : 'Veranstaltung ist jetzt sichtbar.';
        $this->session->setFlash('success', $msg);
        $this->response->redirect('/events/' . $guid);
    }

    public function showCreate(): void
    {
        $this->session->requireLogin();
        $this->view->render('event/form', [
            'pageTitle' => 'Veranstaltung erstellen',
            'event'     => null,
            'errors'    => [],
            'old'       => [],
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
                'pageTitle' => 'Create Event',
                'event'     => null,
                'errors'    => $errors,
                'old'       => $_POST,
            ]);
            return;
        }

        $guid = $this->eventRepo->create($this->session->getUserId(), $data);
        $this->session->setFlash('success', 'Veranstaltung erfolgreich erstellt. Nach einer Prüfung wird sie innerhalb der nächsten Stunden für andere sichtbar sein.');
        $this->response->redirect('/events/' . $guid);
    }

    public function showEdit(Request $req, string $guid): void
    {
        $this->session->requireLogin();
        $event = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();

        if (!$this->eventRepo->isOwner($event->eventId, $this->session->getUserId())) {
            $this->response->abort403();
        }

        $this->view->render('event/form', [
            'pageTitle' => 'Veranstaltung bearbeiten',
            'event'     => $event,
            'errors'    => [],
            'old'       => [],
        ]);
    }

    public function update(Request $req, string $guid): void
    {
        $this->session->requireLogin();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $event = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();

        if (!$this->eventRepo->isOwner($event->eventId, $this->session->getUserId())) {
            $this->response->abort403();
        }

        ['errors' => $errors, 'data' => $data] = $this->validateEventData($req);

        if (!empty($errors)) {
            $this->view->render('event/form', [
                'pageTitle' => 'Edit Event',
                'event'     => $event,
                'errors'    => $errors,
                'old'       => $_POST,
            ]);
            return;
        }

        $this->eventRepo->update($event->eventId, $data);
        $this->session->setFlash('success', 'Veranstaltung erfolgreich aktualisiert.');
        $this->response->redirect('/events/' . $guid);
    }

    public function showDelete(string $guid): void
    {
        $this->session->requireLogin();
        $event = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();

        if (!$this->eventRepo->isOwner($event->eventId, $this->session->getUserId())) {
            $this->response->abort403();
        }

        $this->view->render('event/confirm_delete', [
            'pageTitle' => 'Veranstaltung löschen',
            'event'     => $event,
        ]);
    }

    public function delete(Request $req, string $guid): void
    {
        $this->session->requireLogin();

        if (!$this->session->validateCsrf($req->post('_csrf', ''))) {
            $this->response->abort403();
        }

        $event = $this->eventRepo->findByGuid($guid) ?? $this->response->abort404();

        if (!$this->eventRepo->isOwner($event->eventId, $this->session->getUserId())) {
            $this->response->abort403();
        }

        $this->eventRepo->deleteSubscribersByEvent($event->eventId);
        $this->eventRepo->delete($event->eventId);

        $this->session->setFlash('success', 'Veranstaltung gelöscht.');
        $this->response->redirect('/events');
    }

    private function validateEventData(Request $req): array
    {
        $title       = trim($req->post('event_title', ''));
        $description = trim($req->post('event_description', ''));
        $dateRaw     = trim($req->post('event_date', ''));
        $location    = trim($req->post('event_location', ''));
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

        $eventDate = null;
        if ($dateRaw === '') {
            $errors['event_date'] = 'Datum und Uhrzeit sind erforderlich.';
        } else {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dateRaw)
               ?: DateTime::createFromFormat('Y-m-d H:i', $dateRaw)
               ?: DateTime::createFromFormat('Y-m-d H:i:s', $dateRaw);
            if (!$dt) {
                $errors['event_date'] = 'Bitte geben Sie ein gültiges Datum und eine gültige Uhrzeit ein.';
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
                'event_duration_hours'  => $duration,
                'event_max_subscriber'  => $maxSub,
            ],
        ];
    }
}
