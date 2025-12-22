<?php

declare(strict_types=1);

namespace Drupal\bm_aggrid\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * @ViewsStyle(
 *   id = "bm_aggrid",
 *   title = @Translation("AG Grid display"),
 *   help = @Translation("Render result rows through AG Grid."),
 *   theme = "bm_aggrid_grid",
 *   display_types = {"normal"}
 * )
 */
class AggridStyle extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['grid_id'] = ['default' => 'views_aggrid'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#grid_id'] = $this->options['grid_id'];
    $build['#attached']['library'][] = 'bm_aggrid/aggrid.base';
    return $build;
  }

}
