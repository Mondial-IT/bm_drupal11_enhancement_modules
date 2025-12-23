# AJAX Messages: Embedded OR Dialog Popup (Drupal 11)

This document describes a **single base form class** that supports **two interchangeable message display modes**:

- **Embedded** (inline container in the form)
- **Dialog popup** (Drupal AJAX modal dialog)

The mode is controlled by a **single switch on the base form**, with zero duplication and identical developer ergonomics.

---

## 1. Design Goals

- One base class
- One API for adding messages
- Switchable rendering strategy
- No theme overrides
- No message duplication
- Works with AJAX rebuilds
- Drupal 11 compliant

---

## 2. Message Modes

```text
EMBEDDED  → messages render inside the form
DIALOG    → messages render in a modal popup
````

The form decides which mode to use.

---

## 3. Base Class Implementation

```php
<?php

declare(strict_types=1);

namespace Drupal\bm_core\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

abstract class AjaxMessageFormBase extends FormBase {

  public const MESSAGE_MODE_EMBEDDED = 'embedded';
  public const MESSAGE_MODE_DIALOG = 'dialog';

  /**
   * Message rendering mode.
   */
  protected string $messageMode = self::MESSAGE_MODE_EMBEDDED;

  /**
   * DOM id for embedded messages.
   */
  protected string $messageContainerId = 'ajax-messages';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#disable_messages'] = TRUE;

    if ($this->messageMode === self::MESSAGE_MODE_EMBEDDED) {
      $form['messages'] = [
        '#type' => 'container',
        '#weight' => -100,
        '#attributes' => [
          'id' => $this->messageContainerId,
          'class' => ['ajax-message-container'],
        ],
      ];
    }

    return $form;
  }

  /**
   * Render messages according to selected mode.
   */
  protected function renderMessagesAjax(): AjaxResponse {
    return match ($this->messageMode) {
      self::MESSAGE_MODE_DIALOG => $this->renderMessagesDialog(),
      default => $this->renderMessagesEmbedded(),
    };
  }

  /**
   * Embedded message rendering.
   */
  protected function renderMessagesEmbedded(): AjaxResponse {
    $messages = [
      '#type' => 'status_messages',
    ];

    $rendered = \Drupal::service('renderer')->renderRoot($messages);

    $response = new AjaxResponse();
    $response->addCommand(
      new ReplaceCommand('#' . $this->messageContainerId, $rendered)
    );

    return $response;
  }

  /**
   * Dialog popup message rendering.
   */
  protected function renderMessagesDialog(): AjaxResponse {
    $messages = [
      '#type' => 'status_messages',
    ];

    $rendered = \Drupal::service('renderer')->renderRoot($messages);

    $response = new AjaxResponse();
    $response->addCommand(
      new OpenDialogCommand(
        '#ajax-message-dialog',
        new TranslatableMarkup('Status'),
        $rendered,
        [
          'width' => 'auto',
          'dialogClass' => 'ajax-message-dialog',
        ]
      )
    );

    return $response;
  }

  /**
   * Clear embedded messages (no-op for dialog mode).
   */
  protected function clearMessagesAjax(): AjaxResponse {
    $response = new AjaxResponse();

    if ($this->messageMode === self::MESSAGE_MODE_EMBEDDED) {
      $response->addCommand(
        new ReplaceCommand('#' . $this->messageContainerId, '')
      );
    }

    return $response;
  }

}
```

---

## 4. Using Embedded Messages (Default)

```php
class MyForm extends AjaxMessageFormBase {
  protected string $messageMode = self::MESSAGE_MODE_EMBEDDED;
}
```

AJAX submit:

```php
$this->messenger()->addStatus($this->t('Saved.'));
return $this->renderMessagesAjax();
```

Result:

* Messages appear inline
* Cleared on pagination if desired

---

## 5. Using Dialog Popup Messages

```php
class MyForm extends AjaxMessageFormBase {
  protected string $messageMode = self::MESSAGE_MODE_DIALOG;
}
```

AJAX submit stays identical:

```php
$this->messenger()->addStatus($this->t('Saved.'));
return $this->renderMessagesAjax();
```

Result:

* Drupal modal dialog opens
* Contains status/error messages
* No embedded container rendered

---

## 6. Clearing Messages on Pagination / Rebuild

```php
public function pagerAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
  $form_state->setRebuild(TRUE);

  $response = new AjaxResponse();
  $response->addCommand(
    new ReplaceCommand('#bm-wrapper', $form['wrapper'])
  );

  if ($this->messageMode === self::MESSAGE_MODE_EMBEDDED) {
    $response->addCommand(
      new ReplaceCommand('#ajax-messages', '')
    );
  }

  return $response;
}
```

Dialog mode requires no clearing.

---

## 7. Styling the Dialog (Optional)

```css
.ajax-message-dialog .messages {
  margin: 0;
}

.ajax-message-dialog {
  padding: 1rem;
}
```

---

## 8. Developer Rules (Codex / Agents)

```md
### AJAX Messages Standard (Drupal 11)

- All AJAX forms must extend `AjaxMessageFormBase`
- Message rendering mode must be explicitly chosen:
  - embedded (default)
  - dialog
- Forms must never render `status_messages` directly
- AJAX callbacks must use `renderMessagesAjax()`
- No form may target `[data-drupal-messages]`

Rationale:
- Prevents duplication
- Centralizes UX behavior
- Allows global UI changes without refactors
```

---
