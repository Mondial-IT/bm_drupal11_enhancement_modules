# bm_tooltip – Tippy.js Tooltips for Drupal

`bm_tooltip` provides a shared Drupal library, Twig helper, and CSS tokens that wrap the [Tippy.js](https://atomiks.github.io/tippyjs/) tooltip engine. It ships a single behavior that targets any element rendered with the `bm_tooltip()` Twig function (or anything using the `.tooltip` class and a `data-tip` / `data-bm-tooltip-content` attribute). The behavior now also supports inline HTML when `data-bm-tooltip-content` is present but empty, using the element’s inner HTML as the tooltip.

## Why Tippy.js?

The initial version of this module emulated tooltips with CSS alone. That approach was lightweight but limited: no viewport-aware positioning, no touch support, and poor collision handling. By adopting the official Tippy.js bundle we gain:

- Popper.js-powered positioning with smart collision detection and fallback placements.
- Smooth animations, touch + keyboard support, focus trapping, and richer triggers.
- Consistent styling across browsers by skinning the `.tippy-box` container with BlueMarloc tokens.

The library is still delivered through the Drupal asset pipeline. No extra setup beyond attaching `bm_tooltip/tooltip` is required.

## Usage

```twig
{{
bm_tooltip(
  'Explain the metric shown in this tile.',
  'KPI label',
  {
    theme: 'brand',
    position: 'right',
    interactive: false,
  }
)
}}
```

Or bind HTML from a separate element:

```html
<span class="bm-tooltip" data-tooltip-selector="#tip-html">Hover me</span>
<div id="tip-html" data-tooltip>
  <strong>Rich content</strong><br />With line breaks.
</div>
```

Every render array or controller that emits tooltips must attach the shared library once:

```php
$build['#attached']['library'][] = 'bm_tooltip/tooltip';
```

The Twig helper outputs:

- The `.tooltip` utility class plus matching modifiers (e.g. `tooltip--right`).
  - `data-bm-tooltip-content` with the translated text, `data-tip`, or `data-bm-tooltip-content` with empty value and inline HTML inside the element. You can also point to external markup with `data-tooltip-selector` / `data-tooltip`.
- Optional attributes such as `data-bm-tooltip-theme`, `data-bm-tooltip-min-width`, `data-bm-tooltip-trigger`, etc.

The Drupal behavior reads those attributes and initializes `tippy()` with sane defaults (`appendTo: body`, `animation: shift-away-subtle`, `offset: [0, 8]`, etc.).

## Theming

`css/components/bm_tooltip.css` overrides the base `.tippy-box` styles using the BlueMarloc palette. Available themes:

- `dark` (default)
- `light`
- `accent`
- `brand`

You can introduce additional themes by targeting `.tippy-box[data-theme~='your-theme']`.

All tooltips respect the computed `--bm-tooltip-min` CSS variable or the generated `data-bm-tooltip-min-width` attribute, so long strings stay readable. Width is capped to the viewport with `max-width: min(22rem, calc(100vw - 1rem))`.

## Advanced Options

The Twig helper forwards several options to the behavior:

| Option                    | Effect                                                      |
|---------------------------|-------------------------------------------------------------|
| `position`                | `top`, `bottom`, `left`, or `right`                         |
| `theme`                   | Matches the CSS theme token                                 |
| `interactive`             | Enables pointer events on the tooltip                       |
| `trigger`                 | Custom trigger string (`mouseenter click`, etc.)            |
| `delay`                   | Single value or `[show, hide]` array                        |
| `distance`                | Gap between trigger and tooltip (px)                        |
| `interactive_border`      | Distance before an interactive tooltip hides                |
| `placement_fallbacks`     | CSV or array of Popper placements to try when colliding     |
| `max_width`               | Explicit max width override                                 |

These map directly to `data-bm-tooltip-*` attributes which the JavaScript layer reads before calling `tippy()`.

## Accessibility

Tippy.js manages focus handling, keyboard dismissal, and ARIA attributes out-of-the-box. The helper still sets `tabindex="0"` by default so triggers remain focusable even when rendered as spans.

## Browser Requirements

- Drupal 11.x
- Modern evergreen browsers (Tippy.js v6 requires ES2015+)

No build tooling is needed: Tippy and Popper are loaded from jsDelivr via the Drupal library definition.
