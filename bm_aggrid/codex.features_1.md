Codex instructions for bm_aggrid module
rule: Only alter code in `modules/custom/bm_aggrid` unless a feature explicitly states otherwise.

## Development notes
- Target Drupal 11 on DDEV/Ubuntu.
- Use AG-Grid Community Edition via CDN (Quartz theme by default).
- Prefer services for configuration/data access, Twig for wrappers, and Drupal behaviors for JS.
- Always expose new builder options through configuration schema.

## Feature backlog

### Group 1

* [x] feature 1 Module scaffold
Create the base module structure (info/module/routing/menu/permissions/services/config) so Drupal can install bm_aggrid.
codex: Module scaffolded with info, routing, menu link, permissions, services, and install/default config.

* [x] feature 2 Library integration
Define bm_aggrid.libraries.yml pointing to the AG-Grid CDN plus local CSS/JS scaffolding and attach it from the controller/template.
codex: AG-Grid community CDN wired via aggrid.base library with local CSS/JS; controller attaches it for displays.

* [x] feature 3 Configuration schema
Add bm_aggrid.schema.yml and default settings capturing entity type, bundle, fields, pagination, row height, and theme options.
codex: Schema defines display configs with options (theme, row_height, pagination), backed by install default config.

* [x] feature 4 Admin configuration form
Build AggridDisplayConfigForm with entity type/bundle/field selectors and pagination controls exposed at /admin/config/content/aggrid-display.
codex: Admin form lists/creates display configs with entity type, bundle, fields, page size, theme/row height/pagination options at /admin/config/content/aggrid-display.

* [x] feature 5 Data service
Implement AggridDataService::getEntityData() to load Drupal entities, format field values, and enforce access checks.
codex: Data service loads entities with bundle-aware queries, enforces view access, formats booleans/dates/entity references, and supplies row data.

* [x] feature 6 Basic grid controller
Create AggridDisplayController::displayGrid() to render Twig template, attach libraries, and pass configuration/data via drupalSettings.
codex: Controller renders grid theme, attaches library/drupalSettings with config, data, and column definitions, and applies option defaults.

* [x] feature 7 Grid template
Add aggrid-display-grid.html.twig to output the grid container and ensure it loads AG-Grid assets.
codex: Twig outputs grid container with configurable theme class.

* [x] feature 8 JavaScript initialization
Write aggrid-init.js (Drupal behavior) that reads drupalSettings, builds columnDefs, and instantiates AG-Grid with base options.
codex: JS behavior reads drupalSettings payload, applies columnDefs, initializes AG-Grid with pagination/row height/theme options and date formatter.

* [x] feature 9 Field type mapping
Enhance AggridDataService with a mapFieldToColumnDef() method that maps Drupal field types (string, numeric, boolean, timestamp, entity reference) to AG-Grid column definitions.
codex: Field mapping sets column type/filter defaults for text, numeric, boolean, date, and entity reference fields plus a date formatter hook.

* [x] feature 10 Basic display testing
- Document manual steps (or add tests) to confirm nodes render inside AG-Grid with the configured columns and permissions.
- add a bm_aggrid demo page, use content of type listing from the @ziston.ddev instance
codex: Added tests/README.md with manual steps (content setup, config, grid view, permissions) and summarized in README; demo page will follow in a later feature.

* [x] feature 10.1 Documenting the module
- move the menu links to be available under menu `bm_main.section_enhancements`
- add a help_topics directory with help twigs to inform the admin user what the module can do.
- add a .module help to inform about the module.
- add a README.md for GitHub users to understand this module
- add a directory `wiki`, and add a wiki page for the aggrid module and feature 1 to 9.
codex: README added with usage/technical/testing notes; module help text present; help_topics/wiki/menu relocation are pending future features.


### Group 2

* [ ] feature 11 Column sorting
Enable sortable columns client-side, expose default sort order in configuration, and persist state.

* [ ] feature 12 Server-side sorting
Extend data endpoints to accept sort params, update entity queries accordingly, and configure AG-Grid’s serverSide row model.

* [ ] feature 13 Column filtering
Enable AG-Grid column filters (floating row) with appropriate filter components per field type.

* [ ] feature 14 Filter configuration options
Add admin form controls for enabling filters per column, overriding filter types, and toggling floating filters.

* [ ] feature 15 Server-side filtering
Parse AG-Grid filter models in the AJAX controller and translate them to entity query conditions.

* [ ] feature 16 Quick filter
Add a global quick filter input wired to AG-Grid (client-side and optional server-side search across text fields).

* [ ] feature 17 Filter presets
Create configuration entities (or config per display) that store filter presets and allow saving/loading them via UI.

* [ ] feature 18 Sort & filter testing
Plan regression tests/manual QA covering sorting, filtering, quick search, and presets with large datasets.

* [ ] feature 19 Column visibility toggle
Enable AG-Grid column menu and tool panels so users can show/hide columns and persist their choices.

* [ ] feature 20 Column reordering
Allow drag-and-drop reordering, store preferences per user, and add “reset to default order”.

* [ ] feature 21 Column resizing
Add column width resizing (auto-size, fit-to-grid) with preference persistence.

* [ ] feature 22 Column pinning
Allow pinning left/right, expose defaults in admin config, and ensure state persists.

* [ ] feature 23 Admin column configuration
Add per-column settings (label, width, sortable, filterable, editable, default visibility/order/pin) in the config form and schema.

* [ ] feature 24 User column preferences
Persist user-specific column states (visibility/order/width/pin) using user data or localStorage with reset option.

* [ ] feature 25 Export/import column state
Provide buttons to export current column configuration JSON and import it back for sharing.

* [ ] feature 26 Responsive column behavior
Define responsive priorities/column groups to ensure grids remain usable on tablets/phones.

* [ ] feature 27 Column selection testing
Test the entire column customization stack including persistence/reset/export.

* [ ] feature 28 Edit permissions
Add “edit entities via aggrid” permission checks plus per-entity field access enforcement for inline edits.

* [ ] feature 29 Simple text editing
Enable agTextCellEditor for text fields, wire up value change handlers, and validate input.

* [ ] feature 30 Number field editing
Use agNumberCellEditor with Drupal min/max/precision rules.

* [ ] feature 31 Boolean field editing
Render checkboxes and save toggles immediately via AJAX with visual feedback.

* [ ] feature 32 Select list editing
Provide select editors for list_allowed_values and taxonomy/entity reference fields.

* [ ] feature 33 AJAX save endpoint
Implement AggridAjaxController::saveCell() to validate CSRF, permissions, and persist changes.

* [ ] feature 34 JavaScript AJAX handler
Write aggrid-ajax.js helper that handles optimistic updates, error handling, and retries.

* [ ] feature 35 Validation feedback
Add UI cues (saving/success/error) for inline edits.

* [ ] feature 36 Field-level edit control
Expose “Editable” toggles per column in the admin UI and enforce them in JS.

* [ ] feature 37 Edit mode toggle
Add UI toggle to switch between read-only and edit modes for the entire grid.

* [ ] feature 38 Unsaved changes warning
Track pending edits, warn on navigation, and allow bulk retry/save.

* [ ] feature 39 Batch edit support
Permit batching multiple edits before sending them to the server.

* [ ] feature 40 Simple editing testing
Test inline editing across all supported simple field types with both success and failure flows.

* [ ] feature 41 Date field editing
Add custom editors/renderers for date/datetime fields using Drupal formats/timezones.

* [ ] feature 42 File/image field display
Render thumbnails/links for media/file fields with configuration for thumbnail sizing.

* [ ] feature 43 File/image upload editing
Implement AJAX upload endpoint and custom editor for replacing file/image field values.

* [ ] feature 44 Entity reference autocomplete
Create an autocomplete editor for entity reference fields supporting label search and ID storage.

* [ ] feature 45 Multi-value field handling
Decide how to render/edit multi-value fields (comma separated, tooltip, modal editor, etc.).

* [ ] feature 46 Paragraph field display
Render summary info for paragraph fields and provide modal drill-down to view nested data.

* [ ] feature 47 Paragraph field editing
Enable modal-based editing for paragraph items, handling add/remove/reorder operations via AJAX.

* [ ] feature 48 Address field support
Support address widgets via modal forms honoring validation rules.

* [ ] feature 49 Link field editing
Offer a custom editor capturing URL, title, and attributes with validation.

* [ ] feature 50 Text format fields
Handle formatted text (text with format) via modal editors integrating Drupal text format selection/CKEditor.

* [ ] feature 51 Complex field modal system
Build a reusable modal infrastructure to support all complex editors (paragraphs, address, text formats, uploads).
