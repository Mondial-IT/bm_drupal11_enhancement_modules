<?php

declare(strict_types=1);

namespace Drupal\bm_tooltip\Form;

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Admin demo form to showcase bm_tooltip usage.
 */
final class BmTooltipDemoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bm_tooltip_demo_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'bm_tooltip/tooltip';
    $form['#attached']['library'][] = 'bm_tooltip/tooltip-demo';

    $dark_mode = (bool) $form_state->getValue(['themes', 'mode_toggle'], FALSE);

    $form['intro'] = [
      '#type' => 'details',
      '#title' => $this->t('How to use bm_tooltip'),
      '#open' => TRUE,
      'body' => [
        '#markup' => Markup::create('<p>Attach the library <code>bm_tooltip/tooltip</code>, then add the <code>tooltip</code> class plus <code>data-bm-tooltip-content</code> (and optional <code>data-bm-tooltip-theme</code>, <code>data-bm-tooltip-placement</code>, etc.).</p>'),
      ],
    ];

    $form['developer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Developer reference'),
      '#attributes' => [
        'class' => ['bm-tooltip-demo-developer'],
      ],
      'placement' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<p>Placement modifiers use <i>bm-tooltip--*</i> classes.</p>'),
        ],
        'code' => [
          '#markup' => '<code>bm-tooltip--top | bm-tooltip--right | bm-tooltip--bottom | bm-tooltip--left</code>',
        ],
      ],
      'themes' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<p>Theme tokens map to <i>data-bm-tooltip-theme</i> values.</p>'),
        ],
        'code' => [
          '#markup' => '<code>data-bm-tooltip-theme="dark|light|brand|accent"</code>',
        ],
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<p>The tooltip text lives in <i>data-bm-tooltip-content</i>.</p>'),
        ],
        'code' => [
          '#markup' => '<code>data-bm-tooltip-content="Helpful hint"</code>',
        ],
      ],
      'interactive' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<p>Interactive tooltips keep focus inside the popover.</p>'),
        ],
        'code' => [
          '#markup' => '<code>data-bm-tooltip-interactive="true"</code>',
        ],
      ],
      'selector' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<p>Link a hidden HTML block using <i>data-tooltip-selector</i>.</p>'),
        ],
        'code' => [
          '#markup' => '<code>data-tooltip-selector="#tooltip-html-block"</code>',
        ],
      ],
    ];

    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic tooltips'),
      'default' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<span class="bm-tooltip" data-bm-tooltip-content="Default tooltip on hover or focus." data-bm-tooltip-theme="dark">Default (dark)</span>'),
        ],
        'code' => [
          '#markup' => '<code>&lt;span class="bm-tooltip" data-bm-tooltip-content="Default tooltip on hover or focus." data-bm-tooltip-theme="dark"&gt;Default (dark)&lt;/span&gt;</code>',
        ],
      ],
      'top' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<span class="bm-tooltip bm-tooltip--top" data-bm-tooltip-content="Positioned on top." data-bm-tooltip-theme="dark">Top placement</span>'),
        ],
        'code' => [
          '#markup' => '<code>&lt;span class="bm-tooltip bm-tooltip--top" data-bm-tooltip-content="Positioned on top." data-bm-tooltip-theme="dark"&gt;Top placement&lt;/span&gt;</code>',
        ],
      ],
      'interactive' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<span class="bm-tooltip bm-tooltip--accent" data-bm-tooltip-content="Interactive tooltip with clickable link" data-bm-tooltip-interactive="true" data-bm-tooltip-theme="accent"><a href="#" onclick="return false;">Interactive target (accent)</a></span>'),
        ],
        'code' => [
          '#markup' => '<code>&lt;span class="bm-tooltip bm-tooltip--accent" data-bm-tooltip-content="Interactive tooltip with clickable link" data-bm-tooltip-interactive="true" data-bm-tooltip-theme="accent"&gt;&lt;a href="#" onclick="return false;"&gt;Interactive target (accent)&lt;/a&gt;&lt;/span&gt;</code>',
        ],
      ],
    ];

    $form['themes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Themed tooltips'),
      '#attributes' => [
        'id' => 'bm-tooltip-themes',
        'class' => $dark_mode ? ['bm-tooltip-demo--dark'] : [],
      ],
      'mode_toggle' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Dark mode (demo)'),
        '#default_value' => (int) $form_state->getValue(['themes', 'mode_toggle'], 0),
        '#description' => $this->t('Adds class <code>bm-tooltip-demo--dark</code> to this section for visual testing.'),
        '#ajax' => [
          'callback' => '::toggleMode',
          'wrapper' => 'bm-tooltip-themes',
        ],
        '#attributes' => [
          'class' => ['bm-tooltip-mode-toggle'],
        ],
      ],
      'dark' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<span class="bm-tooltip" data-bm-tooltip-content="Dark theme (default)" data-bm-tooltip-theme="dark">Dark theme</span>'),
        ],
        'code' => [
          '#markup' => '<code>&lt;span class="bm-tooltip" data-bm-tooltip-content="Dark theme (default)" data-bm-tooltip-theme="dark"&gt;Dark theme&lt;/span&gt;</code>',
        ],
      ],
      'light' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<span class="bm-tooltip" data-bm-tooltip-content="Light theme" data-bm-tooltip-theme="light">Light theme</span>'),
        ],
        'code' => [
          '#markup' => '<code>&lt;span class="bm-tooltip" data-bm-tooltip-content="Light theme" data-bm-tooltip-theme="light"&gt;Light theme&lt;/span&gt;</code>',
        ],
      ],
      'brand' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<span class="bm-tooltip" data-bm-tooltip-content="Brand theme" data-bm-tooltip-theme="brand">Brand theme</span>'),
        ],
        'code' => [
          '#markup' => '<code>&lt;span class="bm-tooltip" data-bm-tooltip-content="Brand theme" data-bm-tooltip-theme="brand"&gt;Brand theme&lt;/span&gt;</code>',
        ],
      ],
    ];

    $form['form_elements'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Form elements with tooltips'),
      'icon' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<label>Title with tooltip <span class="bm-tooltip bm-tooltip--right" data-bm-tooltip-content="' . $this->t('Helpful hint about this field.') . '" data-bm-tooltip-theme="light">❔</span></label>'),
        ],
        'code' => [
          '#markup' => '<code>&lt;span class="bm-tooltip bm-tooltip--right" data-bm-tooltip-content="' . Html::escape((string) $this->t('Helpful hint about this field.')) . '" data-bm-tooltip-theme="light"&gt;❔&lt;/span&gt;</code>',
        ],
      ],
      'input' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<input class="form-text bm-tooltip bm-tooltip--right" data-bm-tooltip-content="' . $this->t('Helpful hint about this field.') . '" data-bm-tooltip-theme="light"" type="text" placeholder="Example field" />'),
        ],
        'code' => [
          '#markup' => '<code>&lt;input class="form-text bm-tooltip bm-tooltip--right" data-bm-tooltip-content="' . Html::escape((string) $this->t('Helpful hint about this field.')) . '" data-bm-tooltip-theme="light" &lt;/input&gt;</code>',
        ],
      ],
      'button' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<button type="button" class="bm-tooltip" data-bm-tooltip-content="' . $this->t('Clicking is safe; this is a demo.') . '" data-bm-tooltip-theme="dark">' . $this->t('Button with tooltip') . '</button>'),
        ],
        'code' => [
          '#markup' => '<code>&lt;button class="bm-tooltip" data-bm-tooltip-content="' . Html::escape((string) $this->t('Clicking is safe; this is a demo.')) . '" data-bm-tooltip-theme="dark"&gt;' . Html::escape((string) $this->t('Button with tooltip')) . '&lt;/button&gt;</code>',
        ],
      ],
      'html_source' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['bm-tooltip-demo-row']],
        'example' => [
          '#markup' => Markup::create('<div><br /><br /><span class="bm-tooltip" data-tooltip-selector="#tooltip-html-block" data-bm-tooltip-theme="brand">HTML source tooltip</span>'
            . '<div id="tooltip-html-block" data-tooltip style="display:none;"><strong>Rich content</strong><br />This tooltip reads HTML from a linked div.</div></div>'),
        ],
        'code' => [
          '#markup' => '<code>&lt;span class="bm-tooltip" data-tooltip-selector="#tooltip-html-block" data-bm-tooltip-theme="brand"&gt;HTML source tooltip&lt;/span&gt;&lt;div id="tooltip-html-block" data-tooltip&gt;&lt;strong&gt;Rich content&lt;/strong&gt;&lt;br /&gt;This tooltip reads HTML from a linked div.&lt;/div&gt;</code>',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No submit actions; demo only.
  }

  /**
   * Ajax callback for mode toggle.
   */
  public function toggleMode(array &$form, FormStateInterface $form_state): array {
    return $form['themes'];
  }

}
