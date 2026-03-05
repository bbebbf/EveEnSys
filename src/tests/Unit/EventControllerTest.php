<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\AbortException;
use Tests\Fakes\FakeResponse;
use Tests\Fakes\RedirectException;

class EventControllerTest extends TestCase
{
    private MockObject        $eventRepo;
    private MockObject        $session;
    private MockObject        $view;
    private FakeResponse      $response;
    private \EventController  $controller;

    protected function setUp(): void
    {
        $this->eventRepo  = $this->createMock(\EventRepositoryInterface::class);
        $this->session    = $this->createMock(\SessionInterface::class);
        $this->view       = $this->createMock(\ViewInterface::class);
        $this->response   = new FakeResponse();

        $this->controller = new \EventController(
            $this->eventRepo,
            $this->session,
            $this->view,
            $this->response,
        );
    }

    // -------------------------------------------------------------------------
    // home()
    // -------------------------------------------------------------------------

    public function test_home_redirects_to_events_when_logged_in(): void
    {
        $this->session->method('isLoggedIn')->willReturn(true);

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/events');

        $this->controller->home();
    }

    public function test_home_renders_with_upcoming_events_for_guest(): void
    {
        $event = $this->makeEvent();

        $this->session->method('isLoggedIn')->willReturn(false);
        $this->eventRepo->expects($this->once())->method('findUpcoming')->with(3)->willReturn([$event]);
        $this->view->expects($this->once())->method('render')->with(
            'home/index',
            $this->callback(fn($d) => $d['upcomingEvents'] === [$event])
        );

        $this->controller->home();
    }

    // -------------------------------------------------------------------------
    // index()
    // -------------------------------------------------------------------------

    public function test_index_shows_only_visible_events_for_regular_user(): void
    {
        $this->session->method('isAdmin')->willReturn(false);
        $this->eventRepo->expects($this->once())->method('findAllUpcoming')->with(true)->willReturn([]);
        $this->view->expects($this->once())->method('render')->with('event/index', $this->anything());

        $this->controller->index();
    }

    public function test_index_shows_all_events_including_hidden_for_admin(): void
    {
        $this->session->method('isAdmin')->willReturn(true);
        $this->eventRepo->expects($this->once())->method('findAllUpcoming')->with(false)->willReturn([]);
        $this->view->expects($this->once())->method('render');

        $this->controller->index();
    }

    // -------------------------------------------------------------------------
    // show()
    // -------------------------------------------------------------------------

    public function test_show_renders_visible_event(): void
    {
        $event = $this->makeEvent(eventIsVisible: true, creatorUserId: 99);

        $this->eventRepo->method('findByGuid')->with('abc')->willReturn($event);
        $this->eventRepo->method('findSubscribersByEvent')->willReturn([]);
        $this->session->method('isAdmin')->willReturn(false);
        $this->session->method('isLoggedIn')->willReturn(false);
        $this->view->expects($this->once())->method('render')->with('event/show', $this->anything());

        $this->controller->show('abc');
    }

    public function test_show_aborts_404_when_event_not_found(): void
    {
        $this->eventRepo->method('findByGuid')->willReturn(null);

        $this->expectException(AbortException::class);
        $this->expectExceptionMessage('HTTP 404');

        $this->controller->show('unknown');
    }

    public function test_show_aborts_404_for_hidden_event_viewed_by_guest(): void
    {
        $event = $this->makeEvent(eventIsVisible: false, creatorUserId: 99);

        $this->eventRepo->method('findByGuid')->willReturn($event);
        $this->session->method('isAdmin')->willReturn(false);
        $this->session->method('isLoggedIn')->willReturn(false);

        $this->expectException(AbortException::class);
        $this->expectExceptionMessage('HTTP 404');

        $this->controller->show('abc');
    }

    public function test_show_allows_admin_to_view_hidden_event(): void
    {
        $event = $this->makeEvent(eventIsVisible: false, creatorUserId: 99);

        $this->eventRepo->method('findByGuid')->willReturn($event);
        $this->eventRepo->method('findSubscribersByEvent')->willReturn([]);
        $this->session->method('isAdmin')->willReturn(true);
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->session->method('getUserId')->willReturn(1);
        $this->view->expects($this->once())->method('render')->with('event/show', $this->anything());

        $this->controller->show('abc');
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    public function test_create_redirects_to_event_on_success(): void
    {
        $_POST = [
            '_csrf'            => 'tok',
            'event_title'      => 'Test Event',
            'event_description'=> '',
            'event_date'       => '2026-06-01T10:00',
            'event_location'   => '',
            'event_duration_hours' => '',
            'event_max_subscriber' => '',
        ];

        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserId')->willReturn(7);
        $this->eventRepo->expects($this->once())->method('create')->willReturn('newguid');
        $this->session->expects($this->once())->method('setFlash');

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/events/newguid');

        $this->controller->create(new \Request());
    }

    public function test_create_renders_form_with_errors_when_title_missing(): void
    {
        $_POST = [
            '_csrf'            => 'tok',
            'event_title'      => '',
            'event_description'=> '',
            'event_date'       => '2026-06-01T10:00',
            'event_location'   => '',
            'event_duration_hours' => '',
            'event_max_subscriber' => '',
        ];

        $this->session->method('validateCsrf')->willReturn(true);
        $this->eventRepo->expects($this->never())->method('create');
        $this->view->expects($this->once())->method('render')->with(
            'event/form',
            $this->callback(fn($d) => isset($d['errors']['event_title']))
        );

        $this->controller->create(new \Request());
    }

    public function test_create_aborts_403_on_invalid_csrf(): void
    {
        $_POST = ['_csrf' => 'bad'];
        $this->session->method('validateCsrf')->willReturn(false);

        $this->expectException(AbortException::class);
        $this->expectExceptionMessage('HTTP 403');

        $this->controller->create(new \Request());
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function test_delete_removes_event_and_redirects(): void
    {
        $_POST = ['_csrf' => 'tok'];
        $event = $this->makeEvent(eventId: 5, eventGuid: 'abc', creatorUserId: 7);

        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserId')->willReturn(7);
        $this->eventRepo->method('findByGuid')->with('abc')->willReturn($event);
        $this->eventRepo->method('isOwner')->with(5, 7)->willReturn(true);
        $this->eventRepo->expects($this->once())->method('deleteSubscribersByEvent')->with(5);
        $this->eventRepo->expects($this->once())->method('delete')->with(5);

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/events');

        $this->controller->delete(new \Request(), 'abc');
    }

    public function test_delete_aborts_403_when_not_owner(): void
    {
        $_POST = ['_csrf' => 'tok'];
        $event = $this->makeEvent(eventId: 5, creatorUserId: 99);

        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserId')->willReturn(7);
        $this->eventRepo->method('findByGuid')->willReturn($event);
        $this->eventRepo->method('isOwner')->willReturn(false);

        $this->expectException(AbortException::class);
        $this->expectExceptionMessage('HTTP 403');

        $this->controller->delete(new \Request(), 'abc');
    }

    // -------------------------------------------------------------------------
    // enroll()
    // -------------------------------------------------------------------------

    public function test_enroll_self_creates_subscriber_and_redirects(): void
    {
        $_POST = ['_csrf' => 'tok', 'enroll_type' => 'self'];
        $event = $this->makeEvent(eventId: 5, eventGuid: 'abc', eventIsVisible: true, eventMaxSubscriber: null);

        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserId')->willReturn(7);
        $this->eventRepo->method('findByGuid')->willReturn($event);
        $this->eventRepo->method('isUserEnrolledAsSelf')->willReturn(false);
        $this->eventRepo->expects($this->once())->method('createSubscriber')->with(5, 7, true, null);

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/events/abc');

        $this->controller->enroll(new \Request(), 'abc');
    }

    public function test_enroll_self_redirects_with_error_when_already_enrolled(): void
    {
        $_POST = ['_csrf' => 'tok', 'enroll_type' => 'self'];
        $event = $this->makeEvent(eventId: 5, eventGuid: 'abc', eventIsVisible: true, eventMaxSubscriber: null);

        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserId')->willReturn(7);
        $this->eventRepo->method('findByGuid')->willReturn($event);
        $this->eventRepo->method('isUserEnrolledAsSelf')->willReturn(true);
        $this->session->expects($this->once())->method('setFlash')->with('error', $this->anything());
        $this->eventRepo->expects($this->never())->method('createSubscriber');

        $this->expectException(RedirectException::class);

        $this->controller->enroll(new \Request(), 'abc');
    }

    public function test_enroll_on_past_event_sets_error_flash_and_redirects(): void
    {
        $_POST = ['_csrf' => 'tok', 'enroll_type' => 'self'];
        $pastEvent = $this->makeEvent(
            eventId:   5,
            eventGuid: 'abc',
            eventIsVisible: true,
            eventDate: new \DateTimeImmutable('2000-01-01 10:00:00'),
        );

        $this->session->method('validateCsrf')->willReturn(true);
        $this->session->method('getUserId')->willReturn(7);
        $this->eventRepo->method('findByGuid')->willReturn($pastEvent);
        $this->session->expects($this->once())
            ->method('setFlash')
            ->with('error', 'Anmeldungen für vergangene Veranstaltungen sind nicht möglich.');
        $this->eventRepo->expects($this->never())->method('createSubscriber');

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('/events/abc');

        $this->controller->enroll(new \Request(), 'abc');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEvent(
        int                  $eventId            = 1,
        string               $eventGuid          = 'abc',
        int                  $creatorUserId      = 1,
        bool                 $eventIsVisible     = true,
        ?int                 $eventMaxSubscriber = null,
        \DateTimeImmutable   $eventDate          = new \DateTimeImmutable('2026-06-01 10:00:00'),
    ): \EventDto {
        return new \EventDto(
            eventId:            $eventId,
            eventGuid:          $eventGuid,
            creatorUserId:      $creatorUserId,
            eventIsNew:         false,
            eventIsVisible:     $eventIsVisible,
            eventTitle:         'Test Event',
            eventDescription:   null,
            eventDate:          $eventDate,
            eventLocation:      null,
            eventDurationHours: null,
            eventMaxSubscriber: $eventMaxSubscriber,
        );
    }
}
