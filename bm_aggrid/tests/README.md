# Basic display test plan (Feature 10)

Manual verification steps for the base AG Grid display:

1) Create content:
- Add a node type with mixed fields: text, integer, boolean, datetime, entity reference.
- Create a few sample nodes with distinct values to verify sorting/filtering visibility.

2) Configure a display:
- Visit `/admin/config/content/aggrid-display`.
- Add a display with the node type/bundle, select the created fields, set rows per page, and pick a theme.

3) View the grid:
- Open `/aggrid-display/grid/{display_id}`.
- Confirm rows render and field values show correctly (boolean as true/false, dates formatted, references as labels).

4) Grid behavior:
- Confirm AG Grid initializes (no JS console errors).
- Verify column headers match selected fields and resizing/column flex works.
- If pagination enabled, page through and confirm row counts change.

5) Permissions:
- Access as an unauthenticated user (should be denied without permission).
- Access with `view aggrid displays` permission (should render).

6) Regression:
- Change display configuration (different bundle/fields) and confirm grid updates after cache rebuild if necessary.
