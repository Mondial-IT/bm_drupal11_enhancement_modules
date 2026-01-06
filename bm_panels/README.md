# BM Panels

BM Panels is a scaffold module that introduces a custom `bm_panels` Form API element, lightweight panel/container theme hooks, an AJAX placeholder controller, and an accompanying service layer. The module is intentionally minimal; it gives site builders and developers a clean starting point for future panel tooling without enforcing any specific drag/resize behavior yet.

## Features

- `bm_panels` Form API element converts arbitrary child elements into draggable, resizable panels with snapping grid.
- ES6 `PanelGrid` class (no jQuery) handles drag/resize interactions with collision detection, add/remove controls, and AJAX persistence.
- AJAX controller + service store state per-user (`PanelService::savePanelState()`, `resetPanelState()` and `getPanelState()`), returning JSON payloads.
- Customizable metadata defaults (`getPanelMetadata()`) inform initial panel sizes and labels.
- Palette controls let users spawn new cards via “Add panel” buttons; removed panels appear as chips and can be reinserted instantly.
- Example forms (`PanelsBasicExampleForm`, `PanelsAjaxExampleForm`) expose configuration toggles (draggable, removable, width/height, palette items) plus reset buttons.
- Kernel test verifies theme hook registration and service availability.

## Installation

```bash
ddev drush en bm_panels -y
```

This enables the module and registers the new form element, library, and AJAX route.

## Usage Example

Embed the element inside a custom form:

```php
$form['panels'] = [
  '#type' => 'bm_panels',
  'panel_one' => [
    '#title' => $this->t('Panel #1'),
    '#description' => $this->t('Short tooltip text.'),
    '#markup' => '<p>Panel #1 content</p>',
  ],
  'panel_two' => [
    '#title' => $this->t('Panel #2'),
    '#panel_description' => $this->t('Allows <strong>HTML</strong> inside the tooltip.'),
    '#markup' => '<p>Panel #2 content</p>',
  ],
];
```

Each child becomes a themed panel rendered via `bm-panel.html.twig`. The wrapper automatically attaches the `bm_panels.core` library and exposes `data-panel-id` attributes for JavaScript.

## Panel Configuration

Each child element can include a `#bm_panels` array:

```php
$form['panels']['hero']['#bm_panels'] = [
  'draggable' => TRUE,
  'removable' => FALSE,
  'width' => 6,
  'height' => 4, // 1..12 columns
  'x' => 0,      // Column start (0-indexed)
  'y' => 0,      // Row start (0-indexed)
];
```

Optional palettes allow runtime additions:

```php
$form['panels']['#palette'][] = [
  'id' => 'alerts',
  'label' => $this->t('Alerts panel'),
  'markup' => '<p>...</p>',
  'width' => 4,
  'height' => 3,
  'draggable' => TRUE,
  'removable' => TRUE,
];
```

Removed cards surface above the grid so authors can reinsert them without page reloads.

### Titles and help text

- Use standard Form API keys `#title` and `#description` on each child element to populate the draggable handle.
- When you need markup inside the tooltip, provide `#panel_description` (string or `MarkupInterface`) and it will be rendered inside the Tippy popover container.
- Palette entries accept `title`, `description`, and `panel_description` keys so dynamically created panels get the same header UX without additional wiring.

### Tooltip library

Tooltips are powered by the companion `bm_tooltip` module, which provides a CSS-only tooltip system plus a Twig helper for rendering trigger markup. Panels automatically attach the `bm_tooltip/tooltip` library so any element with the `.tooltip` class and `data-tip` attribute gains hover/focus tooltips without additional JavaScript.

## AJAX Endpoints

- Load state: `GET /bm-panels/state/{collection}`
- Save state: `POST /bm-panels/state/{collection}`

Both routes require the `access bm panels` permission. POST requests include an `X-CSRF-Token` header exposed through `drupalSettings.bmPanels[instance].csrfToken`.

## Testing

Run the kernel test to confirm registrations:

```bash
ddev exec phpunit -c web/core/phpunit.xml.dist web/modules/custom/bm_panels/src/Tests/Kernel/BmPanelsKernelTest.php
```

## Next Steps

- Replace the placeholder CSS/JS with production-ready panel interactions.
- Expand `PanelService` to store metadata (layout regions, responsive breakpoints, etc.).
- Add configuration entities and UI screens once requirements solidify.
- Grid placement is powered by CSS Grid; set `x`/`y` to define the starting column/row (0-indexed) so default layouts never overlap. Width/height still represent how many columns/rows the panel spans.
