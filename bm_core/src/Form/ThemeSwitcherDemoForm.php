<?php

declare(strict_types=1);

namespace Drupal\bm_core\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Demo form to showcase the bm_theme_switcher component.
 */
final class ThemeSwitcherDemoForm extends FormBase {

  public function getFormId(): string {
    return 'bm_core_theme_switcher_demo_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Ensure component assets are available.
    $form['#attached']['library'][] = 'bm_core/theme_switcher';
    $form['#attached']['library'][] = 'bm_core/theme_switcher_demo';

    // Intro / how-to.
    $form['intro'] = [
      '#type' => 'details',
      '#title' => $this->t('How to use theme switcher'),
      '#open' => TRUE,
      'body' => [
        '#markup' => Markup::create(
          '<p>Attach the library <code>bm_core/theme_switcher</code> and render the component with <code>#theme =&gt; \'bm_theme_switcher\'</code>.</p>'
          . '<p>Click the buttons to toggle <code>data-theme</code> on <code>&lt;html&gt;</code> (light, dark, system).</p>'
        ),
      ],
    ];

    // Demo rows.
    $form['examples'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Component output'),
    ];
    $form['examples']['form'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['bm-demo-row']],
      'example' => [
        '#theme' => 'bm_theme_switcher',
      ],
      'code_php' => [
        '#markup' => '
            <code>
            /* Add to the form to insert buttons */
            \'example\' => [
                \'#theme\' => \'bm_theme_switcher\',
            ],
        </code>',
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No submission; demo-only.
  }

}
