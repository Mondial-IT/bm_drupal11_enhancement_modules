// js/override.js
console.log('Mondial-IT views_currentPage_override: override views UI is running');
(function (Drupal, once) {
  Drupal.behaviors.bluemarlocOverride = {
    attach: function (context) {
      once('viewsCurrentPageOverride', 'select[name="override[dropdown]"]', context).forEach(function (el) {
        for (let i = 0; i < el.options.length; i++) {
          let label = el.options[i].text.toLowerCase();
          if (label.includes('override') && !label.includes('all displays')) {
            el.selectedIndex = i;
            break;
          }
        }
      });
    }
  };
})(Drupal, once);
