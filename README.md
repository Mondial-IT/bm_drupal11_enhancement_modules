# BM Drupal Enhancements - Menu Manage Admin

Enhances the Drupal admin menu manage page (`/admin/structure/menu/manage/admin`) with collapsible branches, per-level collapse controls, quick filter, and item counts to make drag/drop manageable on long menus.

## What it does
- Adds per-parent collapse/expand checkboxes and per-level collapse toggles (levels 1–4) with a dedicated “Collapsed” column header.
- Provides a single quick filter that keeps matched items and their ancestors visible for drag/drop.
- Uses light styling with a sticky header; all markup is rendered server-side for stability.
- Attaches via form alters to `menu_overview_form` and `menu_edit_form` without modifying core templates; expand/collapse column is rendered server-side for stable markup.

## Usage
- Controls appear above the menu table on the manage page.
- Collapse/expand by level or by branch; filters keep ancestors visible for matched items.
- Drag handles remain native; collapsed branches hide descendants to reduce clutter.

## Technical
- Module machine name: `menu_manage_admin`.
- Library: `menu_manage_admin/menu_overview_enhance` (JS/CSS).
- Package: BM Drupal Enhancements.

## Maintenance
- Target form: `menu_overview_form` / `menu_edit_form`.
- Version marker in `drupalSettings.menu_manage_admin.version`.
- Update codex docs on feature changes.

# BM Drupal Enhancements -  bm_core – Theme Switcher Component

A lightweight, forward-compatible **Light / Dark / System theme switcher** component for **Drupal 11.2.5**.

This component is implemented as a **render element** and can be embedded in any form or render array.
It uses **plain CSS variables**, **plain JavaScript**, and respects the user’s OS preference via
`prefers-color-scheme`.

## Features

- Light / Dark / System modes
- OS theme aware
- Persistent via `localStorage`
- No jQuery
- No dependencies beyond Drupal core
- Safe for reuse in forms and layouts

Clear caches after installation.

## Usage in Forms

```
$form['theme_switcher'] = [
  '#type' => 'bm_theme_switcher',
];
```

## Usage in Render Arrays
```
$build['theme_switcher'] = [
  '#type' => 'bm_theme_switcher',
];
```
