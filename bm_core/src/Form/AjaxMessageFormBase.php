<?php
/**
 *
 * ## 4. Using Embedded Messages (Default)
 *
 * ```
 * class MyForm extends AjaxMessageFormBase {
 *  protected string $messageMode = self::MESSAGE_MODE_EMBEDDED;
 * }
 *```
 *
 * AJAX submit:
 *```
 *  $this->messenger()->addStatus($this->t('Saved.'));
 *  return $this->renderMessagesAjax();
 *```
 *
 * Result:
 *
 * Messages appear inline
 *
 * Cleared on pagination if desired
 *
 * ## 5. Using Dialog Popup Messages
 *
 * ```
 * class MyForm extends AjaxMessageFormBase {
 *  protected string $messageMode = self::MESSAGE_MODE_DIALOG;
 * }
 *```
 *
 * AJAX submit stays identical:
 * ```
 * $this->messenger()->addStatus($this->t('Saved.'));
 *  return $this->renderMessagesAjax();
 * ```
 *
 * Result:
 *
 * Drupal modal dialog opens
 *
 * Contains status/error messages
 *
 * No embedded container rendered
 *
 * ## 6. Clearing Messages on Pagination / Rebuild
 * ```
 * public function pagerAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
 *  $form_state->setRebuild(TRUE);
 *
 *  $response = new AjaxResponse();
 *  $response->addCommand(
 *  new ReplaceCommand('#bm-wrapper', $form['wrapper'])
 * );
 *
 * if ($this->messageMode === self::MESSAGE_MODE_EMBEDDED) {
 *  $response->addCommand(
 *    new ReplaceCommand('#ajax-messages', '')
 *  );
 * }
 *
 * return $response;
 * }
 *```
 *
 * Dialog mode requires no clearing.
 *
 */
declare(strict_types=1);

namespace Drupal\bm_core\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CommandInterface;
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

    /* include bm_notify for messages as notifications */
    if (\Drupal::hasService('bm_notify')) {
      $form['#attached']['library'][] = 'bm_notify/notify';
    }

    // Dialog mode needs the AJAX dialog library so the OpenDialogCommand works.
    if ($this->messageMode === self::MESSAGE_MODE_DIALOG) {
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

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
    // Lightweight on-form debug to help verify message handling.
    $form['messages_debug'] = [
      '#type' => 'container',
      '#weight' => -99,
      '#attributes' => ['class' => ['ajax-message-debug']],
      '#markup' => sprintf(
        '<div><strong>Ajax message mode:</strong> %s (container: %s)</div>',
        $this->messageMode,
        $this->messageContainerId
      ),
    ];

    return $form;
  }

  /**
   * Helper to log message rendering/debug info.
   */
  protected function logMessageRender(string $context, $rendered): void {
    $length = is_string($rendered) ? strlen($rendered) : 0;
    \Drupal::logger('bm_core')->debug(
      'Ajax messages render (@context) mode=@mode length=@len',
      [
        '@context' => $context,
        '@mode' => $this->messageMode,
        '@len' => $length,
      ]
    );
  }

  /**
   * Render messages according to selected mode.
   * Note with the included bm_notify module notifications.
   */
  protected function renderMessagesAjax(): AjaxResponse {
    $response = match ($this->messageMode) {
      self::MESSAGE_MODE_DIALOG => $this->renderMessagesDialog(),
      default => $this->renderMessagesEmbedded(),
    };

    if ($notify = $this->notify()) {
      $notify->addToResponse($response);
    }

    return $response;
  }

  /**
   * Embedded message rendering.
   */
  protected function renderMessagesEmbedded(): AjaxResponse {
    $messages = [
      '#type' => 'status_messages',
    ];

    $rendered = \Drupal::service('renderer')->renderRoot($messages);
    $this->logMessageRender('embedded', $rendered);

    $has_notify = ($notify = $this->notify()) ? $notify->hasNotifications() : FALSE;
    $rendered_trim = trim((string) $rendered);

    // Nothing to render: skip message container replace; still allow notify.
    if ($rendered_trim === '') {
      $response = new AjaxResponse();
      if ($has_notify && $notify) {
        $notify->addToResponse($response);
      }
      return $response;
    }

    $response = new AjaxResponse();
    $response->addCommand(
      new ReplaceCommand('#' . $this->messageContainerId, $rendered)
    );
    // Append notify commands if available.
    if ($notify = $this->notify()) {
      $notify->addToResponse($response);
    }

    return $response;
  }

  /**
   * Append rendered message commands to an existing response.
   */
  protected function appendMessages(AjaxResponse $response): AjaxResponse {
    $messages_response = $this->renderMessagesAjax();
    $commands =& $response->getCommands();
    $commands = array_merge($commands, $messages_response->getCommands());
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
    $this->logMessageRender('dialog', $rendered);

    $has_notify = ($notify = $this->notify()) ? $notify->hasNotifications() : FALSE;
    $rendered_trim = trim((string) $rendered);

    // No messages: do not open a dialog; only add notifications if present.
    if ($rendered_trim === '') {
      $response = new AjaxResponse();
      if ($has_notify && $notify) {
        $notify->addToResponse($response);
      }
      return $response;
    }

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
    // Append notify commands if available.
    if ($notify = $this->notify()) {
      $notify->addToResponse($response);
    }

    return $response;
  }

  /**
   * Clear embedded messages (no-op for dialog mode).
   */
  protected function clearMessagesAjax(): AjaxResponse {
    $response = new AjaxResponse();

    if ($this->messageMode === self::MESSAGE_MODE_EMBEDDED) {
      $this->logMessageRender('clear', '');
      $response->addCommand(
        new ReplaceCommand('#' . $this->messageContainerId, [
          '#type' => 'container',
          '#attributes' => [
            'id' => $this->messageContainerId,
            'class' => ['ajax-message-container'],
          ],
        ])
      );
    }

    return $response;
  }

  /**
   * inserting the bm_notify module to display notification messages.
   *
   * @return object|null
   */
  protected function notify(): ?object {
    return \Drupal::hasService('bm_notify')
      ? \Drupal::service('bm_notify')
      : NULL;
  }

  /**
   * Display a message a notification, using bm_notify module.
   * @param string $message
   * @param string $type
   * @param int $timeout
   * @return void
   */
  protected function addNotification(
    string $message,
    string $type = 'info',
    int $timeout = 4000
  ): void {
    if ($notify = $this->notify()) {
      $notify->addNotification($message, $type, $timeout);
    }
  }

}
