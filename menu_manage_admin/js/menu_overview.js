(function (Drupal, once) {
  /**
   * Menu manage admin enhancer.
   * Version: 1.1.1 (bm_menu_manage_admin).
   */
  Drupal.behaviors.bmMenuManageAdmin = {
    attach(context) {
      const tables = once('bm-menu-manage', 'table[data-drupal-selector="edit-links"]', context);
      tables.forEach((table) => {
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        if (!rows.length) {
          return;
        }

        // Build depth/parent map using indentation count.
        const lastAtDepth = [];
        rows.forEach((row, index) => {
          const indentCells = row.querySelectorAll('.indentation');
          const depth = indentCells ? indentCells.length : 0;
          row.dataset.depth = depth;
          const parent = depth > 0 ? lastAtDepth[depth - 1] : null;
          if (parent) {
            row.dataset.parentRowId = parent.id;
            parent.dataset.hasChildren = '1';
          }
          if (!row.id) {
            row.id = `bm-menu-row-${index}`;
          }
          lastAtDepth[depth] = row;
        });

        // Insert toggle column to maintain alignment.
        rows.forEach((row) => {
          const handleCell = row.querySelector('td.tabledrag-cell');
          if (!handleCell || !handleCell.nextElementSibling) {
            return;
          }

          const toggleCell = document.createElement('td');
          toggleCell.className = 'bm-menu-toggle-cell';

          if (row.dataset.hasChildren === '1') {
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'bm-menu-toggle';
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('data-target-row', row.id);
            toggle.textContent = '−';
            toggle.addEventListener('click', () => toggleBranch(row, toggle));
            toggleCell.appendChild(toggle);
          }
          else {
            const spacer = document.createElement('span');
            spacer.className = 'bm-menu-toggle-spacer';
            spacer.textContent = ' ';
            toggleCell.appendChild(spacer);
          }

          handleCell.parentNode.insertBefore(toggleCell, handleCell.nextElementSibling);
        });

        const collapseLevelButtons = context.querySelectorAll('.bm-menu-collapse-level');
        const expandAllBtn = context.querySelector('.bm-menu-expand-all');
        const filterInput = context.querySelector('.bm-menu-filter');
        const filterSecondary = context.querySelector('.bm-menu-filter-secondary');
        const totalCountEl = context.querySelector('.bm-menu-count-total');
        const visibleCountEl = context.querySelector('.bm-menu-count-visible');

        const hideRow = (row) => row.classList.add('bm-menu-hidden');
        const showRow = (row) => row.classList.remove('bm-menu-hidden');

        const updateCounts = () => {
          if (totalCountEl) {
            totalCountEl.textContent = rows.length.toString();
          }
          if (visibleCountEl) {
            const visible = rows.filter((row) => !row.classList.contains('bm-menu-hidden')).length;
            visibleCountEl.textContent = visible.toString();
          }
        };

        const collapseBranch = (parentRow) => {
          const parentDepth = parseInt(parentRow.dataset.depth || '0', 10);
          parentRow.setAttribute('aria-expanded', 'false');
          parentRow.classList.add('bm-menu-collapsed');
          rows.forEach((row) => {
            const depth = parseInt(row.dataset.depth || '0', 10);
            if (depth > parentDepth && row.dataset.parentRowId && row.dataset.parentRowId === parentRow.id) {
              hideRow(row);
              collapseBranch(row);
            }
          });
        };

        const expandBranch = (parentRow) => {
          const parentDepth = parseInt(parentRow.dataset.depth || '0', 10);
          parentRow.setAttribute('aria-expanded', 'true');
          parentRow.classList.remove('bm-menu-collapsed');
          rows.forEach((row) => {
            const depth = parseInt(row.dataset.depth || '0', 10);
            if (depth === parentDepth + 1 && row.dataset.parentRowId === parentRow.id) {
              showRow(row);
              if (row.classList.contains('bm-menu-collapsed')) {
                collapseBranch(row);
              }
            }
          });
        };

        const toggleBranch = (row, toggleEl) => {
          if (row.classList.contains('bm-menu-collapsed')) {
            expandBranch(row);
            if (toggleEl) {
              toggleEl.textContent = '−';
            }
          }
          else {
            collapseBranch(row);
            if (toggleEl) {
              toggleEl.textContent = '+';
            }
          }
          updateCounts();
        };

        collapseLevelButtons.forEach((btn) => {
          btn.addEventListener('click', (e) => {
            e.preventDefault();
            const level = parseInt(btn.getAttribute('data-bm-menu-level') || '0', 10);
            rows.forEach((row) => {
              const depth = parseInt(row.dataset.depth || '0', 10);
              if (depth === level && row.dataset.hasChildren === '1') {
                collapseBranch(row);
              }
            });
            updateCounts();
          });
        });

        expandAllBtn?.addEventListener('click', (e) => {
          e.preventDefault();
          rows.forEach((row) => {
            showRow(row);
            row.classList.remove('bm-menu-collapsed');
            row.setAttribute('aria-expanded', 'true');
          });
          table.querySelectorAll('.bm-menu-toggle').forEach((toggle) => {
            toggle.textContent = '−';
          });
          updateCounts();
        });

        const getLabel = (row) => {
          const labelCell = row.querySelector('td:nth-child(3) a, td:nth-child(3)');
          return labelCell ? labelCell.textContent.toLowerCase() : '';
        };

        const applyFilters = () => {
          const q1 = (filterInput?.value || '').trim().toLowerCase();
          const q2 = (filterSecondary?.value || '').trim().toLowerCase();
          if (!q1 && !q2) {
            rows.forEach((row) => showRow(row));
            updateCounts();
            return;
          }
          rows.forEach((row) => hideRow(row));

          rows.forEach((row) => {
            const label = getLabel(row);
            const match = (q1 && label.includes(q1)) || (q2 && label.includes(q2));
            if (match) {
              showRow(row);
              // Show ancestors.
              let ancestorId = row.dataset.parentRowId;
              while (ancestorId) {
                const ancestor = document.getElementById(ancestorId);
                if (!ancestor) break;
                showRow(ancestor);
                ancestorId = ancestor.dataset.parentRowId;
              }
            }
          });
          updateCounts();
        };

        filterInput?.addEventListener('input', applyFilters);
        filterSecondary?.addEventListener('input', applyFilters);
        updateCounts();
      });
    },
  };
})(Drupal, once);
