<?php

declare(strict_types=1);

namespace Drupal\bm_panels\Form;

use Drupal\bm_panels\Service\PanelService;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function bm_main_panel_title_and_help;

/**
 * Demonstrates how panel metadata and AJAX routes could be consumed.
 */
class PanelsAjaxExampleForm extends FormBase {

  private const COUNT_STATE_KEY = 'bm_panels_ajax_counts';

  public function __construct(
    protected PanelService $panelService,
    protected TimeInterface $time,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('bm_panels.service'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bm_panels_ajax_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $collection = 'bm_panels_ajax';
    $metadata = $this->panelService->getPanelMetadata('analytics_demo');
    $updatedTimestamp = $metadata['updated'] ?? $this->time->getRequestTime();
    $ajaxUrl = Url::fromRoute('bm_panels.state_load', ['collection' => $collection]);
    $this->initializePanelCounts($form_state, ['metadata', 'ajax_hint']);

    $form['title_bar'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>BM Panels module - Demonstrates form type <b>`bm_panels`</b>, with panel additions, metadata overlays, and state loading.</h2>'),
    ];

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Panels are persisted via the JSON routes, so dragging/resizing here mirrors real dashboards. Palette buttons create new cards, removed cards appear as “restore” pills, and panel handles always display the child element’s <code>#title</code>/<code>#description</code>.') . '</p>',
    ];

    $form['tips'] = [
      '#type' => 'details',
      '#title' => $this->t('Developer tips'),
      '#open' => TRUE,
      'content' => [
        '#markup' => '<ul><li>' . $this->t('Use <code>PanelService::savePanelState()</code> to persist coordinates via the secure controller.') . '</li><li>' . $this->t('Call %route for read-only JSON payloads.', ['%route' => $ajaxUrl->toString()]) . '</li></ul>',
      ],
    ];

$twigExample = <<<'TWIG'
{{ bm_tooltip(panel_description|striptags,
              panel_title|default(panel_id),
              { theme: 'dark', position: 'bottom', class: 'bm-panel__title-text' }) }}
TWIG;

    $form['twig_markup'] = [
      '#type' => 'details',
      '#title' => $this->t('Twig handle markup'),
      '#open' => FALSE,
      'snippet' => [
        '#type' => 'markup',
        '#markup' => Markup::create('<pre><code class="language-twig">' . Html::escape($twigExample) . '</code></pre>'),
      ],
    ];

    $form['panels_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bm-panels-ajax-wrapper'],
    ];

    $form['panels_wrapper']['panels'] = [
      '#type' => 'bm_panels',
      '#collection' => $collection,
      '#instance_id' => 'bm_panels_ajax_instance',
      '#columns' => 12,
      '#show_panel_meta' => TRUE,
      '#palette' => [
        [
          'id' => 'alerts',
          'label' => $this->t('Alerts panel'),
          'title' => $this->t('Alerts panel'),
          'description' => $this->t('Draggable and removable.'),
          'markup' => '<p>' . $this->t('Drag me anywhere and I will persist through AJAX reloads.') . '</p>',
          'width' => 4,
          'height' => 2,
          'removable' => TRUE,
        ],
      ],
    ];

    $form['panels_wrapper']['panels']['metadata'] = $this->panelMetadata($form_state, $metadata, $updatedTimestamp);

    $form['panels_wrapper']['panels']['ajax_hint'] = $this->panelAjaxHint($form_state, $ajaxUrl);

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset AJAX layout'),
      '#submit' => ['::resetAjaxLayout'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxResetLayout',
        'wrapper' => 'bm-panels-ajax-wrapper',
        'disable-refocus' => TRUE,
        'effect' => 'fade',
      ],
      '#bm_panels_action' => 'reset',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    if (($trigger['#bm_panels_action'] ?? '') === 'reset') {
      return;
    }
    $this->messenger()->addStatus($this->t('Drag, resize, and add panels to observe persisted JSON layouts.'));
  }

  public function resetAjaxLayout(array &$form, FormStateInterface $form_state): void {
    $this->panelService->resetPanelState('bm_panels_ajax');
    $this->messenger()->addStatus($this->t('AJAX example reset to defaults.'));
    $form_state->setRebuild(TRUE);
  }

  /**
   * Panel builders.
   */
  protected function panelMetadata(FormStateInterface $form_state, array $metadata, int $timestamp): array {
    $wrapper = 'bm-panels-metadata';
    $count = $this->getPanelCount($form_state, 'metadata');
    return [
      '#title' => $this->t('Panel metadata'),
      '#description' => $this->t('Sourced from PanelService.'),
      '#bm_panels' => [
        'draggable' => TRUE,
        'removable' => FALSE,
        'width' => 4,
        'height' => 3,
        'x' => 0,
        'y' => 0,
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['id' => $wrapper],
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            $this->t('ID: @id', ['@id' => $metadata['id']]),
            $this->t('Label: @label', ['@label' => $metadata['label']]),
            $this->t('Last generated: @date', ['@date' => date('c', $timestamp)]),
          ],
        ],
        'counter' => [
          '#markup' => '<p>' . $this->t('Panel count: @count', ['@count' => $count]) . '</p>',
        ],
        'actions' => [
          'count' => [
            '#type' => 'submit',
            '#value' => $this->t('Count'),
            '#name' => 'metadata-count',
            '#ajax' => [
              'callback' => '::ajaxPanelMetadata',
              'wrapper' => $wrapper,
            ],
            '#limit_validation_errors' => [],
            '#validate' => ['::validatePanelMetadata'],
            '#submit' => ['::submitPanelMetadata'],
          ],
        ],
      ],
    ];
  }

  protected function panelAjaxHint(FormStateInterface $form_state, Url $ajaxUrl): array {
    $wrapper = 'bm-panels-ajax-hint';
    $count = $this->getPanelCount($form_state, 'ajax_hint');
    return [
      '#title' => $this->t('AJAX hint'),
      '#panel_description' => $this->t('Highlights load and save routes.'),
      '#bm_panels' => [
        'draggable' => TRUE,
        'removable' => TRUE,
        'width' => 3,
        'height' => 2,
        'x' => 4,
        'y' => 0,
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['id' => $wrapper],
        'description' => [
          '#markup' => '<p>' . $this->t('Lazy load content from %route or swap the route for your own controller.', ['%route' => $ajaxUrl->toString()]) . '</p>',
        ],
        'counter' => [
          '#markup' => '<p>' . $this->t('Panel count: @count', ['@count' => $count]) . '</p>',
        ],
        'actions' => [
          'count' => [
            '#type' => 'submit',
            '#value' => $this->t('Count'),
            '#name' => 'ajax-hint-count',
            '#ajax' => [
              'callback' => '::ajaxPanelAjaxHint',
              'wrapper' => $wrapper,
            ],
            '#limit_validation_errors' => [],
            '#validate' => ['::validatePanelAjaxHint'],
            '#submit' => ['::submitPanelAjaxHint'],
          ],
        ],
      ],
    ];
  }

  /**
   * AJAX callbacks.
   */
  public function ajaxPanelMetadata(array &$form, FormStateInterface $form_state): array {
    return $form['panels_wrapper']['panels']['metadata']['content'];
  }

  public function ajaxPanelAjaxHint(array &$form, FormStateInterface $form_state): array {
    return $form['panels_wrapper']['panels']['ajax_hint']['content'];
  }

  public function ajaxResetLayout(array &$form, FormStateInterface $form_state): AjaxResponse {
    $element = $form['panels_wrapper']['panels'];
    $instance_id = $element['#instance_id'] ?? 'bm_panels_ajax_instance';
    $toolbar_id = $element['#toolbar_id'] ?? ('bm-panels-toolbar-' . $instance_id);
    $grid_id = $element['#grid_id'] ?? ('bm-panels-grid-' . $instance_id);

    $response = new AjaxResponse();
    if (!empty($element['#toolbar'])) {
      $response->addCommand(new ReplaceCommand('#' . $toolbar_id, $element['#toolbar']));
    }

    $grid_element = $element;
    $grid_element['#render_toolbar'] = FALSE;
    $grid_element['#render_wrapper'] = FALSE;
    $response->addCommand(new ReplaceCommand('#' . $grid_id, $grid_element));

    return $response;
  }

  /**
   * Validation + submit handlers.
   */
  public function validatePanelMetadata(array &$form, FormStateInterface $form_state): void {}

  public function submitPanelMetadata(array &$form, FormStateInterface $form_state): void {
    $this->incrementPanelCount($form_state, 'metadata');
    $form_state->setRebuild(TRUE);
  }

  public function validatePanelAjaxHint(array &$form, FormStateInterface $form_state): void {}

  public function submitPanelAjaxHint(array &$form, FormStateInterface $form_state): void {
    $this->incrementPanelCount($form_state, 'ajax_hint');
    $form_state->setRebuild(TRUE);
  }

  /**
   * Storage helpers.
   */
  private function initializePanelCounts(FormStateInterface $form_state, array $panels): void {
    $counts = $form_state->get(self::COUNT_STATE_KEY) ?? [];
    foreach ($panels as $panel_id) {
      $counts[$panel_id] = $counts[$panel_id] ?? 0;
    }
    $form_state->set(self::COUNT_STATE_KEY, $counts);
  }

  private function getPanelCount(FormStateInterface $form_state, string $panel_id): int {
    $counts = $form_state->get(self::COUNT_STATE_KEY) ?? [];
    return (int) ($counts[$panel_id] ?? 0);
  }

  private function incrementPanelCount(FormStateInterface $form_state, string $panel_id): void {
    $counts = $form_state->get(self::COUNT_STATE_KEY) ?? [];
    $counts[$panel_id] = ((int) ($counts[$panel_id] ?? 0)) + 1;
    $form_state->set(self::COUNT_STATE_KEY, $counts);
  }

}
