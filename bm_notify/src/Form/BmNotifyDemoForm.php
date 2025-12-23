<?php

declare(strict_types=1);

namespace Drupal\bm_notify\Form;

use Drupal\bm_core\Form\AjaxMessageFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;

/**
 * Demo form to showcase BM Notify toasts.
 */
final class BmNotifyDemoForm extends AjaxMessageFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bm_notify_demo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $form['#attached']['library'][] = 'bm_notify/notify';

    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bm-notify-demo-wrapper'],
    ];

    $form['wrapper']['description'] = [
      '#markup' => '<p>Use this form to preview BM Notify toast messages in different severities.</p>',
    ];

    $form['wrapper']['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $form_state->getValue('message') ?? $this->t('This is a BM Notify demo.'),
      '#required' => TRUE,
    ];

    $form['wrapper']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'status' => $this->t('Status (success)'),
        'info' => $this->t('Info'),
        'warning' => $this->t('Warning'),
        'error' => $this->t('Error'),
      ],
      '#default_value' => $form_state->getValue('type') ?? 'status',
    ];

    $form['wrapper']['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout (ms)'),
      '#min' => 500,
      '#max' => 20000,
      '#step' => 100,
      '#default_value' => $form_state->getValue('timeout') ?? 4000,
    ];

    $form['wrapper']['actions'] = [
      '#type' => 'actions',
    ];
    $form['wrapper']['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Show notification'),
      '#ajax' => [
        'callback' => '::submitAjax',
        'wrapper' => 'bm-notify-demo-wrapper',
      ],
    ];

    return $form;
  }

  /**
   * AJAX submit handler.
   */
  public function submitAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
    $message = (string) $form_state->getValue('message');
    $type = (string) $form_state->getValue('type');
    $timeout = (int) $form_state->getValue('timeout');

    if ($notify = $this->notify()) {
      // Map type to helper when possible.
      switch ($type) {
        case 'warning':
          $notify->addWarning($message, $timeout);
          break;
        case 'error':
          $notify->addError($message, $timeout);
          break;
        case 'info':
          $notify->addInfo($message, $timeout);
          break;
        default:
          $notify->addStatus($message, $timeout);
      }
    }
    else {
      // Fallback to messenger if notify is unavailable.
      $this->messenger()->addStatus($message);
    }

    $response = new AjaxResponse();
    if ($notify = $this->notify()) {
      $notify->addToResponse($response);
    }

    return $this->appendMessages($response);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // All handling via AJAX.
  }

}
