<!-- bm_core/help/topics/theme-switcher.md -->
# Theme Switcher

The Theme Switcher component allows users to toggle between:

- Light mode
- Dark mode
- System (OS preference)

## How it works

- CSS variables define theme colors
- JavaScript toggles a `data-theme` attribute on `<html>`
- System mode uses `prefers-color-scheme`
- User preference is stored in `localStorage`

## Usage

Use the render element in forms or render arrays:

``
#type: bm_theme_switcher
```


The component automatically attaches required assets.

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
