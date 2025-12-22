((Drupal, drupalSettings, once) => {
  const valueFormatters = {
    date: (params) => {
      if (!params.value) {
        return '';
      }
      const timestamp = Number(params.value);
      if (Number.isNaN(timestamp)) {
        return params.value;
      }
      return new Date(timestamp * 1000).toLocaleString();
    },
  };

  Drupal.behaviors.aggridDisplayInit = {
    attach(context) {
      const settings = drupalSettings.aggridDisplay || {};

      Object.entries(settings).forEach(([gridId, payload]) => {
        const selector = `#aggrid-display-${gridId}`;
        once('aggrid-display', selector, context).forEach((element) => {
          if (typeof agGrid === 'undefined') {
            return;
          }
          const config = payload.config || {};
          const options = config.options || {};

          const columnDefs = (payload.columnDefs || []).map((col) => {
            const newCol = Object.assign({}, col);
            if (newCol.valueFormatter && typeof newCol.valueFormatter === 'string' && valueFormatters[newCol.valueFormatter]) {
              newCol.valueFormatter = valueFormatters[newCol.valueFormatter];
            }
            return newCol;
          });

          const gridOptions = {
            columnDefs,
            rowData: payload.data || [],
            rowSelection: 'single',
            animateRows: true,
            rowHeight: options.row_height || 44,
            pagination: !!options.enable_pagination,
            paginationPageSize: options.pagination_page_size || config.page_size || 50,
            defaultColDef: {
              flex: 1,
              sortable: true,
              resizable: true,
            },
          };

          if (options.theme) {
            element.classList.add(options.theme);
          }

          new agGrid.Grid(element, gridOptions);
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
