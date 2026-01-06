(function (Drupal, once) {
  Drupal.behaviors.bmTooltipDemoMode = {
    attach(context) {
      const wrapper = once('bm-tooltip-demo-mode', '#bm-tooltip-themes', context);
      if (!wrapper.length) {
        return;
      }
      const el = wrapper[0];
      const checkbox = el.querySelector('.bm-tooltip-mode-toggle input[type="checkbox"]');
      const body = document.body;
      const setClass = () => {
        if (!checkbox) {
          return;
        }
        if (checkbox.checked) {
          el.classList.add('bm-tooltip-demo--dark');
          body.classList.add('bm-tooltip-demo-dark-mode');
        }
        else {
          el.classList.remove('bm-tooltip-demo--dark');
          body.classList.remove('bm-tooltip-demo-dark-mode');
        }
      };
      checkbox?.addEventListener('change', setClass);
      setClass();
    },
  };
})(Drupal, once);
