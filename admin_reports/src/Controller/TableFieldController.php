<?php

namespace Drupal\admin_reports\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class TableFieldController extends ControllerBase
{

  public function ajaxGetFields()
  {
    $request = \Drupal::request();
    $table = $request->query->get('table');

    $items = [];

    if (!empty($table)) {
      $connection = Database::getConnection();
      $query = $connection->query('
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = :table
          AND TABLE_SCHEMA = :schema
      ', [
        ':table' => $table,
        ':schema' => Database::getConnectionInfo()['default']['database'],
      ]);

      $fields = $query->fetchAll();

      foreach ($fields as $field) {
        $items[] = $field->COLUMN_NAME;
      }
    }

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Fields of @table', ['@table' => $table]),
      '#items' => $items,
    ];
  }

}
