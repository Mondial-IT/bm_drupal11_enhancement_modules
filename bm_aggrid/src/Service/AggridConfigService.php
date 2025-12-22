<?php

declare(strict_types=1);

namespace Drupal\bm_aggrid\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class AggridConfigService {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $fieldManager,
  ) {}

  public function getDisplayConfigs(): array {
    return $this->configFactory->get('bm_aggrid.settings')->get('display_configs') ?? [];
  }

  public function getDisplayConfig(string $config_id): ?array {
    $configs = $this->getDisplayConfigs();
    return $configs[$config_id] ?? NULL;
  }

  public function getBundleOptions(string $entity_type): array {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $bundles = method_exists($storage, 'loadMultiple') ? $storage->loadMultiple() : [];
    $options = ['_none' => t('- Select -')];
    foreach ($bundles as $bundle_id => $bundle) {
      $label = method_exists($bundle, 'label') ? $bundle->label() : $bundle_id;
      $options[$bundle_id] = $label;
    }
    return $options;
  }

  public function getFieldOptions(string $entity_type, string $bundle): array {
    $definitions = $this->fieldManager->getFieldDefinitions($entity_type, $bundle);
    $options = [];
    foreach ($definitions as $field_name => $definition) {
      if ($definition->isComputed() || $definition->getFieldStorageDefinition()->isBaseField() === FALSE && $definition->getFieldStorageDefinition()->isDeleted()) {
        continue;
      }
      $options[$field_name] = $definition->getLabel();
    }
    return $options;
  }

}
