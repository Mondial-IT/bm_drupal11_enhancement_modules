# Blue Marloc Tokens (`bm_tokens`)

Custom tokens for Drupal 11.2.5 (PHP 8.3) that provide:
- `bluemarloc:year-next-month` → Returns `YYYY-MM` for the first day of next month.
- `bluemarloc:term:{tid}:{view_mode}` → Renders a taxonomy term in a given view mode.
- `bluemarloc:basic-node` (chains into node tokens, e.g. `:title`) → Delegates to the Node token set for Node ID 1.

## Requirements
- Drupal **11.2.5**
- PHP **8.3**
- Core modules: **Token**, **Help**, **Help Topics** (for embedded documentation)

## Installation
1. Place the `bm_tokens` module in `web/modules/custom/bm_tokens`.
2. Enable via **Extend** (`/admin/modules`) or `drush en bm_tokens`.

## Usage

### Static date token
- Use `[bluemarloc:year-next-month]` to display the next month in `YYYY-MM`.

### Term render token
- Pattern: `[bluemarloc:term:{tid}:{view_mode}]`
- Example: `[bluemarloc:term:261:taxonomy_term_micro]`

> Ensure the `taxonomy_term` view mode exists and is configured.

### Basic node delegation
- Use chaining with Node tokens:
  - `[bluemarloc:basic-node:title]`
  - `[bluemarloc:basic-node:url]`

## Help & Documentation
- **Admin → Help → Blue Marloc Tokens**: Module help overview.
- **Help Topics**:
  - *Blue Marloc Tokens: Overview*
  - *Blue Marloc Tokens: Available Tokens*
  - *Blue Marloc Tokens: Examples & Recipes*

## Development Notes
- Tokens are declared via `hook_token_info()` and implemented via `hook_tokens()`.
- Internally, term rendering uses the entity view builder and the standard renderer service.
- `bluemarloc:basic-node:*` tokens are delegated to core `node` tokens for Node ID `1`.

## License
© Mondial-IT BV - Blue Marloc 2024. All rights reserved.
