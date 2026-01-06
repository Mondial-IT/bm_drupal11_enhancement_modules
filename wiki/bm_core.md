# bm_core – Theme Switcher Component

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

## Installation

Place the component inside your existing module:

* modules/custom/bm_core


Ensure the following files exist:

bm_core/
├── bm_core.info.yml
├── bm_core.libraries.yml
├── bm_core.module
├── css/theme-switcher.css
├── js/theme-switcher.js
├── src/Element/ThemeSwitcher.php
├── templates/bm-theme-switcher.html.twig
├── help/bm_core.help.yml
├── help/topics/theme-switcher.md
├── src/Form/ThemeSwitcherDemoForm.php

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

## see ThemeSwitcherDemoForm example

`/admin/config/bm-core/theme-switcher-demo`
