<?php

declare(strict_types=1);

namespace Drupal\bm_aggrid\Form;

use Drupal\bm_aggrid\Service\AggridConfigService;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AggridDisplayConfigForm extends ConfigFormBase {

  protected const CONFIG_NAME = 'bm_aggrid.settings';

  protected EntityTypeManagerInterface $entityTypeManager;

  protected EntityFieldManagerInterface $fieldManager;

  protected AggridConfigService $configService;

  public static function create(ContainerInterface $container): static {
    /** @var static $instance */
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->fieldManager = $container->get('entity_field.manager');
    $instance->configService = $container->get('bm_aggrid.config');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [static::CONFIG_NAME];
  }

  public function getFormId(): string {
    return 'bm_aggrid_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::CONFIG_NAME);
    $display_configs = $config->get('display_configs') ?? [];

    $form['intro'] = [
      '#markup' => $this->t('<p>Define AG Grid displays by choosing an entity type, bundle, and fields. Each configuration can be rendered via routes or embedded in layouts.</p>'),
    ];

    $form['display_configs'] = [
      '#type' => 'details',
      '#title' => $this->t('Grid definitions'),
      '#open' => TRUE,
    ];

    $entity_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $id => $definition) {
      if ($definition->entityClassImplements('Drupal\\Core\\Entity\\ContentEntityInterface')) {
        $entity_options[$id] = $definition->getLabel();
      }
    }

    $index = 0;
    foreach ($display_configs as $config_id => $definition) {
      $form['display_configs'][$config_id] = $this->buildConfigSubform($definition, $entity_options, $config_id);
      $index++;
    }

    $form['new_config'] = $this->buildConfigSubform([], $entity_options, 'new_' . $index);

    return parent::buildForm($form, $form_state);
  }

  protected function buildConfigSubform(array $definition, array $entity_options, string $delta): array {
    $entity_type = $definition['entity_type'] ?? '_none';
    $bundle_options = $entity_type !== '_none'
      ? $this->configService->getBundleOptions($entity_type)
      : ['_none' => $this->t('- Select -')];
    $field_options = $entity_type !== '_none' && ($definition['bundle'] ?? FALSE)
      ? $this->configService->getFieldOptions($entity_type, $definition['bundle'])
      : [];

    return [
      '#type' => 'details',
      '#title' => $definition['label'] ?? $this->t('New display'),
      '#tree' => TRUE,
      '#open' => FALSE,
      'label' => [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $definition['label'] ?? '',
      ],
      'id' => [
        '#type' => 'machine_name',
        '#title' => $this->t('Machine name'),
        '#default_value' => $definition['id'] ?? '',
        '#disabled' => !empty($definition['id']),
      ],
      'entity_type' => [
        '#type' => 'select',
        '#title' => $this->t('Entity type'),
        '#options' => ['_none' => $this->t('- Select -')] + $entity_options,
        '#default_value' => $entity_type,
      ],
      'bundle' => [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => $bundle_options,
        '#default_value' => $definition['bundle'] ?? '_none',
      ],
      'fields' => [
        '#type' => 'checkboxes',
        '#title' => $this->t('Columns'),
        '#options' => $field_options,
        '#default_value' => array_combine($definition['fields'] ?? [], $definition['fields'] ?? []),
      ],
      'page_size' => [
        '#type' => 'number',
        '#title' => $this->t('Rows per page'),
        '#default_value' => $definition['page_size'] ?? 50,
        '#min' => 5,
        '#max' => 500,
      ],
      'options' => [
        '#type' => 'details',
        '#title' => $this->t('Display options'),
        '#open' => FALSE,
        'theme' => [
          '#type' => 'select',
          '#title' => $this->t('AG Grid theme'),
          '#options' => [
            'ag-theme-quartz' => $this->t('Quartz (default)'),
            'ag-theme-alpine' => $this->t('Alpine'),
            'ag-theme-balham' => $this->t('Balham'),
          ],
          '#default_value' => $definition['options']['theme'] ?? 'ag-theme-quartz',
        ],
        'row_height' => [
          '#type' => 'number',
          '#title' => $this->t('Row height'),
          '#default_value' => $definition['options']['row_height'] ?? 44,
          '#min' => 24,
          '#max' => 120,
        ],
        'enable_pagination' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable pagination'),
          '#default_value' => $definition['options']['enable_pagination'] ?? FALSE,
        ],
        'pagination_page_size' => [
          '#type' => 'number',
          '#title' => $this->t('Pagination page size'),
          '#default_value' => $definition['options']['pagination_page_size'] ?? ($definition['page_size'] ?? 50),
          '#min' => 5,
          '#max' => 500,
        ],
      ],
    ];
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable(static::CONFIG_NAME);
    $prepared = [];
    foreach ($values['display_configs'] as $row) {
      if (empty($row['id']) || $row['entity_type'] === '_none' || $row['bundle'] === '_none') {
        continue;
      }
      $fields = array_filter($row['fields'] ?? []);
      if (empty($fields)) {
        continue;
      }
      $prepared[$row['id']] = [
        'id' => $row['id'],
        'label' => $row['label'] ?? $row['id'],
        'entity_type' => $row['entity_type'],
        'bundle' => $row['bundle'],
        'fields' => array_values($fields),
        'page_size' => (int) $row['page_size'],
        'options' => [
          'theme' => $row['options']['theme'] ?? 'ag-theme-quartz',
          'row_height' => isset($row['options']['row_height']) ? (int) $row['options']['row_height'] : 44,
          'enable_pagination' => !empty($row['options']['enable_pagination']),
          'pagination_page_size' => isset($row['options']['pagination_page_size']) ? (int) $row['options']['pagination_page_size'] : (int) $row['page_size'],
        ],
      ];
    }
    $config->set('display_configs', $prepared)->save();
    parent::submitForm($form, $form_state);
  }

}
