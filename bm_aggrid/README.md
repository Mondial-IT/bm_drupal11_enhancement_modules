# BM AG Grid

Provides AG Grid powered displays for Drupal content entities with configurable columns and themes.

## What it does
- Lets admins define grid displays (entity type, bundle, fields, pagination, theme) via `/admin/config/content/aggrid-display`.
- Renders grids at `/aggrid-display/grid/{config_id}` with AG Grid Community (Quartz theme by default).
- Builds column definitions based on field types (text, numeric, boolean, date, references) and outputs row data with access checks.

## Usage
1. Create a display under Configuration → Content → AG Grid displays, choosing entity type, bundle, columns, and options.
2. Visit `/aggrid-display/grid/{id}` to view the grid. Use the selected theme class in the markup for styling.
3. Extend or embed the grid via the provided Twig template `aggrid-display-grid.html.twig`.

## Technical notes
- Library: `bm_aggrid/aggrid.base` (AG Grid CDN + local JS/CSS).
- Services: `bm_aggrid.config` (config helpers), `bm_aggrid.data` (data + column definitions).
- Drupal settings per grid include config, data, and columnDefs consumed by `aggrid-init.js`.

## Basic testing (feature 10)
- Create content with varied field types (text, numeric, boolean, date, references).
- Configure a display via `/admin/config/content/aggrid-display` and select those fields.
- Load `/aggrid-display/grid/{id}` and verify data renders, pagination (if enabled) works, and no JS console errors.
- Check permissions: only users with `view aggrid displays` should access.
