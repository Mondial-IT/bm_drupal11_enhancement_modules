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
