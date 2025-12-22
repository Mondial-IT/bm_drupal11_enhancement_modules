```php
<?php

declare(strict_types=1);

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * This targets the menu overview form:
 * /admin/structure/menu/manage/admin
 */
function mymodule_form_menu_overview_form_alter(array &$form, FormStateInterface $form_state, string $form_id): void {
  // The main table on the menu edit screen is usually under 'links'.
  if (!isset($form['links']) || $form['links']['#type'] !== 'table') {
    return;
  }

  // Route the table element to a custom theme implementation instead
  // of the default table renderer.
  $form['links']['#theme'] = 'mymodule_menu_links_table';

  // Example: add td-level metadata the default theme_table does not support.
  // We introduce a custom key '#td_attributes' that our own theme will honor.
  foreach (Element::children($form['links']) as $row_key) {
    $row =& $form['links'][$row_key];

    // Example: style the "weight" column's <td>.
    if (isset($row['weight']) && is_array($row['weight'])) {
      $row['weight']['#td_attributes']['class'][] = 'my-weight-cell';
      $row['weight']['#td_attributes']['style'] = 'background-color:#ffe;';
    }

    // Example: mark a whole row with a custom <tr> class.
    $row['#attributes']['class'][] = 'my-menu-row';
  }
}

/**
 * Implements hook_theme().
 */
function mymodule_theme($existing, $type, $theme, $path): array {
  return [
    // Custom renderer for the menu links table.
    'mymodule_menu_links_table' => [
      'render element' => 'element',
    ],

    // Optional: ag-Grid replacement theme (see below).
    'mymodule_menu_links_grid' => [
      'render element' => 'element',
    ],
  ];
}

/**
 * Custom renderer for the menu links table.
 *
 * This gives you full control over <table>, <tr>, and <td>, including
 * support for a custom '#td_attributes' key defined in form_alter().
 */
function theme_mymodule_menu_links_table(array $variables): string {
  $element = $variables['element'];

  $header = $element['#header'] ?? [];
  $table_attributes = new Attribute($element['#attributes'] ?? []);

  $output = [];

  // Start table.
  $output[] = '<table' . $table_attributes . '>';

  // Render header if present.
  if (!empty($header)) {
    $output[] = '<thead><tr>';
    foreach ($header as $cell) {
      // Header cells can be plain strings or arrays.
      if (is_array($cell)) {
        $label = $cell['data'] ?? '';
        $cell_attributes = new Attribute($cell['attributes'] ?? []);
        $output[] = '<th' . $cell_attributes . '>' . $label . '</th>';
      }
      else {
        $output[] = '<th>' . $cell . '</th>';
      }
    }
    $output[] = '</tr></thead>';
  }

  // Render body.
  $output[] = '<tbody>';

  foreach (Element::children($element) as $row_key) {
    $row_element = $element[$row_key];

    // <tr> attributes.
    $tr_attributes = new Attribute($row_element['#attributes'] ?? []);
    $output[] = '<tr' . $tr_attributes . '>';

    // Each child element in the row becomes a <td>.
    foreach (Element::children($row_element) as $col_key) {
      $cell_element = $row_element[$col_key];

      // Our custom td attributes, set in form_alter().
      $td_attributes = new Attribute($cell_element['#td_attributes'] ?? []);

      // Render the actual form element (this may produce the div+input structure).
      $cell_render = \Drupal::service('renderer')->render($cell_element);

      $output[] = '<td' . $td_attributes . '>' . $cell_render . '</td>';
    }

    $output[] = '</tr>';
  }

  $output[] = '</tbody></table>';

  return implode('', $output);
}

/**
 * OPTIONAL: Replace the table with an ag-Grid container instead of <table>.
 *
 * In form_alter(), point the element to this theme:
 *
 *   $form['links']['#theme'] = 'mymodule_menu_links_grid';
 *   $form['#attached']['library'][] = 'mymodule/menu_grid';
 */
function theme_mymodule_menu_links_grid(array $variables): string {
  $element = $variables['element'];

  // Collect data for JS (ag-Grid) from the form elements.
  // You can decide what to expose; here is a simple example.
  $rows = [];
  foreach (Element::children($element) as $row_key) {
    $row_element = $element[$row_key];

    $row_data = [
      'id' => $row_key,
      'title' => '',
      'enabled' => '',
      'weight' => '',
    ];

    if (isset($row_element['title'])) {
      $row_data['title'] = \Drupal::service('renderer')->renderPlain($row_element['title']);
    }
    if (isset($row_element['enabled'])) {
      $row_data['enabled'] = \Drupal::service('renderer')->renderPlain($row_element['enabled']);
    }
    if (isset($row_element['weight'])) {
      $row_data['weight'] = \Drupal::service('renderer')->renderPlain($row_element['weight']);
    }

    $rows[] = $row_data;
  }

  // Expose data + config to JS via drupalSettings.
  $element['#attached']['drupalSettings']['mymodule']['menuGrid']['rows'] = $rows;

  // Ensure the library is attached (JS will bootstrap ag-Grid on this div).
  $element['#attached']['library'][] = 'mymodule/menu_grid';

  // Render only a container; ag-Grid takes over inside JS.
  $attributes = new Attribute([
    'id' => 'mymodule-menu-grid',
    'class' => ['mymodule-menu-grid-wrapper'],
    'data-drupal-selector' => 'mymodule-menu-grid',
  ]);

  // Return a simple wrapper. Form elements are still part of the form,
  // but you are free to render them inside the grid with JS.
  return '<div' . $attributes . '></div>';
}
```
