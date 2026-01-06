<?php

declare(strict_types=1);

namespace Drupal\bm_panels\Form;

use Drupal\bm_panels\Service\PanelService;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function bm_main_panel_title_and_help;

/**
 * Demonstrates the bm_panels element with configuration options.
 */
class PanelsBasicExampleForm extends FormBase {

  private const COUNT_STATE_KEY = 'bm_panels_basic_counts';
  private const ENTITY_SELECTION_KEY = 'bm_panels_basic_entity';

  public function __construct(
    protected PanelService $panelService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('bm_panels.service'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bm_panels_basic_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Initialization: keep panel-level counters and selections scoped per card.
    $instance = 'bm_panels_basic';
    $this->initializePanelCounts($form_state, ['hero', 'secondary', 'notes']);
    $this->initializeEntitySelection($form_state);

    $form['title_bar'] = [
      '#type' => 'markup',
      '#markup' => bm_main_panel_title_and_help($this->t('BM Panels basic demo'), $this->t('Shows draggable/removable controls, palettes, and reset behavior.')),
    ];

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Each array child of <code>#type = bm_panels</code> becomes a draggable panel. Provide <code>#title</code> + <code>#description</code> to fill the handle, and use <code>#panel_description</code> when the tooltip needs markup. This demo exposes <code>#bm_panels[draggable]</code>, <code>#bm_panels[removable]</code>, width, height, palettes, and reset helpers so authors can tailor the UX.') . '</p>' .
        '<p>' . $this->t('Treat each card like a sub-form: <code>$this->panelEntitySelector()</code> stores the entity type in form state while <code>$this->panelEntityFields()</code> reads it to refresh the field list over AJAX, so the cards communicate without bloating <code>buildForm()</code>.') . '</p>',
    ];

    $codeExample = <<<'PHP'
$form["panels"]["hero"] = $this->panelHero($form_state);
$form["panels"]["entity_fields"] = $this->panelEntityFields($form_state);
PHP;
    $form['code_example'] = [
      '#type' => 'details',
      '#title' => $this->t('Example configuration'),
      '#open' => FALSE,
      'snippet' => [
        '#type' => 'markup',
        '#markup' => Markup::create('<pre><code class="language-php">' . Html::escape($codeExample) . '</code></pre>'),
      ],
    ];

    // Build the panels container and delegate content to dedicated helpers.
    $form['panels'] = [
      '#type' => 'bm_panels',
      '#collection' => 'bm_panels_basic',
      '#instance_id' => $instance,
      '#columns' => 12,
      '#show_panel_meta' => TRUE,
      '#palette' => [
        [
          'id' => 'insights',
          'label' => $this->t('Insights panel'),
          'title' => $this->t('Insights panel'),
          'description' => $this->t('Configured from palette.'),
          'panel_description' => $this->t('A freshly added card with KPIs or alerts.'),
          'markup' => '<p>' . $this->t('A freshly added card with KPIs or alerts.') . '</p>',
          'width' => 3,
          'height' => 2,
        ],
        [
          'id' => 'note',
          'label' => $this->t('Note panel'),
          'title' => $this->t('Note panel'),
          'description' => $this->t('Draggable disabled example.'),
          'panel_description' => $this->t('Use removed tabs to reinsert panels instantly.'),
          'markup' => '<p>' . $this->t('Use removed tabs to reinsert panels instantly.') . '</p>',
          'width' => 2,
          'height' => 2,
          'draggable' => FALSE,
        ],
      ],
    ];
    $form['panels']['hero'] = $this->panelHero($form_state);
    $form['panels']['secondary'] = $this->panelSecondary($form_state);
    $form['panels']['notes'] = $this->panelNotes($form_state);
    $form['panels']['entity_selector'] = $this->panelEntitySelector($form_state);
    $form['panels']['entity_fields'] = $this->panelEntityFields($form_state);

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset layout'),
      '#submit' => ['::resetLayout'],
      '#limit_validation_errors' => [],
      '#attributes' => ['class' => ['button', 'button--secondary']],
      '#bm_panels_action' => 'reset',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    if (($trigger['#bm_panels_action'] ?? '') === 'reset') {
      return;
    }
    $this->messenger()->addStatus($this->t('Drag, resize, add, and remove panels to see state persistence in action.'));
  }

  public function resetLayout(array &$form, FormStateInterface $form_state): void {
    $this->panelService->resetPanelState('bm_panels_basic');
    $this->messenger()->addStatus($this->t('Panel positions were reset to their defaults.'));
  }

  /**
   * Panel builders.
   */
  protected function panelHero(FormStateInterface $form_state): array {
    $wrapper = 'bm-panels-hero';
    $count = $this->getPanelCount($form_state, 'hero');
    return [
      '#title' => $this->t('Hero panel'),
      '#description' => $this->t('Foundational metrics tile.'),
      '#panel_description' => $this->t('Ideal for headlines or top-level messaging.'),
      '#bm_panels' => [
        'draggable' => TRUE,
        'removable' => FALSE,
        'width' => 6,
        'height' => 4,
        'x' => 0,
        'y' => 0,
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['id' => $wrapper],
        'summary' => [
          '#markup' => '<p>' . $this->t('Each panel acts like a sub-form; the Count button illustrates isolated handlers.') . '</p>',
        ],
        'counter' => [
          '#markup' => '<p>' . $this->t('Count total: @count', ['@count' => $count]) . '</p>',
        ],
        'actions' => [
          'count' => [
            '#type' => 'submit',
            '#value' => $this->t('Count'),
            '#name' => 'hero-count',
            '#ajax' => [
              'callback' => '::ajaxPanelHero',
              'wrapper' => $wrapper,
            ],
            '#limit_validation_errors' => [],
            '#validate' => ['::validatePanelHero'],
            '#submit' => ['::submitPanelHero'],
          ],
        ],
      ],
    ];
  }

  protected function panelSecondary(FormStateInterface $form_state): array {
    $wrapper = 'bm-panels-secondary';
    $count = $this->getPanelCount($form_state, 'secondary');
    return [
      '#title' => $this->t('Secondary panel'),
      '#description' => $this->t('Layer supporting CTAs.'),
      '#bm_panels' => [
        'draggable' => TRUE,
        'removable' => TRUE,
        'width' => 3,
        'height' => 3,
        'x' => 6,
        'y' => 0,
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['id' => $wrapper],
        'summary' => [
          '#markup' => '<p>' . $this->t('Close, restore, and interact with this panel independently.') . '</p>',
        ],
        'counter' => [
          '#markup' => '<p>' . $this->t('Count total: @count', ['@count' => $count]) . '</p>',
        ],
        'actions' => [
          'count' => [
            '#type' => 'submit',
            '#value' => $this->t('Count'),
            '#name' => 'secondary-count',
            '#ajax' => [
              'callback' => '::ajaxPanelSecondary',
              'wrapper' => $wrapper,
            ],
            '#limit_validation_errors' => [],
            '#validate' => ['::validatePanelSecondary'],
            '#submit' => ['::submitPanelSecondary'],
          ],
        ],
      ],
    ];
  }

  protected function panelNotes(FormStateInterface $form_state): array {
    $wrapper = 'bm-panels-notes';
    $count = $this->getPanelCount($form_state, 'notes');
    return [
      '#title' => $this->t('Helper panel'),
      '#description' => $this->t('Shows how IDs become selectors.'),
      '#panel_description' => Markup::create($this->t('Panel IDs become <code>data-panel-id</code> attributes to hook your JavaScript behaviors.')),
      '#bm_panels' => [
        'draggable' => FALSE,
        'removable' => TRUE,
        'width' => 2,
        'height' => 2,
        'x' => 9,
        'y' => 0,
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['id' => $wrapper],
        'summary' => [
          '#markup' => '<p>' . $this->t('Even static panels can run their own submit handlers.') . '</p>',
        ],
        'counter' => [
          '#markup' => '<p>' . $this->t('Count total: @count', ['@count' => $count]) . '</p>',
        ],
        'actions' => [
          'count' => [
            '#type' => 'submit',
            '#value' => $this->t('Count'),
            '#name' => 'notes-count',
            '#ajax' => [
              'callback' => '::ajaxPanelNotes',
              'wrapper' => $wrapper,
            ],
            '#limit_validation_errors' => [],
            '#validate' => ['::validatePanelNotes'],
            '#submit' => ['::submitPanelNotes'],
          ],
        ],
      ],
    ];
  }

  protected function panelEntitySelector(FormStateInterface $form_state): array {
    $wrapper = 'bm-panels-entity-selector';
    $options = $this->getEntityTypeOptions();
    $selection = $this->getEntitySelection($form_state);
    return [
      '#title' => $this->t('Panel entity'),
      '#description' => $this->t('Select an entity type and pass the selection to the fields panel.'),
      '#bm_panels' => [
        'draggable' => TRUE,
        'removable' => FALSE,
        'width' => 4,
        'height' => 3,
        'x' => 0,
        'y' => 5,
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['id' => $wrapper],
        'entity_type' => [
          '#type' => 'select',
          '#title' => $this->t('Entity type'),
          '#options' => $options,
          '#default_value' => $selection,
        ],
        'actions' => [
          'apply' => [
            '#type' => 'submit',
            '#value' => $this->t('Apply'),
            '#name' => 'entity-select-apply',
            '#ajax' => [
              'callback' => '::ajaxPanelEntityFields',
              'wrapper' => 'bm-panels-entity-fields',
            ],
            '#limit_validation_errors' => [['panels', 'entity_selector', 'content', 'entity_type']],
            '#validate' => ['::validatePanelEntitySelector'],
            '#submit' => ['::submitPanelEntitySelector'],
          ],
        ],
      ],
    ];
  }

  protected function panelEntityFields(FormStateInterface $form_state): array {
    $wrapper = 'bm-panels-entity-fields';
    $selection = $this->getEntitySelection($form_state);
    return [
      '#title' => $this->t('Panel fields'),
      '#description' => $this->t('Lists the fields for the selected entity type.'),
      '#bm_panels' => [
        'draggable' => TRUE,
        'removable' => TRUE,
        'width' => 6,
        'height' => 3,
        'x' => 4,
        'y' => 5,
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => ['id' => $wrapper],
        'summary' => [
          '#markup' => '<p>' . $this->t('Currently showing fields for <strong>@type</strong>.', ['@type' => $selection]) . '</p>',
        ],
        'fields' => [
          '#theme' => 'item_list',
          '#items' => $this->buildFieldList($selection),
        ],
        'actions' => [
          'refresh' => [
            '#type' => 'submit',
            '#value' => $this->t('Refresh list'),
            '#name' => 'entity-fields-refresh',
            '#ajax' => [
              'callback' => '::ajaxPanelEntityFields',
              'wrapper' => $wrapper,
            ],
            '#limit_validation_errors' => [],
            '#validate' => ['::validatePanelEntityFields'],
            '#submit' => ['::submitPanelEntityFields'],
          ],
        ],
      ],
    ];
  }

  /**
   * AJAX callbacks.
   */
  public function ajaxPanelHero(array &$form, FormStateInterface $form_state): array {
    return $form['panels']['hero']['content'];
  }

  public function ajaxPanelSecondary(array &$form, FormStateInterface $form_state): array {
    return $form['panels']['secondary']['content'];
  }

  public function ajaxPanelNotes(array &$form, FormStateInterface $form_state): array {
    return $form['panels']['notes']['content'];
  }

  public function ajaxPanelEntityFields(array &$form, FormStateInterface $form_state): array {
    return $form['panels']['entity_fields']['content'];
  }

  /**
   * Validation + submit handlers.
   */
  public function validatePanelHero(array &$form, FormStateInterface $form_state): void {}

  public function submitPanelHero(array &$form, FormStateInterface $form_state): void {
    $this->incrementPanelCount($form_state, 'hero');
    $form_state->setRebuild(TRUE);
  }

  public function validatePanelSecondary(array &$form, FormStateInterface $form_state): void {}

  public function submitPanelSecondary(array &$form, FormStateInterface $form_state): void {
    $this->incrementPanelCount($form_state, 'secondary');
    $form_state->setRebuild(TRUE);
  }

  public function validatePanelNotes(array &$form, FormStateInterface $form_state): void {}

  public function submitPanelNotes(array &$form, FormStateInterface $form_state): void {
    $this->incrementPanelCount($form_state, 'notes');
    $form_state->setRebuild(TRUE);
  }

  public function validatePanelEntitySelector(array &$form, FormStateInterface $form_state): void {
    $selection = $form_state->getValue(['panels', 'entity_selector', 'content', 'entity_type']);
    if (empty($selection)) {
      $form_state->setError($form['panels']['entity_selector']['content']['entity_type'], $this->t('Please choose an entity type.'));
    }
  }

  public function submitPanelEntitySelector(array &$form, FormStateInterface $form_state): void {
    $selection = $form_state->getValue(['panels', 'entity_selector', 'content', 'entity_type']);
    if (!empty($selection)) {
      $this->setEntitySelection($form_state, (string) $selection);
    }
    $form_state->setRebuild(TRUE);
  }

  public function validatePanelEntityFields(array &$form, FormStateInterface $form_state): void {}

  public function submitPanelEntityFields(array &$form, FormStateInterface $form_state): void {
    $form_state->setRebuild(TRUE);
  }

  /**
   * Helper methods for state and lookups.
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

  private function initializeEntitySelection(FormStateInterface $form_state): void {
    if ($form_state->has(self::ENTITY_SELECTION_KEY)) {
      return;
    }
    $options = $this->getEntityTypeOptions();
    $form_state->set(self::ENTITY_SELECTION_KEY, array_key_first($options));
  }

  private function getEntitySelection(FormStateInterface $form_state): string {
    return (string) $form_state->get(self::ENTITY_SELECTION_KEY);
  }

  private function setEntitySelection(FormStateInterface $form_state, string $entity_type): void {
    $form_state->set(self::ENTITY_SELECTION_KEY, $entity_type);
  }

  private function getEntityTypeOptions(): array {
    $definitions = $this->entityTypeManager->getDefinitions();
    $options = [];
    foreach ($definitions as $id => $definition) {
      if ($definition->getGroup() === 'content') {
        $options[$id] = $definition->getLabel();
      }
    }
    if (!$options) {
      foreach ($definitions as $id => $definition) {
        $options[$id] = $definition->getLabel();
      }
    }
    return $options ?: ['node' => $this->t('Content')];
  }

  private function buildFieldList(string $entity_type_id): array {
    try {
      $definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    }
    catch (\Exception $exception) {
      return [$this->t('Unable to load fields for @type.', ['@type' => $entity_type_id])];
    }
    if (!$definitions) {
      return [$this->t('No fields found for @type.', ['@type' => $entity_type_id])];
    }
    $items = [];
    foreach ($definitions as $field_name => $definition) {
      $items[] = $this->t('@field (@type)', [
        '@field' => $field_name,
        '@type' => $definition->getType(),
      ]);
    }
    return $items;
  }

}
