(function (Drupal, $, once) {
  Drupal.behaviors.tableClickBehavior = {
    attach: function (context) {
      once('tableClickBehavior', '.table-row', context).forEach(function (row) {

        $(row).on('click', function () {

          const table = $(this).data('table');

          Drupal.ajax({
            url: Drupal.url('admin-reports/get-table-fields'),
            dialogType: 'off_canvas',
            dialog: { title: 'Fields of ' + table, width: '400px' },
            progress: { type: 'fullscreen' },
            submit: { table }
          }).execute();

        });

      });
    }
  };
})(Drupal, jQuery, once);
