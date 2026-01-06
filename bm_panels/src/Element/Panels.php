<?php

declare(strict_types=1);

namespace Drupal\bm_panels\Element;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Attribute\FormElement as FormElementAttribute;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Provides the bm_panels Form API element.
 */
#[FormElementAttribute('bm_panels')]
class Panels extends FormElementBase {

  /**
   * Returns the element definition for hook_element_info().
   */
  public static function definition(): array {
    return [
      '#tree' => TRUE,
      '#theme' => 'bm_panel_container',
      '#collection' => 'default',
      '#instance_id' => NULL,
      '#grid_size' => 80,
      '#columns' => 12,
      '#palette' => [],
      '#show_panel_meta' => FALSE,
      '#render_toolbar' => TRUE,
      '#render_wrapper' => TRUE,
      '#attached' => [
        'library' => ['bm_panels/bm_panels.core'],
      ],
      '#attributes' => [],
      '#pre_render' => [
        [static::class, 'preRenderPanels'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return static::definition();
  }

  /**
   * Builds the render array before it is themed.
   */
  public static function preRenderPanels(array $element): array {
    $collection = $element['#collection'] ?? 'default';
    $gridSize = (int) ($element['#grid_size'] ?? 80);
    $instanceId = $element['#instance_id'] ?? Html::getUniqueId('bm-panels-' . $collection);
    $panelService = \Drupal::service('bm_panels.service');
    $state = $panelService->getPanelState($collection);
    $storedPanels = $state['panels'] ?? [];
    $removedPanelsState = $state['removed'] ?? [];

    $panels = [];
    $panelSettings = [];
    $panelConfigs = [];
    $removedPanels = [];
    $hasActiveRemovablePanels = FALSE;
    foreach (Element::children($element) as $child) {
      $metadata = $panelService->getPanelMetadata($child);
      $defaults = $metadata['defaults'] ?? ['x' => 0, 'y' => 0, 'width' => 4, 'height' => 3];
      $bmConfig = $element[$child]['#bm_panels'] ?? [];
      $positionDefaults = [
        'x' => max(0, (int) ($bmConfig['x'] ?? $defaults['x'] ?? 0)),
        'y' => max(0, (int) ($bmConfig['y'] ?? $defaults['y'] ?? 0)),
        'width' => max(1, min(12, (int) ($bmConfig['width'] ?? $defaults['width'] ?? 4))),
        'height' => max(1, min(12, (int) ($bmConfig['height'] ?? $defaults['height'] ?? 3))),
      ];
      $position = $storedPanels[$child] ?? $positionDefaults;
      $position['x'] = max(0, (int) ($position['x'] ?? $positionDefaults['x']));
      $position['y'] = max(0, (int) ($position['y'] ?? $positionDefaults['y']));
      $position['width'] = max(1, min(12, (int) ($position['width'] ?? $positionDefaults['width'])));
      $position['height'] = max(1, min(12, (int) ($position['height'] ?? $positionDefaults['height'])));
      $panelTitle = $element[$child]['#panel_title'] ?? $element[$child]['#title'] ?? $metadata['label'] ?? $child;
      $panelDescription = $element[$child]['#panel_description'] ?? $element[$child]['#description'] ?? '';
      $panelDescriptionString = $panelDescription === NULL ? '' : (string) $panelDescription;
      $panelDescriptionRenderable = $panelDescription instanceof MarkupInterface ? $panelDescription : Markup::create($panelDescriptionString);
      $isRemoved = !empty($removedPanelsState[$child]);
      $config = [
        'draggable' => $bmConfig['draggable'] ?? TRUE,
        'removable' => $bmConfig['removable'] ?? TRUE,
        'width' => $position['width'],
        'height' => $position['height'],
        'title' => $panelTitle,
        'panelDescription' => $panelDescriptionString,
        'label' => $metadata['label'] ?? $child,
        'removed' => $isRemoved,
      ];

      if (!$isRemoved && $config['removable']) {
        $hasActiveRemovablePanels = TRUE;
      }

      if ($isRemoved) {
        $removedPanels[$child] = [
          'id' => $child,
          'label' => $metadata['label'] ?? $child,
          'title' => $panelTitle,
        ];
      }

      $panels[] = [
        '#theme' => 'bm_panel',
        '#panel_id' => $child,
        '#content' => $element[$child],
        '#position' => $position,
        '#label' => $metadata['label'] ?? $child,
        '#panel_config' => $config,
        '#panel_title' => $panelTitle,
        '#panel_description' => $panelDescriptionRenderable,
        '#show_meta' => !empty($element['#show_panel_meta']),
      ];
      $panelSettings[$child] = [
        'x' => $position['x'],
        'y' => $position['y'],
        'width' => $position['width'],
        'height' => $position['height'],
        'label' => $metadata['label'] ?? $child,
        'title' => $panelTitle,
      'panelDescription' => $panelDescriptionString,
      ];
      $panelConfigs[$child] = $config;
    }

    $element['#panels'] = $panels;
    $element['#attributes']['class'][] = 'bm-panel-container';
    $element['#attributes']['data-bm-panels-instance'] = $instanceId;
    $element['#attributes']['data-grid-size'] = (string) $gridSize;
    $gridId = $element['#grid_id'] ?? ('bm-panels-grid-' . $instanceId);
    $element['#grid_id'] = $gridId;
    $element['#attributes']['data-bm-panels-grid'] = $gridId;
    if (empty($element['#attributes']['id'])) {
      $element['#attributes']['id'] = $gridId;
    }
    $element['#attached']['library'][] = 'bm_tooltip/tooltip';
    $element['#removed_panels'] = array_values($removedPanels);
    $element['#toolbar_visible'] = !empty($element['#render_toolbar']) && ($hasActiveRemovablePanels || !empty($removedPanels));
    $element['#toolbar_id'] = $element['#toolbar_id'] ?? ('bm-panels-toolbar-' . $instanceId);
    $element['#toolbar'] = [
      '#theme' => 'bm_panel_toolbar',
      '#palette' => $element['#palette'],
      '#instance_id' => $instanceId,
      '#removed_panels' => $element['#removed_panels'],
      '#toolbar_id' => $element['#toolbar_id'],
      '#visible' => $element['#toolbar_visible'],
    ];

    $paletteSettings = [];
    $paletteItems = $element['#palette'] ?? [];
    if (!is_iterable($paletteItems)) {
      $paletteItems = [];
    }
    foreach ($paletteItems as $item) {
      if (empty($item['id'])) {
        continue;
      }
      $paletteSettings[$item['id']] = [
        'id' => $item['id'],
        'label' => $item['label'] ?? $item['id'],
        'markup' => $item['markup'] ?? '',
        'draggable' => $item['draggable'] ?? TRUE,
        'removable' => $item['removable'] ?? TRUE,
        'width' => max(1, min(12, (int) ($item['width'] ?? 4))),
        'height' => max(1, min(12, (int) ($item['height'] ?? 3))),
        'title' => $item['title'] ?? $item['label'] ?? $item['id'],
        'panelDescription' => (string) ($item['panel_description'] ?? $item['description'] ?? ''),
      ];
    }

    $csrfToken = \Drupal::service('csrf_token')->get('bm_panels.state.' . $collection);
    $element['#attached']['drupalSettings']['bmPanels'][$instanceId] = [
      'collection' => $collection,
      'grid' => $gridSize,
      'columns' => (int) ($element['#columns'] ?? 12),
      'loadUrl' => Url::fromRoute('bm_panels.state_load', ['collection' => $collection])->toString(),
      'saveUrl' => Url::fromRoute('bm_panels.state_save', ['collection' => $collection])->toString(),
      'panels' => $panelSettings,
      'panelsConfig' => $panelConfigs,
      'palette' => $paletteSettings,
      'removed' => array_filter($removedPanelsState ?? [], static fn($value) => (bool) $value),
      'csrfToken' => $csrfToken,
    ];

    $element['#palette'] = array_values($paletteSettings);

    return $element;
  }

}
