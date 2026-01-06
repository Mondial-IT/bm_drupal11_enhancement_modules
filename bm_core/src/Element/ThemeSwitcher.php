<?php
// bm_core/src/Element/ThemeSwitcher.php

namespace Drupal\bm_core\Element;

use Drupal\Core\Render\Element\RenderElementBase;

/**
 * @RenderElement("bm_theme_switcher")
 */
final class ThemeSwitcher extends RenderElementBase {

  public function getInfo(): array {
    return [
      '#theme' => 'bm_theme_switcher',
      '#pre_render' => [
        [static::class, 'preRenderThemeSwitcher'],
      ],
      '#attached' => [
        'library' => [
          'bm_core/theme_switcher',
        ],
      ],
    ];
  }

  public static function preRenderThemeSwitcher(array $element): array {
    $element['#attributes']['data-bm-theme-switcher'] = true;
    $element['#attributes']['class'][] = 'bm-theme-switcher';
    return $element;
  }

}
