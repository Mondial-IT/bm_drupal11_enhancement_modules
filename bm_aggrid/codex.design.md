# AG-Grid Drupal 11 Module - Development Codex

## Project Overview

This codex defines the complete specifications for building a Drupal 11 module that integrates AG-Grid for advanced data grid functionality. The module will provide configurable grid displays for content entities with progressive enhancement from basic display to complex inline editing.

## Development Environment

- **CMS**: Drupal 11.x
- **Dev Environment**: DDEV + Ubuntu
- **Grid Library**: AG-Grid Community Edition (via CDN)
- **Module Name**: `bm_aggrid`
- **Module Path**: `modules/custom/bm_aggrid`

## Module Structure

```
bm_aggrid/
├── bm_aggrid.info.yml
├── bm_aggrid.module
├── bm_aggrid.routing.yml
├── bm_aggrid.links.menu.yml
├── bm_aggrid.libraries.yml
├── bm_aggrid.permissions.yml
├── config/
│   ├── install/
│   │   └── bm_aggrid.settings.yml
│   └── schema/
│       └── bm_aggrid.schema.yml
├── src/
│   ├── Controller/
│   │   ├── AggridDisplayController.php
│   │   └── AggridAjaxController.php
│   ├── Form/
│   │   ├── AggridDisplayConfigForm.php
│   │   └── AggridDisplayEditForm.php
│   ├── Plugin/
│   │   └── views/
│   │       └── style/
│   │           └── AggridStyle.php
│   └── Service/
│       ├── AggridDataService.php
│       └── AggridConfigService.php
├── js/
│   ├── aggrid-init.js
│   ├── aggrid-editor.js
│   └── aggrid-ajax.js
├── css/
│   └── aggrid-display.css
└── templates/
    ├── aggrid-display-grid.html.twig
    └── aggrid-display-wrapper.html.twig
```

## Feature Implementation Checklist

### Phase 1: Foundation & Basic Display

* [ ] 001 Module Scaffold {Create basic module structure with info.yml, routing, and libraries}
  - Create `bm_aggrid.info.yml` with Drupal 11 compatibility
  - Define module dependencies (views, field, entity)
  - Set up module namespace and PSR-4 autoloading

* [ ] 002 Library Integration {Integrate AG-Grid library via CDN}
  - Define `bm_aggrid.libraries.yml` with AG-Grid Community CDN
  - Include jQuery dependency for compatibility
  - Add custom CSS for Drupal theme integration

* [ ] 003 Configuration Schema {Define configuration schema for grid settings}
  - Create `bm_aggrid.schema.yml` with grid configuration structure
  - Define settings for: entity_type, bundle, fields, pagination, row_height
  - Add default configuration in `bm_aggrid.settings.yml`

* [ ] 004 Admin Configuration Form {Build admin form for grid configuration}
  - Create `AggridDisplayConfigForm.php` extending ConfigFormBase
  - Add entity type selector (node, user, taxonomy_term, etc.)
  - Add bundle selector (dynamic based on entity type)
  - Add field multi-select for columns
  - Add pagination settings (enabled, page_size options)
  - Route: `/admin/config/content/aggrid-display`

* [ ] 005 Data Service {Create service to fetch and format entity data}
  - Create `AggridDataService.php` with dependency injection
  - Method: `getEntityData($entity_type, $bundle, $fields, $limit, $offset)`
  - Return structured array compatible with AG-Grid format
  - Handle field types: string, integer, boolean, timestamp
  - Apply Drupal access controls and entity queries

* [ ] 006 Basic Grid Controller {Create controller to render grid page}
  - Create `AggridDisplayController.php`
  - Method: `displayGrid()` - renders grid with configuration
  - Load configuration from config factory
  - Pass data to JavaScript via drupalSettings
  - Route: `/aggrid-display/grid/{config_id}`
  - Permission: `view aggrid displays`

* [ ] 007 Grid Template {Create Twig template for grid HTML structure}
  - Create `aggrid-display-grid.html.twig`
  - Define grid container div with data attributes
  - Include library attachments
  - Add drupalSettings for grid configuration

* [ ] 008 JavaScript Initialization {Create JS to initialize AG-Grid}
  - Create `aggrid-init.js` with Drupal behaviors
  - Read configuration from drupalSettings
  - Initialize AG-Grid with columnDefs from Drupal fields
  - Set rowData from Drupal-provided entity data
  - Configure basic grid options (theme, height, etc.)

* [ ] 009 Field Type Mapping {Map Drupal field types to AG-Grid column types}
  - In DataService, create method `mapFieldToColumnDef($field_definition)`
  - Map string → text column
  - Map integer/decimal → numericColumn
  - Map boolean → checkbox column
  - Map timestamp → dateColumn
  - Map entity_reference → text (entity label)

* [ ] 010 Basic Display Testing {Test basic grid display functionality}
  - Create test content (nodes with various field types)
  - Configure grid via admin form
  - Verify grid renders with correct data
  - Test with different entity types and bundles
  - Verify permissions work correctly

### Phase 2: Sort & Filter Features

* [ ] 011 Column Sorting {Enable sorting on all columns}
  - Update `aggrid-init.js` to set `sortable: true` in columnDefs
  - Configure multi-column sorting (Shift+Click)
  - Add default sort configuration option in admin form
  - Store sort state in configuration

* [ ] 012 Server-Side Sorting {Implement server-side sorting for large datasets}
  - Update DataService to accept sort parameters
  - Modify entity query to add sort conditions
  - Update AJAX endpoint to handle sort requests
  - Configure AG-Grid for serverSide row model

* [ ] 013 Column Filtering {Add filtering capability to columns}
  - Update columnDefs to set `filter: true`
  - Configure appropriate filter types per field:
    - Text fields → agTextColumnFilter
    - Numeric → agNumberColumnFilter
    - Date → agDateColumnFilter
    - Boolean → agSetColumnFilter
  - Enable floating filter row (`floatingFilter: true`)

* [ ] 014 Filter Configuration Options {Add filter settings to admin form}
  - Add checkbox to enable/disable filtering per column
  - Add filter type selection override
  - Add option for floating filters
  - Store in configuration schema

* [ ] 015 Server-Side Filtering {Implement server-side filtering}
  - Update DataService to accept filter parameters
  - Parse AG-Grid filter model to Drupal conditions
  - Apply conditions to entity query
  - Handle complex filter operators (contains, startsWith, equals, etc.)

* [ ] 016 Quick Filter {Add global search/quick filter}
  - Add search input field above grid
  - Implement AG-Grid quickFilter functionality
  - For server-side: search across configured text fields
  - Add configuration option to enable/disable quick filter

* [ ] 017 Filter Presets {Allow saving and loading filter presets}
  - Create configuration entity for filter presets
  - Add UI to save current filter state
  - Add dropdown to load saved presets
  - Store presets per grid configuration

* [ ] 018 Sort & Filter Testing {Test all sorting and filtering features}
  - Test single and multi-column sorting
  - Test each filter type with appropriate data
  - Test quick filter functionality
  - Test filter presets save/load
  - Verify server-side performance with large datasets

### Phase 3: Column Selection & Customization

* [ ] 019 Column Visibility Toggle {Add UI to show/hide columns}
  - Enable AG-Grid column menu (`suppressMenu: false`)
  - Add "Columns" tool panel to sidebar
  - Configure column chooser with drag-and-drop
  - Persist column visibility in user session

* [ ] 020 Column Reordering {Allow users to reorder columns}
  - Set `suppressMovableColumns: false` in gridOptions
  - Enable drag-and-drop column reordering
  - Persist column order in localStorage or user settings
  - Add reset to default button

* [ ] 021 Column Resizing {Enable column width adjustment}
  - Set `resizable: true` for all columns
  - Add auto-size columns button
  - Add size columns to fit button
  - Store column widths in user preferences

* [ ] 022 Column Pinning {Allow pinning columns left/right}
  - Enable column pinning in column menu
  - Add option to pin from admin configuration (default pins)
  - Persist pinning state
  - Ensure pinned columns work with reordering

* [ ] 023 Admin Column Configuration {Enhanced admin config for columns}
  - Add column-specific settings table in admin form
  - Configure per column: label, width, sortable, filterable, editable
  - Set default column visibility
  - Set default column order
  - Configure column pinning defaults

* [ ] 024 User Column Preferences {Save user-specific column settings}
  - Create user settings storage (localStorage or Drupal user data)
  - Save: visibility, order, width, pinning per grid config
  - Add "Reset to defaults" button
  - Load user preferences on grid initialization

* [ ] 025 Export Column State {Allow exporting column configuration}
  - Add "Export Column State" button
  - Generate JSON of current column configuration
  - Allow importing column state
  - Useful for sharing configurations between users

* [ ] 026 Responsive Column Behavior {Handle columns on mobile/tablet}
  - Configure column hide priority for responsive design
  - Add responsive column groups
  - Test grid behavior on various screen sizes
  - Consider mobile-specific column configurations

* [ ] 027 Column Selection Testing {Test all column customization features}
  - Test show/hide functionality
  - Test drag-and-drop reordering
  - Test column resizing and auto-sizing
  - Test pinning left and right
  - Test persistence across sessions
  - Test export/import of column state

### Phase 4: In-Place Editing (Simple Fields)

* [ ] 028 Edit Permissions {Set up editing permissions and access control}
  - Define permission: `edit entities via aggrid`
  - Check both aggrid permission AND entity edit permission
  - In DataService, add method: `userCanEditEntity($entity, $field_name)`
  - Configure editable columns based on permissions

* [ ] 029 Simple Text Editing {Enable inline editing for text fields}
  - Set `editable: true` for text field columns (with permission check)
  - Configure `cellEditor: 'agTextCellEditor'`
  - Add `onCellValueChanged` event handler
  - Validate input before accepting changes

* [ ] 030 Number Field Editing {Enable editing for numeric fields}
  - Configure `cellEditor: 'agNumberCellEditor'` for integer/decimal fields
  - Add min/max validation from field configuration
  - Handle decimal precision based on field settings
  - Validate numeric input

* [ ] 031 Boolean Field Editing {Enable toggle editing for boolean fields}
  - Use `cellRenderer: 'agCheckboxCellRenderer'` for display
  - Configure `cellEditor: 'agCheckboxCellEditor'` for editing
  - Handle immediate save on toggle
  - Show visual feedback on save

* [ ] 032 Select List Editing {Enable dropdown editing for list fields}
  - For fields with allowed values, configure `cellEditor: 'agSelectCellEditor'`
  - Populate `cellEditorParams.values` from field allowed values
  - Handle taxonomy term reference fields
  - Handle entity reference fields (select by label)

* [ ] 033 AJAX Save Endpoint {Create endpoint to save cell edits}
  - Create `AggridAjaxController::saveCellEdit()` method
  - Route: `/aggrid-display/ajax/save-cell` (POST)
  - Accept: entity_type, entity_id, field_name, new_value
  - Validate: permissions, field access, field type
  - Load entity, set field value, validate, save
  - Return JSON: {success: true/false, message: '', updated_value: ''}

* [ ] 034 JavaScript AJAX Handler {Create JS to handle save requests}
  - Create `aggrid-ajax.js` with AJAX save function
  - In onCellValueChanged: call AJAX endpoint with cell data
  - Show loading indicator on cell during save
  - Handle success: show success feedback, update row data
  - Handle failure: revert cell value, show error message
  - Implement retry logic for failed saves

* [ ] 035 Validation Feedback {Add visual feedback for validation}
  - Show saving state (spinner or color change)
  - Show success state (green border, checkmark)
  - Show error state (red border, error icon)
  - Display validation error messages in tooltip or alert
  - Auto-clear feedback after timeout

* [ ] 036 Field-Level Edit Control {Configure editability per field in admin}
  - Add "Editable" checkbox per column in admin form
  - Store in configuration
  - Apply in JavaScript when building columnDefs
  - Document which field types support inline editing

* [ ] 037 Edit Mode Toggle {Add toggle between view and edit modes}
  - Add "Edit Mode" button above grid
  - Toggle `editable` property on all configured columns
  - Change grid appearance to indicate edit mode
  - Consider making this a per-user preference

* [ ] 038 Unsaved Changes Warning {Warn users about unsaved changes}
  - Track cells with pending/failed saves
  - Show indicator for rows with unsaved changes
  - Add "Save All" button to retry failed saves
  - Warn on page navigation if unsaved changes exist

* [ ] 039 Batch Edit Support {Allow editing multiple cells before save}
  - Add option to batch changes before saving
  - Collect all changes in memory
  - Provide "Save Changes" button to commit all at once
  - Update AJAX endpoint to handle batch saves

* [ ] 040 Simple Editing Testing {Test all simple field editing features}
  - Test text, number, boolean, select editing
  - Test permission checks (denied users can't edit)
  - Test validation (invalid values rejected)
  - Test AJAX save success and failure scenarios
  - Test visual feedback for all states
  - Test batch editing if implemented

### Phase 5: Complex Field Editing

* [ ] 041 Date Field Editing {Enable date/datetime field editing}
  - Configure custom date picker cell editor
  - Use Drupal date format settings
  - Handle date, datetime, and daterange fields
  - Validate date formats and ranges
  - Display dates according to user timezone

* [ ] 042 File/Image Field Display {Display file and image fields in grid}
  - For image fields: show thumbnail with `cellRenderer`
  - For file fields: show filename and download link
  - Add configuration for thumbnail size
  - Handle multiple cardinality (show count + link)

* [ ] 043 File/Image Upload Editing {Enable file/image upload via grid}
  - Create custom cell editor with file upload input
  - Use Drupal File API to handle upload
  - Create AJAX endpoint: `/aggrid-display/ajax/upload-file`
  - Validate file type, size, extensions
  - Update entity field with new file ID
  - Show thumbnail of uploaded image

* [ ] 044 Entity Reference Autocomplete {Enable autocomplete for entity references}
  - Create custom cell editor with autocomplete input
  - Use Drupal autocomplete endpoint or create custom
  - Search referenced entity type (nodes, users, terms, etc.)
  - Display entity label, save entity ID
  - Handle multiple cardinality references

* [ ] 045 Multi-Value Field Handling {Support fields with multiple values}
  - Display multi-value fields as comma-separated or count
  - Add cell renderer to show all values in tooltip
  - Create custom editor to add/remove/reorder values
  - Update AJAX endpoint to handle multi-value saves
  - Consider showing multi-value fields in expanded row detail

* [ ] 046 Paragraph Fields Display {Display paragraph field data in grid}
  - Show paragraph summary in cell (first text field or label)
  - Add cell renderer with "View Details" button
  - On click, show modal with full paragraph field values
  - Handle nested paragraph structures
  - Show count for multi-value paragraph fields

* [ ] 047 Paragraph Fields Editing {Enable editing of paragraph fields}
  - On cell click/edit, open modal form
  - Render paragraph form fields in modal (Drupal Form API)
  - Submit paragraph edits via AJAX
  - Update paragraph entity, validate, save
  - Return updated summary to grid cell
  - Handle add/remove for multi-value paragraphs

* [ ] 048 Address Field Support {Support address field complex widget}
  - Display address as formatted string in cell
  - On edit, open modal with full address form
  - Include all address components (street, city, country, etc.)
  - Validate using Drupal address field validation
  - Save entire address structure via AJAX

* [ ] 049 Link Field Editing {Enable editing of link fields}
  - Display link text and URL in cell
  - Create custom editor with URL and title inputs
  - Validate URL format
  - Handle link attributes (target, rel, etc.)
  - Save complete link field structure

* [ ] 050 Text Format Fields {Handle text with format (filtered HTML)}
  - Display formatted text in cell (render as HTML or plain text)
  - On edit, open modal with text format selector
  - Include text format selector (full HTML, basic HTML, plain text)
  - If allowed, include CKEditor or text editor
  - Save text and format value via AJAX

* [ ] 051 Complex Field Modal System {Build reusable modal system for complex editing}
  - Create JavaScript modal component
  - Integrate with Drupal dialog/modal system
  - Handle loading states (fetch form via AJAX)
  - Handle form submission and validation
  - Return to grid on save, update cell display
  - Handle cancel/close without saving

* [ ] 052 Complex Field Admin Settings {Configure complex field behavior in admin}
  - Add settings for which fields use modal editing
  - Configure thumbnail sizes for image fields
  - Set autocomplete result limits for entity references
  - Configure paragraph display format (summary vs full)
  - Set modal size preferences per field type

* [ ] 053 Field Widget Integration {Integrate Drupal field widgets}
  - For complex fields, render actual Drupal field widgets in modal
  - Use AJAX to fetch field widget form elements
  - Preserve widget configuration (e.g., image upload settings)
  - Validate using Drupal field validation
  - Ensure compatibility with contrib field types

* [ ] 054 Complex Editing Testing {Test all complex field editing features}
  - Test date picker functionality
  - Test file/image upload and display
  - Test entity reference autocomplete
  - Test multi-value field editing
  - Test paragraph field display and editing
  - Test address, link, and text format fields
  - Test modal system functionality
  - Test validation for all complex field types

### Phase 6: Advanced Features & Polish

* [ ] 055 Pagination {Implement client and server-side pagination}
  - Configure AG-Grid pagination settings
  - Add pagination controls below grid
  - Add page size selector
  - For server-side: implement infinite scroll row model
  - Add total row count display

* [ ] 056 Row Selection {Enable row selection functionality}
  - Add checkbox column for row selection
  - Configure single vs multi-row selection
  - Add "Select All" functionality
  - Emit selection events for integration
  - Add bulk actions dropdown for selected rows

* [ ] 057 Bulk Actions {Implement bulk operations on selected rows}
  - Add bulk actions menu (delete, publish, unpublish, etc.)
  - Create AJAX endpoint for bulk operations
  - Show confirmation dialog for destructive actions
  - Update grid after bulk action completes
  - Show progress indicator for long operations

* [ ] 058 Export Functionality {Add data export features}
  - Add "Export to CSV" button
  - Add "Export to Excel" button (if AG-Grid Enterprise, else CSV only)
  - Configure export to include only visible columns
  - Add option to export filtered data vs all data
  - Generate filename based on grid config and timestamp

* [ ] 059 Context Menu {Add right-click context menu}
  - Enable AG-Grid context menu
  - Add menu items: Copy, Copy with Headers, Export
  - Add custom menu items: Edit in Drupal form, View Entity
  - Add entity-specific actions (delete, clone, etc.)
  - Respect permissions for menu items

* [ ] 060 Master-Detail View {Add expandable row details}
  - Configure master-detail for entity relationships
  - On row expand, show related entities or full field details
  - Render detail as nested grid or formatted display
  - Add configuration for which fields to show in detail
  - Handle loading detail data via AJAX

* [ ] 061 Grouping {Add row grouping functionality}
  - Configure grouping by specific columns
  - Add group row renderer
  - Show aggregate data in group rows (count, sum, avg)
  - Allow collapsing/expanding groups
  - Add admin configuration for default grouping

* [ ] 062 Aggregation {Add column aggregation features}
  - Configure aggregation functions: sum, avg, count, min, max
  - Show aggregation in footer row
  - Update aggregation when data changes
  - Configure which columns show aggregation
  - Handle aggregation with grouped data

* [ ] 063 Custom Cell Renderers {Create Drupal-specific cell renderers}
  - Create renderer for entity status (published/unpublished)
  - Create renderer for user pictures
  - Create renderer for operation links (edit, delete, view)
  - Create renderer for taxonomy term chips
  - Make renderers themeable via Twig

* [ ] 064 Views Integration {Integrate with Drupal Views}
  - Create Views style plugin: `AggridStyle.php`
  - Allow using Views UI to configure grid data source
  - Inherit Views filters, sorts, and relationships
  - Map Views fields to AG-Grid columns
  - Support Views contextual filters
  - Add grid-specific display options in Views

* [ ] 065 REST API Integration {Create REST endpoints for external access}
  - Create REST resource for grid data
  - Endpoint: `/api/aggrid/{config_id}/data`
  - Support query parameters: sort, filter, page, pageSize
  - Return data in AG-Grid compatible JSON format
  - Implement authentication and permissions
  - Add CORS configuration options

* [ ] 066 Caching Strategy {Implement caching for performance}
  - Cache grid configuration per config entity
  - Cache entity data with appropriate cache tags
  - Invalidate cache on entity updates
  - Add cache configuration options in admin
  - Consider BigPipe or lazy loading for large grids

* [ ] 067 Performance Optimization {Optimize for large datasets}
  - Implement virtual scrolling (already in AG-Grid)
  - Optimize database queries with indexes
  - Implement query result caching
  - Add configurable row limit warnings
  - Profile and optimize JavaScript performance
  - Implement lazy loading of complex fields

* [ ] 068 Theming & Styling {Make grid themeable}
  - Support AG-Grid themes: Alpine, Balham, Material
  - Create Drupal-specific theme integration
  - Add CSS variables for easy customization
  - Ensure accessibility (WCAG 2.1 AA compliance)
  - Add RTL support
  - Make responsive on mobile devices

* [ ] 069 Accessibility {Ensure WCAG compliance}
  - Add keyboard navigation support
  - Ensure screen reader compatibility
  - Add ARIA labels and roles
  - Test with keyboard-only navigation
  - Test with screen readers (NVDA, JAWS)
  - Add high contrast mode support

* [ ] 070 Error Handling & Logging {Implement comprehensive error handling}
  - Add try-catch blocks for all operations
  - Log errors to Drupal watchdog
  - Show user-friendly error messages
  - Add debug mode configuration
  - Implement graceful degradation
  - Add error boundary for JavaScript

* [ ] 071 Documentation {Create comprehensive documentation}
  - Write README.md with installation instructions
  - Document all configuration options
  - Create user guide for common tasks
  - Document API for developers
  - Add inline code comments
  - Create video tutorials for complex features

* [ ] 072 Automated Tests {Write comprehensive test coverage}
  - Unit tests for services (DataService, ConfigService)
  - Kernel tests for data retrieval and saving
  - Functional tests for UI interactions
  - JavaScript tests for grid initialization
  - Integration tests for AJAX operations
  - Performance tests for large datasets

* [ ] 073 Security Audit {Perform security review}
  - Review all permission checks
  - Audit AJAX endpoints for CSRF protection
  - Review input validation and sanitization
  - Test for SQL injection vulnerabilities
  - Test for XSS vulnerabilities
  - Review file upload security

* [ ] 074 Module Package {Prepare module for distribution}
  - Update composer.json with dependencies
  - Add LICENSE file
  - Create CHANGELOG.md
  - Add module to drupal.org (if public)
  - Create release tags
  - Set up CI/CD pipeline

## Configuration Reference

### Grid Configuration Object Structure

```yaml
bm_aggrid.settings:
  grids:
    example_grid:
      id: 'example_grid'
      label: 'Example Grid'
      entity_type: 'node'
      bundle: 'article'
      fields:
        - field_name: 'title'
          label: 'Title'
          width: 200
          sortable: true
          filterable: true
          editable: false
          pinned: 'left'
        - field_name: 'created'
          label: 'Created'
          width: 150
          sortable: true
          filterable: true
          editable: false
          type: 'date'
        - field_name: 'status'
          label: 'Published'
          width: 100
          sortable: true
          filterable: true
          editable: true
          type: 'boolean'
      pagination:
        enabled: true
        page_size: 50
        page_size_options: [25, 50, 100, 200]
      features:
        quick_filter: true
        column_menu: true
        context_menu: true
        export: true
        grouping: false
        master_detail: false
      theme: 'ag-theme-alpine'
      row_height: 35
```

## JavaScript Configuration Example

```javascript
// drupalSettings.aggridDisplay
{
  grid_id: 'example_grid',
  config: {
    entity_type: 'node',
    bundle: 'article',
    columnDefs: [
      {
        field: 'title',
        headerName: 'Title',
        width: 200,
        sortable: true,
        filter: 'agTextColumnFilter',
        editable: false,
        pinned: 'left'
      },
      {
        field: 'created',
        headerName: 'Created',
        width: 150,
        sortable: true,
        filter: 'agDateColumnFilter',
        valueFormatter: params => new Date(params.value * 1000).toLocaleDateString()
      },
      {
        field: 'status',
        headerName: 'Published',
        width: 100,
        sortable: true,
        filter: 'agSetColumnFilter',
        editable: true,
        cellRenderer: 'agCheckboxCellRenderer',
        cellEditor: 'agCheckboxCellEditor'
      }
    ],
    rowData: [], // Loaded via AJAX or embedded
    pagination: true,
    paginationPageSize: 50,
    paginationPageSizeSelector: [25, 50, 100, 200]
  },
  endpoints: {
    data: '/aggrid-display/ajax/data/example_grid',
    save: '/aggrid-display/ajax/save-cell',
    upload: '/aggrid-display/ajax/upload-file'
  },
  permissions: {
    can_edit: true,
    editable_fields: ['status', 'field_tags']
  }
}
```

## DDEV Setup Commands

```bash
# Initial project setup
mkdir drupal-aggrid && cd drupal-aggrid
ddev config --project-type=drupal11 --docroot=web
ddev start
ddev composer create drupal/recommended-project
ddev drush site:install --account-name=admin --account-pass=admin

# Create custom module directory
mkdir -p web/modules/custom/bm_aggrid

# Download module scaffold (when available as zip)
cd web/modules/custom
# Extract bm_aggrid.zip here

# Enable module
ddev drush en bm_aggrid -y
ddev drush cr

# Create sample content for testing
ddev drush generate:content 100

# Access site
ddev launch
```

## Development Workflow

1. **Set up DDEV environment** (see commands above)
2. **Implement features sequentially** by feature number
3. **For each feature:**
  - Read feature description
  - Implement code changes
  - Test functionality manually
  - Run `ddev drush cr` to clear cache
  - Verify in browser
  - Commit changes with feature number in commit message
4. **Testing checkpoints:**
  - After Phase 1: Verify basic grid display works
  - After Phase 2: Test sorting/filtering with large datasets
  - After Phase 3: Test column customization persistence
  - After Phase 4: Test simple field editing with various field types
  - After Phase 5: Test complex field editing (dates, paragraphs, files)
  - After Phase 6: Full regression testing and performance testing

## API Endpoints Reference

### GET `/aggrid-display/ajax/data/{config_id}`
Fetch grid data with filtering, sorting, pagination.

**Query Parameters:**
- `startRow`: Starting row index (pagination)
- `endRow`: Ending row index (pagination)
- `sortModel`: JSON encoded sort configuration
- `filterModel`: JSON encoded filter configuration

**Response:**
```json
{
  "data": [...],
  "totalRows": 1000
}
```

### POST `/aggrid-display/ajax/save-cell`
Save single cell edit.

**Request Body:**
```json
{
  "entity_type": "node",
  "entity_id": 123,
  "field_name": "title",
  "value": "New Title"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Saved successfully",
  "updated_value": "New Title"
}
```

### POST `/aggrid-display/ajax/upload-file`
Upload file for file/image field.

**Request:** multipart/form-data with file and metadata

**Response:**
```json
{
  "success": true,
  "file_id": 456,
  "url": "/sites/default/files/image.jpg",
  "thumbnail": "/sites/default/files/styles/thumbnail/image.jpg"
}
```

## Testing Commands

```bash
# Run PHPUnit tests
ddev exec vendor/bin/phpunit -c web/core web/modules/custom/bm_aggrid

# Run PHPCS code standards check
ddev exec vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/bm_aggrid

# Run PHPStan static analysis
ddev exec vendor/bin/phpstan analyze web/modules/custom/bm_aggrid

# JavaScript linting (if configured)
ddev exec npm run lint --prefix web/modules/custom/bm_aggrid
```

## Performance Considerations

- Use Views caching for data queries
- Implement lazy loading for complex fields (paragraphs, files)
- Consider using AG-Grid Enterprise for server-side row model with very large datasets (10,000+ rows)
- Use database indexes on commonly filtered/sorted fields
- Implement queue workers for bulk operations
- Cache rendered cell values when possible
- Use BigPipe or similar for progressive enhancement

## Security Checklist

- [ ] All AJAX endpoints check CSRF tokens
- [ ] Entity access checks before any operation
- [ ] Field access checks before display/edit
- [ ] Input sanitization on all user-provided values
- [ ] SQL injection prevention (use entity query API)
- [ ] XSS prevention (use Xss::filter or t() functions)
- [ ] File upload validation (type, size, extension)
- [ ] Rate limiting on AJAX endpoints
- [ ] Proper permission definitions and checks

## Browser Compatibility

- Chrome 90+ (primary testing)
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile: iOS Safari 14+
