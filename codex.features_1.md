# Feature development — Admin menu UX (menu_manage_admin)

Use these instructions under `menu_manage_admin` to plan and execute the admin menu UX enhancement for `/admin/structure/menu/manage/admin`.

## Feature 1.0 Collapsible, manageable admin menu list
* [x] Design and implement collapsible/expandable tree controls on the menu manage form (`menu_overview_form`/`menu_edit_form`), reducing drag-drop complexity on long lists.
* [x] Add an “Expand all / Collapse by level” control group and per-parent toggles, preserving keyboard accessibility (`aria-expanded`, focus management).
* [x] Disable drag handles on collapsed branches to prevent accidental moves; re-enable on expand.
* [x] Add dual quick filters to highlight and keep only matching items (keep parents visible when descendants match) to aid drag/drop between branches.
* [x] Provide subtle styling (indentation guides, sticky header) while keeping admin-theme friendly.
* [x] Ship the behavior as a `menu_manage_admin` library (JS + CSS) attached via form alters.
* [x] Document the feature in README/help (what it does, how to use toggles/filter, accessibility notes).
codex: Ported the bm_main menu manage enhancer into this module with toggles, per-level collapse, dual filters, counts, and dedicated JS/CSS library attached to menu_overview/menu_edit forms.

## Feature 6 enhancements
* [x] add table column heading Collapse / Expand to the column after `Menu link`.
* [x] undo the second filter input and function.
codex: Added header insertion in JS for the toggle column and removed the secondary filter field/logic; controls now use a single quick filter.

## Feature 7 Serverside preparation
* [x] refactor the menu_manage_admin module menu_overview.js and menu_manage_admin.module to:
- add the 'expand/collapse column function' in the serverside form_alter function rendering it server side, key: `$form['links']['links']`
- make sure the table header is set in the form_alter as well key: `$form['links`]['#header']
codex: Header now injected server-side and each row renders a toggle column via form alter; JS consumes server-rendered toggles without adding columns client-side.

## Feature 8 GUI enhancements
- [x] change the collapse/expand input button type to input checkbox boolean type, where checked means Collapsed, unchecked = expanded.
- [x] alter the corresponding column label from "Collapse/Expanse" to "Collapsed"
- [x] remove the counts functionality from the js and display
- [x] the 'level' collapse buttons should be a toggle:
- higher levels collapse sets the lower level collapsed buttons to active (collapsed) to.
- when clicked, they remain active state, until `expand all (+)` deactivates them.
codex: Collapse column now renders checkbox inputs and header "Collapsed"; level controls are checkboxes with cascading activation, counts removed from UI/JS, and expand-all clears all toggles.
