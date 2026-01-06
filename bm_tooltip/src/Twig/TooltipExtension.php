<?php

namespace Drupal\bm_tooltip\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Drupal\bm_tooltip\Service\TooltipService;
use Drupal\Component\Utility\Html;

/**
 * Twig extension providing the tooltip() function.
 */
class TooltipExtension extends AbstractExtension {

  protected TooltipService $service;

  public function __construct(TooltipService $service) {
    $this->service = $service;
  }

  public function getFunctions(): array {
    return [
      new TwigFunction('tooltip', [$this, 'buildTooltip'], ['is_safe' => ['html']]),
    ];
  }

  public function buildTooltip(string $tip, string $label = '', array $options = []): string {
    $tag = $options['tag'] ?? 'span';
    $classes = $this->service->buildClasses($options);
    $tab = array_key_exists('tabindex', $options) ? $options['tabindex'] : 0;
    $attributes = array_merge(
      $this->service->buildDataAttributes($options),
      $options['attributes'] ?? [],
    );

    $attributes['class'] = trim($classes);
    if ($tip !== '') {
      $attributes['data-tip'] = $tip;
      $attributes['data-tippy-content'] = $tip;
    }
    if ($tab !== NULL) {
      $attributes['tabindex'] = (string) $tab;
    }

    if ($tip !== '') {
      $minWidth = $this->service->calculateMinWidth($tip);
      if ($minWidth) {
        $attributes['data-bm-tooltip-min-width'] = $minWidth;
        $style = $attributes['style'] ?? '';
        $style = trim($style);
        $style .= ($style ? ' ' : '') . '--bm-tooltip-min:' . $minWidth . ';';
        $attributes['style'] = $style;
      }
    }

    $attributeString = $this->buildAttributeString($attributes);

    return sprintf(
      '<%1$s %2$s>%3$s</%1$s>',
      Html::escape($tag),
      $attributeString,
      Html::escape($label)
    );
  }

  protected function buildAttributeString(array $attributes): string {
    $pairs = [];
    foreach ($attributes as $name => $value) {
      if ($value === NULL || $value === '') {
        continue;
      }
      $pairs[] = sprintf(
        '%s="%s"',
        Html::escape($name),
        Html::escape((string) $value)
      );
    }
    return implode(' ', $pairs);
  }
}
