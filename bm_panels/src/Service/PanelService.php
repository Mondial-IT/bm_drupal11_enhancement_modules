<?php

declare(strict_types=1);

namespace Drupal\bm_panels\Service;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Stores and retrieves panel metadata and state.
 */
class PanelService {

  /**
   * Constructs the panel service.
   */
  public function __construct(
    protected KeyValueFactoryInterface $keyValueFactory,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Provides default metadata for a panel.
   */
  public function getPanelMetadata(string $panel_id = 'default'): array {
    $label = ucwords(str_replace('_', ' ', $panel_id));
    return [
      'id' => $panel_id,
      'label' => $label,
      'updated' => time(),
      'defaults' => [
        'x' => 0,
        'y' => 0,
        'width' => 4,
        'height' => 3,
      ],
    ];
  }

  /**
   * Returns the saved state for a collection.
   */
  public function getPanelState(string $collection = 'default'): array {
    $state = $this->store()
      ->get($this->buildStorageKey($collection), ['panels' => [], 'removed' => []]);
    $state['panels'] ??= [];
    $state['removed'] ??= [];
    return $state;
  }

  /**
   * Persists panel state for a collection.
   */
  public function savePanelState(string $collection, array $state): void {
    $panels = [];
    foreach ($state['panels'] ?? [] as $panel_id => $values) {
      $panels[$panel_id] = [
        'x' => (int) ($values['x'] ?? 0),
        'y' => (int) ($values['y'] ?? 0),
        'width' => max(1, (int) ($values['width'] ?? 1)),
        'height' => max(1, (int) ($values['height'] ?? 1)),
      ];
    }
    $removed = [];
    foreach ($state['removed'] ?? [] as $panel_id => $is_removed) {
      if ($is_removed) {
        $removed[$panel_id] = TRUE;
      }
    }
    $this->store()->set($this->buildStorageKey($collection), [
      'panels' => $panels,
      'removed' => $removed,
    ]);
  }

  /**
   * Returns the current storage for panel data.
   */
  protected function store(): KeyValueStoreInterface {
    return $this->keyValueFactory->get('bm_panels.state');
  }

  /**
   * Builds a namespaced storage key.
   */
  protected function buildStorageKey(string $collection): string {
    $uid = (int) $this->currentUser->id();
    return $collection . ':' . $uid;
  }

  /**
   * Removes stored state for a collection.
   */
  public function resetPanelState(string $collection): void {
    $this->store()->delete($this->buildStorageKey($collection));
  }

}
