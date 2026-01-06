<?php

namespace Drupal\bm_tooltip\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Demo showcase for BlueMarloc Tooltip.
 */
class DemoController extends ControllerBase {

  public function demo() {
    return [
      '#theme' => 'bm_tooltip_demo',
      '#attached' => [
        'library' => [
          'bm_tooltip/tooltip',
        ],
      ],
    ];
  }

}
