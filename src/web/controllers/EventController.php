<?php
declare(strict_types=1);

class EventController
{
    private EventRepository $eventRepo;

    public function __construct(private mysqli $db)
    {
        $this->eventRepo = new EventRepository($db);
    }

    public function home(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/events');
        }
        $upcomingEvents = $this->eventRepo->findUpcoming(3);
        View::render('home/index', ['pageTitle' => 'Home', 'upcomingEvents' => $upcomingEvents]);
    }

    public function index(): void
    {
        Session::requireLogin();
        $events = $this->eventRepo->findAllUpcoming();
        View::render('event/index', ['pageTitle' => 'Upcoming Events', 'events' => $events]);
    }

    public function indexAll(): void
    {
        Session::requireLogin();
        $events = $this->eventRepo->findAll();
        View::render('event/index', ['pageTitle' => 'All Events', 'events' => $events]);
    }

    public function indexMy(): void
    {
        Session::requireLogin();
        $events = $this->eventRepo->findAllByUser(Session::getUserId());
        View::render('event/index', ['pageTitle' => 'My Events', 'events' => $events]);
    }

    public function show(string $guid): void
    {
        $event           = $this->eventRepo->findByGuid($guid) ?? $this->abort(404);
        $subscribers     = $this->eventRepo->findSubscribersByEvent($event->eventId);
        $isEnrolledAsSelf = Session::isLoggedIn()
            && $this->eventRepo->isUserEnrolledAsSelf($event->eventId, Session::getUserId());
        View::render('event/show', [
            'pageTitle'        => $event->eventTitle,
            'event'            => $event,
            'subscribers'      => $subscribers,
            'isEnrolledAsSelf' => $isEnrolledAsSelf,
            'subscriberCount'  => count($subscribers),
        ]);
    }

    public function enroll(Request $req, string $guid): void
    {
        Session::requireLogin();

        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $event  = $this->eventRepo->findByGuid($guid) ?? $this->abort(404);
        $userId = Session::getUserId();
        $type   = $req->post('enroll_type', '');

        if ($event->eventMaxSubscriber !== null) {
            $count = $this->eventRepo->countSubscribers($event->eventId);
            if ($count >= $event->eventMaxSubscriber) {
                Session::setFlash('error', 'This event is fully booked.');
                $this->redirect('/events/' . $guid);
            }
        }

        if ($type === 'self') {
            if ($this->eventRepo->isUserEnrolledAsSelf($event->eventId, $userId)) {
                Session::setFlash('error', 'You are already enrolled in this event.');
                $this->redirect('/events/' . $guid);
            }
            $this->eventRepo->createSubscriber($event->eventId, $userId, true, null);
            Session::setFlash('success', 'You have been enrolled successfully.');
        } elseif ($type === 'other') {
            $name = trim($req->post('subscriber_name', ''));
            if ($name === '') {
                Session::setFlash('error', 'Please enter a name.');
                $this->redirect('/events/' . $guid);
            }
            if (mb_strlen($name) > 100) {
                Session::setFlash('error', 'Name must not exceed 100 characters.');
                $this->redirect('/events/' . $guid);
            }
            $this->eventRepo->createSubscriber($event->eventId, $userId, false, $name);
            Session::setFlash('success', $name . ' has been enrolled successfully.');
        } else {
            $this->redirect('/events/' . $guid);
        }

        $this->redirect('/events/' . $guid);
    }

    public function unenroll(Request $req, string $guid, string $subscriberId): void
    {
        Session::requireLogin();

        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $event  = $this->eventRepo->findByGuid($guid) ?? $this->abort(404);
        $userId = Session::getUserId();

        if (!$this->eventRepo->deleteSubscriber((int)$subscriberId, $userId)) {
            Session::setFlash('error', 'Enrollment not found or you do not have permission to remove it.');
        } else {
            Session::setFlash('success', 'Enrollment removed.');
        }

        $this->redirect('/events/' . $guid);
    }

    public function showCreate(): void
    {
        Session::requireLogin();
        View::render('event/form', [
            'pageTitle' => 'Create Event',
            'event'     => null,
            'errors'    => [],
            'old'       => [],
        ]);
    }

    public function create(Request $req): void
    {
        Session::requireLogin();

        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        ['errors' => $errors, 'data' => $data] = $this->validateEventData($req);

        if (!empty($errors)) {
            View::render('event/form', [
                'pageTitle' => 'Create Event',
                'event'     => null,
                'errors'    => $errors,
                'old'       => $_POST,
            ]);
            return;
        }

        $guid = $this->eventRepo->create(Session::getUserId(), $data);
        Session::setFlash('success', 'Event created successfully.');
        $this->redirect('/events/' . $guid);
    }

    public function showEdit(Request $req, string $guid): void
    {
        Session::requireLogin();
        $event = $this->eventRepo->findByGuid($guid) ?? $this->abort(404);

        if (!$this->eventRepo->isOwner($event->eventId, Session::getUserId())) {
            $this->abort(403);
        }

        View::render('event/form', [
            'pageTitle' => 'Edit Event',
            'event'     => $event,
            'errors'    => [],
            'old'       => [],
        ]);
    }

    public function update(Request $req, string $guid): void
    {
        Session::requireLogin();

        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $event = $this->eventRepo->findByGuid($guid) ?? $this->abort(404);

        if (!$this->eventRepo->isOwner($event->eventId, Session::getUserId())) {
            $this->abort(403);
        }

        ['errors' => $errors, 'data' => $data] = $this->validateEventData($req);

        if (!empty($errors)) {
            View::render('event/form', [
                'pageTitle' => 'Edit Event',
                'event'     => $event,
                'errors'    => $errors,
                'old'       => $_POST,
            ]);
            return;
        }

        $this->eventRepo->update($event->eventId, $data);
        Session::setFlash('success', 'Event updated successfully.');
        $this->redirect('/events/' . $guid);
    }

    public function showDelete(string $guid): void
    {
        Session::requireLogin();
        $event = $this->eventRepo->findByGuid($guid) ?? $this->abort(404);

        if (!$this->eventRepo->isOwner($event->eventId, Session::getUserId())) {
            $this->abort(403);
        }

        View::render('event/confirm_delete', [
            'pageTitle' => 'Delete Event',
            'event'     => $event,
        ]);
    }

    public function delete(Request $req, string $guid): void
    {
        Session::requireLogin();

        if (!Session::validateCsrf($req->post('_csrf', ''))) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $event = $this->eventRepo->findByGuid($guid) ?? $this->abort(404);

        if (!$this->eventRepo->isOwner($event->eventId, Session::getUserId())) {
            $this->abort(403);
        }

        $this->eventRepo->deleteSubscribersByEvent($event->eventId);
        $this->eventRepo->delete($event->eventId);

        Session::setFlash('success', 'Event deleted.');
        $this->redirect('/events');
    }

    private function validateEventData(Request $req): array
    {
        $title       = trim($req->post('event_title', ''));
        $description = trim($req->post('event_description', ''));
        $dateRaw     = trim($req->post('event_date', ''));
        $durationRaw = trim($req->post('event_duration_hours', ''));
        $maxSubRaw   = trim($req->post('event_max_subscriber', ''));

        $errors = [];

        if ($title === '') {
            $errors['event_title'] = 'Title is required.';
        } elseif (mb_strlen($title) > 150) {
            $errors['event_title'] = 'Title must not exceed 150 characters.';
        }

        $eventDate = null;
        if ($dateRaw === '') {
            $errors['event_date'] = 'Date and time are required.';
        } else {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dateRaw)
               ?: DateTime::createFromFormat('Y-m-d H:i', $dateRaw)
               ?: DateTime::createFromFormat('Y-m-d H:i:s', $dateRaw);
            if (!$dt) {
                $errors['event_date'] = 'Please enter a valid date and time.';
            } else {
                $eventDate = $dt->format('Y-m-d H:i:s');
            }
        }

        $duration = null;
        if ($durationRaw !== '') {
            if (!is_numeric($durationRaw) || (float)$durationRaw <= 0) {
                $errors['event_duration_hours'] = 'Duration must be a positive number.';
            } else {
                $duration = (float)$durationRaw;
            }
        }

        $maxSub = null;
        if ($maxSubRaw !== '') {
            if (!ctype_digit($maxSubRaw) || (int)$maxSubRaw <= 0) {
                $errors['event_max_subscriber'] = 'Max subscribers must be a positive whole number.';
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
                'event_duration_hours'  => $duration,
                'event_max_subscriber'  => $maxSub,
            ],
        ];
    }

    private function abort(int $code): never
    {
        http_response_code($code);
        $errorView = APP_ROOT . '/views/errors/' . $code . '.php';
        if (file_exists($errorView)) {
            include APP_ROOT . '/views/layout/header.php';
            include $errorView;
            include APP_ROOT . '/views/layout/footer.php';
        } else {
            echo '<h1>' . $code . '</h1>';
        }
        exit;
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}
