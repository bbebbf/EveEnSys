# Views — Conventions

## Confirmation Modals

For destructive actions (delete, unenroll, etc.) use an inline Bootstrap modal per row
instead of a separate confirmation page or a shared modal populated via JavaScript.

**Pattern:**
- Create a partial `_action_modal.php` (underscore prefix = partial) in the relevant view subfolder.
- The partial renders both the trigger button and the modal `<div>` together.
- All data is passed as PHP variables set by the caller before `include` — no JavaScript needed.
- Use `??=` defaults in the partial for optional variables.
- Add `text-start` to `modal-content` so inherited `text-align` from table cells does not affect the dialog.

**Example partial signature** (`event/_unenroll_modal.php`):
```php
// Required: $unenrollSubscriberGuid, $unenrollEventGuid, $unenrollSubscriberName
// Optional: $unenrollSource (default ''), $unenrollEventInfo (default null)
```

**Caller usage:**
```php
<?php
  $unenrollSubscriberGuid = $sub->subscriberGuid;
  $unenrollEventGuid      = $event->eventGuid;
  $unenrollSubscriberName = $sub->subscriberName ?? 'Unbekannt';
  include APP_ROOT . '/views/event/_unenroll_modal.php';
?>
```
