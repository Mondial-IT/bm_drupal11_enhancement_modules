<?php

declare(strict_types=1);

namespace Drupal\bm_aggrid\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Placeholder for future advanced edit configuration.
 */
class AggridDisplayEditForm extends FormBase {

  public function getFormId(): string {
    return 'bm_aggrid_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['message'] = [
      '#markup' => $this->t('Advanced editor configuration will be implemented in later phases.'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
