# BM Drupal Enhancements - Menu Manage Admin

Enhances the Drupal admin menu manage page (`/admin/structure/menu/manage/admin`) with collapsible branches, per-level collapse controls, dual filters, and item counts to make drag/drop manageable on long menus.

## What it does
- Adds per-parent toggles (±) and per-level collapse buttons (levels 1–4).
- Provides dual quick filters so both source/target branches stay visible for drag/drop.
- Shows total and visible counts and uses light styling with a sticky header.
- Attaches via form alters to `menu_overview_form` and `menu_edit_form` without modifying core templates.

## Usage
- Controls appear above the menu table on the manage page.
- Collapse/expand by level or by branch; filters keep ancestors visible for matched items.
- Drag handles remain native; collapsed branches hide descendants to reduce clutter.

## Technical
- Library: `menu_manage_admin/menu_overview_enhance` (JS/CSS).
- Namespace: `bm_drupal_enhancements_menu_manage_admin`.
- Package: BM Drupal Enhancements.

## Maintenance
- Target form: `menu_overview_form` / `menu_edit_form`.
- Version marker in `drupalSettings.bm_menu_manage_admin.version`.
- Update codex docs on feature changes.
