<!-- modules/custom/gmaps_fix/README.md -->
# Google Maps Fix for Gavias Ziston (Drupal 11)

This module fixes Google Maps loading issues in the **Gavias Ziston** Drupal
theme. Some installations load OpenStreetMap successfully but fail to load
Google Maps or Mapbox tiles. Issues often originate from script order,
missing API keys, or theme-level integration problems.

## Purpose

- Load the Google Maps API script dynamically.
- Use the API key configured in the Gavias Ziston theme settings.
- Avoid multiple script injections.
- Provide a stable integration layer without modifying theme files.

## Features

- Automatic script attachment via `hook_page_attachments()`.
- Clean Drupal Settings integration.
- Help topic for Drupal’s built-in help system.
- Zero hardcoded keys.

## Installation

1. Place the module into `modules/custom/gmaps_fix`.
2. Enable it from **Extend**.
3. Add your Google Maps API key under: (both)
   **/admin/appearance/settings/ziston_sub**.
   **/admin/config/services/gmap-field-settings**
4. Clear caches.

## Project Status

Production-ready for Drupal 11 sites using Gavias Ziston where Google Maps does
not display tiles by default.

## License

MIT.


<details><summary>Details</summary>

Short version: it’s a **loading-order + duplication problem** in the theme’s JS, amplified by how Google’s loader works.

## What’s actually happening

1. **The theme injects Google Maps “directly”**
   Gavias Ziston typically references the API URL in a library or inline tag without `loading=async` (and sometimes without `async`/`defer`). The API detects that it wasn’t loaded via the modern async pattern and prints:

   > “has been loaded directly without loading=async…”

2. **Scripts can be injected more than once**
   Multiple widgets/sections attach the same library, resulting in **duplicate `<script>` tags** for `maps.googleapis.com`. Google’s loader tolerates this but you get race conditions, late init, and warnings.

3. **Conditional presence isn’t checked**
   The script is loaded on pages **without** a Google map, and on pages **with multiple map providers** (OSM/Mapbox). Unnecessary loads increase the chance of interleaving/ordering bugs.

4. **Initialization while hidden**
   Maps often initialize inside tabs/accordions/carousels (i.e., `display:none`). The map instance exists, but the **tile layers won’t render** until a resize occurs. If the theme doesn’t trigger a resize when the container becomes visible, you see blank tiles.

5. **Drupal attachment semantics**
   If the library is attached from several places (blocks, views, paragraphs), Drupal will try to coalesce, but **external URLs** in different libs/weights may still end up emitted in bad order relative to the theme’s init code.

6. **CSP or key restrictions can mask the problem**
   Even with correct loading, tiles won’t appear if CSP blocks `maps.googleapis.com / gstatic.com` (or if the **API key/billing/referrer** isn’t valid). That often coexists with the loading warning, making diagnosis confusing.

## Why the warning specifically

Google now recommends `?loading=async` **plus** using `async` (and usually `defer`) on the script tag. When the API sees a direct, synchronous-style include (common in older themes/libraries.yml), it warns about “suboptimal performance.” It’s also a red flag that **other best practices (single loader, conditional load, visibility-safe init)** might be missing.

## How the fixes address it

* **Single, conditional loader**: only add the API script when a map DOM selector exists; never add it twice.
* **Modern load flags**: `?loading=async` + `async` + `defer` on the script.
* **Visibility-safe init**: trigger a `resize` once the map container becomes visible (IntersectionObserver).
* **Centralized credentials**: pull the key/client ID from `google_map_field.settings` (or theme/settings.php override) so you’re not hardcoding it in multiple places.
* **Library alter** (optional): strip any hard-coded API URLs from Gavias libs so the unified loader controls order and duplication.

Result: the warning disappears, tiles render reliably, and the API only loads when truly needed.


</details>
