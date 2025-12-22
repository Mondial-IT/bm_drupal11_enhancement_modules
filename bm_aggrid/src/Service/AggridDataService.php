<?php

declare(strict_types=1);

namespace Drupal\bm_aggrid\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

class AggridDataService {
  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $fieldManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  public function getEntityData(string $entity_type, string $bundle, array $fields, int $limit = 50, int $offset = 0, array $context = []): array {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->range($offset, $limit);

    $bundle_key = $storage->getEntityType()->getKey('bundle');
    if ($bundle_key && $bundle) {
      $query->condition($bundle_key, $bundle);
    }

    $ids = $query->execute();
    if (!$ids) {
      return [];
    }
    $entities = $storage->loadMultiple($ids);
    $rows = [];
    foreach ($entities as $entity) {
      $row = ['id' => $entity->id()];
      foreach ($fields as $field_name) {
        if (!$entity->hasField($field_name)) {
          continue;
        }
        $field = $entity->get($field_name);
        if (!$field->access('view', $this->currentUser)) {
          continue;
        }
        $row[$field_name] = $this->formatFieldValue($field);
      }
      $rows[] = $row;
    }
    return $rows;
  }

  public function saveCellValue(array $payload): array {
    return [
      'status' => 'error',
      'message' => $this->t('Editing is not yet implemented.'),
    ];
  }

  public function getColumnDefinitions(string $entity_type, string $bundle, array $fields): array {
    $definitions = $this->fieldManager->getFieldDefinitions($entity_type, $bundle);
    $columns = [];
    foreach ($fields as $field_name) {
      if (!isset($definitions[$field_name])) {
        continue;
      }
      $columns[] = $this->mapFieldToColumnDef($definitions[$field_name]);
    }
    return $columns;
  }

  public function mapFieldToColumnDef(FieldDefinitionInterface $definition): array {
    $type = $definition->getType();
    $column = [
      'field' => $definition->getName(),
      'headerName' => $definition->getLabel(),
      'sortable' => TRUE,
      'filter' => 'agTextColumnFilter',
      'resizable' => TRUE,
    ];

    switch ($type) {
      case 'integer':
      case 'float':
      case 'decimal':
        $column['filter'] = 'agNumberColumnFilter';
        $column['type'] = ['numericColumn'];
        break;

      case 'boolean':
        $column['filter'] = 'agSetColumnFilter';
        $column['cellRenderer'] = 'agCheckboxCellRenderer';
        break;

      case 'timestamp':
      case 'datetime':
      case 'created':
      case 'changed':
        $column['filter'] = 'agDateColumnFilter';
        $column['valueFormatter'] = 'date';
        break;

      case 'entity_reference':
        $column['filter'] = 'agTextColumnFilter';
        break;

      default:
        break;
    }

    return $column;
  }

  protected function formatFieldValue(FieldItemListInterface $field): mixed {
    $definition = $field->getFieldDefinition();
    $type = $definition->getType();
    $values = $field->getValue();

    if ($type === 'boolean') {
      return !empty($values[0]['value'] ?? FALSE);
    }

    if ($type === 'timestamp' || $type === 'datetime') {
      $value = $values[0]['value'] ?? NULL;
      if ($value) {
        return (int) $value;
      }
      return NULL;
    }

    if ($type === 'entity_reference') {
      $referenced = $field->referencedEntities();
      if ($referenced) {
        $labels = array_map(fn($entity) => $entity->label(), $referenced);
        return implode(', ', $labels);
      }
      return $values[0]['target_id'] ?? NULL;
    }

    if (count($values) === 1 && isset($values[0]['value'])) {
      return $values[0]['value'];
    }
    return $values;
  }

}
