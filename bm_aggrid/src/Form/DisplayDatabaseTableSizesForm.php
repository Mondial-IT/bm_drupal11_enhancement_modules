<?php

/*
 Copyright (c) Mondial-IT BV - Blue Marloc 2024
   Created on 2024-11-25 at 2:12:37
 */

namespace Drupal\bm_main\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

class displayDatabaseTableSizesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'database_table_size_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $query = $connection->query('SELECT table_name AS name,
                                        ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
                                 FROM information_schema.tables
                                 WHERE table_schema = :database_name
                                 ORDER BY size_mb DESC', [
      ':database_name' => Database::getConnectionInfo()['default']['database'],
    ]);
    $results = $query->fetchAll();

    $options = [];
    foreach ($results as $row) {
      $options[$row->name] = $this->t('@name (@size MB)', ['@name' => $row->name, '@size' => $row->size_mb]);
    }

    $form['table_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Database Tables'),
      '#options' => $options,
      '#description' => $this->t('Select a table to view its details.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_table = $form_state->getValue('table_select');
    $this->messenger()->addMessage($this->t('You selected the table: @table', ['@table' => $selected_table]));
  }

}
