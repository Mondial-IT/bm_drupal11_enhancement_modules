(function (Drupal, once) {
  Drupal.behaviors.bmMenuManageAdmin = {
    attach(context) {
      const tables = once('bm-menu-manage', 'table[data-drupal-selector="edit-links"]', context);

      tables.forEach((table) => {
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        if (!rows.length) {
          return;
        }

        const rowById = {};
        const childrenMap = {};
        const toggles = new Map();

        // Build depth/parent map and collect toggles.
        const lastAtDepth = [];
        rows.forEach((row, index) => {
          if (!row.id) {
            row.id = `bm-menu-row-${index}`;
          }
          rowById[row.id] = row;

          const depth = row.querySelectorAll('.indentation').length;
          row.dataset.depth = depth;

          const parent = depth > 0 ? lastAtDepth[depth - 1] : null;
          if (parent) {
            row.dataset.parentRowId = parent.id;
            parent.dataset.hasChildren = '1';
            if (!childrenMap[parent.id]) {
              childrenMap[parent.id] = [];
            }
            childrenMap[parent.id].push(row);
          }
          lastAtDepth[depth] = row;

          const toggle = row.querySelector('.bm-menu-toggle');
          if (toggle) {
            toggles.set(row.id, toggle);
            toggle.checked = false;
          }
        });

        const collapseLevelControls = Array.from(context.querySelectorAll('.bm-menu-collapse-level'));
        const expandAllBtn = context.querySelector('.bm-menu-expand-all');
        const filterInput = context.querySelector('.bm-menu-filter');

        const hideRow = (row) => row.classList.add('bm-menu-hidden');
        const showRow = (row) => row.classList.remove('bm-menu-hidden');

        const collapseBranch = (row) => {
          row.classList.add('bm-menu-collapsed');
          row.setAttribute('aria-expanded', 'false');
          const toggle = toggles.get(row.id);
          if (toggle) {
            toggle.checked = true;
          }
          const kids = childrenMap[row.id] || [];
          kids.forEach((child) => {
            hideRow(child);
            collapseBranch(child);
          });
        };

        const expandBranch = (row) => {
          row.classList.remove('bm-menu-collapsed');
          row.setAttribute('aria-expanded', 'true');
          const toggle = toggles.get(row.id);
          if (toggle) {
            toggle.checked = false;
          }
          const kids = childrenMap[row.id] || [];
          kids.forEach((child) => {
            showRow(child);
            if (child.classList.contains('bm-menu-collapsed')) {
              collapseBranch(child);
            }
          });
        };

        const toggleBranch = (row) => {
          if (row.classList.contains('bm-menu-collapsed')) {
            expandBranch(row);
          }
          else {
            collapseBranch(row);
          }
        };

        // Row-level toggles.
        toggles.forEach((toggle, rowId) => {
          const row = rowById[rowId];
          if (!row) {
            return;
          }
          toggle.addEventListener('change', () => {
            if (toggle.checked) {
              collapseBranch(row);
            }
            else {
              expandBranch(row);
            }
          });
        });

        // Level toggle controls.
        collapseLevelControls.forEach((control) => {
          control.addEventListener('change', () => {
            const level = parseInt(control.dataset.bmMenuLevel || '0', 10);
            const checked = control.checked;

            if (checked) {
              collapseLevelControls.forEach((other) => {
                const otherLevel = parseInt(other.dataset.bmMenuLevel || '0', 10);
                if (otherLevel > level) {
                  other.checked = true;
                }
              });
              rows.forEach((row) => {
                const depth = parseInt(row.dataset.depth || '0', 10);
                if (depth === level && row.dataset.hasChildren === '1') {
                  collapseBranch(row);
                }
              });
            }
            else {
              rows.forEach((row) => {
                const depth = parseInt(row.dataset.depth || '0', 10);
                if (depth === level && row.dataset.hasChildren === '1') {
                  expandBranch(row);
                }
              });
            }
          });
        });

        expandAllBtn?.addEventListener('click', (e) => {
          e.preventDefault();
          rows.forEach((row) => {
            showRow(row);
            row.classList.remove('bm-menu-collapsed');
            row.setAttribute('aria-expanded', 'true');
          });
          toggles.forEach((toggle) => {
            toggle.checked = false;
            toggle.setAttribute('aria-expanded', 'true');
          });
          collapseLevelControls.forEach((control) => {
            control.checked = false;
          });
        });

        const getLabel = (row) => {
          const link = row.querySelector('.menu-item__link');
          const fallback = row.querySelector('td');
          return (link ? link.textContent : (fallback ? fallback.textContent : '')).toLowerCase();
        };

        const applyFilter = () => {
          const query = (filterInput?.value || '').trim().toLowerCase();
          if (!query) {
            rows.forEach((row) => showRow(row));
            return;
          }

          rows.forEach((row) => hideRow(row));
          rows.forEach((row) => {
            if (getLabel(row).includes(query)) {
              showRow(row);
              let ancestorId = row.dataset.parentRowId;
              while (ancestorId) {
                const ancestor = rowById[ancestorId];
                if (!ancestor) {
                  break;
                }
                showRow(ancestor);
                ancestorId = ancestor.dataset.parentRowId;
              }
            }
          });
        };

        filterInput?.addEventListener('input', applyFilter);
      });
    },
  };
})(Drupal, once);
