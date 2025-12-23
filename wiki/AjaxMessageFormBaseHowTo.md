# How to Use `bm_core/AjaxMessageFormBase` (Drupal 11)

This guide explains how to use the shared `AjaxMessageFormBase` class to control Drupal AJAX messages in a **single, predictable location**, without duplication during form rebuilds.

The base class:
- Disables Drupal’s automatic message injection
- Provides one explicit message container
- Ensures messages only appear when you decide
- Works reliably with AJAX rebuilds, pagination, and partial form replacement

---

## 1. When to Use This Base Class

Use `AjaxMessageFormBase` for any form that:

- Uses `#ajax` callbacks
- Calls `$form_state->setRebuild(TRUE)`
- Displays status/error messages
- Suffers from duplicated messages after AJAX interactions

Typical use cases:
- Data tables
- Wizards
- Paginated admin forms
- ag-Grid–based UIs
- Any Form API–driven AJAX UI

---

## 2. Extend the Base Class

Instead of extending `FormBase`, extend `AjaxMessageFormBase`.

```php
use Drupal\bm_core\Form\AjaxMessageFormBase;

class ExampleAjaxForm extends AjaxMessageFormBase {
````

No other setup is required.

---

## 3. Message Container Placement

The base class automatically injects a single container:

```html
<div id="ajax-messages" class="ajax-message-container"></div>
```

It is rendered:

* Once
* At the top of the form
* Outside all AJAX wrappers

Drupal will **never** auto-render messages elsewhere for this form.

---

## 4. Adding Messages in AJAX Submits

Inside any AJAX submit handler:

```php
public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
  $this->messenger()->addStatus($this->t('Changes saved.'));
  return $this->renderMessagesAjax();
}
```

Messages will appear only in the controlled container.

---

## 5. Clearing Messages on Pagination or Rebuild

For non-submit AJAX callbacks (pagination, filters, page size):

```php
public function pageSizeAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
  $form_state->setRebuild(TRUE);
  return $this->replaceWrapperAjax($form['wrapper'], TRUE);
}
```

This ensures:

* Old messages do not linger
* UI remains clean during navigation

---

## 6. Persisting Messages Across Rebuilds (Optional)

If messages should survive pagination:

```php
$this->persistMessages($form_state);
```

Then conditionally skip clearing:

```php
$clear = !$this->messagesShouldPersist($form_state);
return $this->replaceWrapperAjax($form['wrapper'], $clear);
```

---

## 7. Styling & Animation (Optional)

Add CSS once (global or module library):

```css
.ajax-message-container {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.ajax-message-container:empty {
  opacity: 0;
  transform: translateY(-6px);
}

.ajax-message-container:not(:empty) {
  opacity: 1;
  transform: translateY(0);
}
```

No JavaScript required.

---

## 8. Do NOT Do These Things

Avoid the following patterns when using this base class:

* ❌ Do not use `[data-drupal-messages]`
* ❌ Do not render `#type => status_messages` in the form
* ❌ Do not rely on theme-level message placement
* ❌ Do not return messages inside AJAX wrappers

The base class already handles everything.

---

## 9. Resulting Behavior

✔ One message location
✔ No duplication
✔ Clean AJAX rebuilds
✔ Predictable UX
✔ Drupal 11–compliant

---

# Codex / Agents Instructions

Use the following section verbatim in your internal documentation or agent instructions.

---

## Standard: AJAX Message Handling (Drupal Forms)

All Drupal 11 forms that use AJAX **must** extend `AjaxMessageFormBase`.

Rules:

1. Automatic Drupal message rendering is disabled per form
2. Messages are rendered only via `renderMessagesAjax()`
3. Pagination, filters, and rebuilds must clear messages unless explicitly persisted
4. No form may render `status_messages` directly
5. No AJAX callback may target `[data-drupal-messages]`

Rationale:

* Prevents duplicated messages
* Avoids Form API side effects
* Ensures predictable UI behavior
* Aligns with Drupal core AJAX patterns

Compliance:

* New forms must extend the base class
* Existing forms must be migrated when touched
* Deviations require explicit architectural approval

---

End of document.
