// bm_core/js/theme-switcher.js
(function (Drupal, once) {
  Drupal.behaviors.bmThemeSwitcher = {
    attach(context) {
      once('bm-theme-switcher', '[data-bm-theme-switcher]', context).forEach(wrapper => {
        wrapper.querySelectorAll('[data-theme]').forEach(button => {
          button.addEventListener('click', () => {
            const theme = button.dataset.theme;

            if (theme === 'system') {
              document.documentElement.removeAttribute('data-theme');
              localStorage.removeItem('bm-theme');
            } else {
              document.documentElement.setAttribute('data-theme', theme);
              localStorage.setItem('bm-theme', theme);
            }
          });
        });
      });

      const savedTheme = localStorage.getItem('bm-theme');
      if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
      }
    }
  };
})(Drupal, once);
