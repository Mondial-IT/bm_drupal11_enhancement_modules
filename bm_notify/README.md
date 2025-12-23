# BM Notify

Lightweight notification helper for Drupal AJAX responses. It mirrors Drupal’s messenger-style methods but renders small toast notifications on the client using the custom `bmNotify` Ajax command.

## What it does
- Adds notification commands to any `AjaxResponse` via `NotifyService::addToResponse()`.
- Provides messenger-style helpers: `addStatus()`, `addWarning()`, `addError()`, `addInfo()`, and the generic `addNotification($message, $type, $timeout)`.
- Front-end JS/CSS (`bm_notify/notify` library) displays toasts in the bottom-right corner.

## How to use
1) Declare a dependency on `bm_notify` (or ensure the service exists) and attach the library if you render messages:
```php
$form['#attached']['library'][] = 'bm_notify/notify';
```
2) Queue notifications in your form/handler:
```php
$notify = \Drupal::service('bm_notify');
$notify->addStatus($this->t('Saved successfully.'));
```
3) Before returning an `AjaxResponse`, append notifications:
```php
$notify->addToResponse($response);
return $response;
```

## Demo
Visit `/admin/blue-marloc/enhancements/notify-demo` (menu: Blue Marloc → Enhancements → Notifications demo) to preview the toasts.

## Styling
Toast colors derive from Claro-style CSS variables in `css/bm-notify.css`. Override the `--bm-notify-*` variables to match your theme.

## Notes
- Works alongside Drupal messenger; use when you prefer unobtrusive toasts over page-level message areas.
- Safe for AJAX forms; no extra routes needed.
