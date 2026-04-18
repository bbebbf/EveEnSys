<?php
declare(strict_types=1);

class LoginEventNotifier
{
    public function __construct(
        private EventRepositoryInterface $eventRepo,
        private SessionInterface $session,
    ) {}

    public function notifyIfNewEventsSince(?\DateTimeImmutable $since): void
    {
        if ($since === null) {
            return;
        }
        $guids = $this->eventRepo->findGuidsNewOrUpdatedSince($since);
        if (count($guids) === 0) {
            return;
        }
        $this->session->setNewOrUpdatedEventGuids($guids);
        $count = count($guids);
        $this->session->setFlash('info',
            "Seit deinem letzten Login gibt es $count neue oder aktualisierte bevorstehende Veranstaltung(en)."
        );
    }
}
