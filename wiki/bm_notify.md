# BM Notify

Lightweight toast notifications for Drupal AJAX responses, part of **BM Drupal Enhancements**.

## Overview
- Provides a `bm_notify` service with messenger-style helpers: `addStatus()`, `addWarning()`, `addError()`, `addInfo()`, and `addNotification($message, $type, $timeout)`.
- Front-end library `bm_notify/notify` adds the custom Ajax command `bmNotify` that renders toasts in the bottom-right corner.
- Toast colors derive from Claro-inspired CSS variables in `css/bm-notify.css`.

## For developers
1) Ensure the module is enabled. Attach the library when you use it:
```php
$form['#attached']['library'][] = 'bm_notify/notify';
```
2) Queue notifications:
```php
\Drupal::service('bm_notify')->addStatus($this->t('Saved.'));
```
3) Append them to your `AjaxResponse`:
```php
\Drupal::service('bm_notify')->addToResponse($response);
return $response;
```
If you use `AjaxMessageFormBase`, it will append notifications automatically when rendering messages.

## For site users
- Notifications appear as small colored toasts (success/status, info, warning, error) at the bottom-right of the screen.
- They auto-dismiss after a short timeout; no page reload needed.

## Styling
Override the `--bm-notify-*` variables in `css/bm-notify.css` to match your theme.

## Demo
- Path: `/admin/blue-marloc/enhancements/notify-demo`
- Menu: Blue Marloc → Enhancements → Notifications demo
