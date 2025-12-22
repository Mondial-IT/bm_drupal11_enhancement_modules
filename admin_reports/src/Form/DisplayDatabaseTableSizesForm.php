<?php

namespace Drupal\admin_reports\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;

class displayDatabaseTableSizesForm extends FormBase
{

  public function getFormId()
  {
    return 'database_table_size_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $connection = Database::getConnection();
    $query = $connection->query('
      SELECT table_name AS name,
             ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
      FROM information_schema.tables
      WHERE table_schema = :database_name
      ORDER BY size_mb DESC
    ', [
      ':database_name' => Database::getConnectionInfo()['default']['database'],
    ]);
    $results = $query->fetchAll();

    $header = [
      $this->t('Table name'),
      $this->t('Size (MB)'),
    ];

    $rows = [];
    foreach ($results as $row) {
      $url = Url::fromRoute('admin_reports.get_table_fields', [], [
        'query' => ['table' => $row->name],
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
          'data-dialog-options' => json_encode(['width' => 400]),
          'data-ajax-http-method' => 'GET',
        ],
      ]);

      $link = Link::fromTextAndUrl($row->name, $url);

      $rows[] = [
        'data' => [
          $link,
          $row->size_mb,
        ],
      ];
    }

    $form['table_list'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'database-table-list'],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'core/drupal.dialog.off_canvas';

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
  }

}
