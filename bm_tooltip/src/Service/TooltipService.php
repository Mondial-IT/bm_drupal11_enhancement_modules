<?php

namespace Drupal\bm_tooltip\Service;

/**
 * Provides helper functions for tooltip generation and configuration.
 */
class TooltipService {

  /**
   * Generates tooltip classes based on settings.
   */
  public function buildClasses(array $options = []): string {
    $theme = $this->getTheme($options);
    $position = $this->getPosition($options);
    $edge = $options['edge'] ?? TRUE;

    $classes = [
      'tooltip',
      "tooltip--{$position}",
      "tooltip--{$theme}",
    ];

    if ($edge) {
      $classes[] = 'tooltip--edge-aware';
    }

    if (!empty($options['parent'])) {
      $classes[] = 'tooltip--is-parent';
    }

    if (!empty($options['class'])) {
      $extra = is_array($options['class']) ? $options['class'] : preg_split('/\s+/', (string) $options['class']);
      $classes = array_merge($classes, array_filter($extra));
    }

    if (!empty($options['classes']) && is_array($options['classes'])) {
      $classes = array_merge($classes, array_filter($options['classes']));
    }

    return implode(' ', $classes);
  }

  /**
   * Builds data attributes consumed by the JavaScript behavior.
   */
  public function buildDataAttributes(array $options = []): array {
    $attributes = [
      'data-bm-tooltip-theme' => $this->getTheme($options),
      'data-bm-tooltip-placement' => $this->getPosition($options),
    ];

    if (isset($options['interactive'])) {
      $attributes['data-bm-tooltip-interactive'] = $options['interactive'] ? 'true' : 'false';
    }
    if (!empty($options['trigger'])) {
      $attributes['data-bm-tooltip-trigger'] = (string) $options['trigger'];
    }
    if (!empty($options['delay'])) {
      $delay = $options['delay'];
      $attributes['data-bm-tooltip-delay'] = is_array($delay) ? implode(',', $delay) : (string) $delay;
    }
    if (!empty($options['distance'])) {
      $attributes['data-bm-tooltip-distance'] = (string) (int) $options['distance'];
    }
    if (!empty($options['interactive_border'])) {
      $attributes['data-bm-tooltip-interactive-border'] = (string) (int) $options['interactive_border'];
    }
    if (!empty($options['placement_fallbacks'])) {
      $fallbacks = $options['placement_fallbacks'];
      $attributes['data-bm-tooltip-placement-fallbacks'] = is_array($fallbacks) ? implode(',', $fallbacks) : (string) $fallbacks;
    }
    if (!empty($options['max_width'])) {
      $attributes['data-bm-tooltip-max-width'] = (string) (int) $options['max_width'];
    }

    return array_filter($attributes, static fn($value) => $value !== NULL && $value !== '');
  }

  /**
   * Calculates a CSS length for tooltip min-width based on word count.
   */
  public function calculateMinWidth(string $tip): ?string {
    $tip = trim($tip);
    if ($tip === '') {
      return NULL;
    }

    $words = preg_split('/\s+/u', $tip, -1, PREG_SPLIT_NO_EMPTY);
    if (empty($words)) {
      return NULL;
    }

    $sample = count($words) >= 7 ? array_slice($words, 0, 7) : $words;
    $sampleText = implode(' ', $sample);
    $chars = mb_strlen($sampleText);
    // Add a little breathing room.
    $chars += 2;

    return max($chars, 10) . 'ch';
  }

  /**
   * Ensures theme is a valid string.
   */
  protected function getTheme(array $options = []): string {
    $theme = $options['theme'] ?? 'dark';
    return preg_replace('/[^a-z0-9_-]/i', '', $theme) ?: 'dark';
  }

  /**
   * Ensures placement is a valid keyword understood by Tippy.js.
   */
  protected function getPosition(array $options = []): string {
    $position = strtolower((string) ($options['position'] ?? 'top'));
    $allowed = ['top', 'bottom', 'left', 'right'];
    return in_array($position, $allowed, TRUE) ? $position : 'top';
  }

}
