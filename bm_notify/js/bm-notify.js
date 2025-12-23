(function (Drupal) {

  Drupal.AjaxCommands.prototype.bmNotify = function (ajax, response) {
    const opts = response.payload || {};
    const toast = document.createElement('div');

    toast.className = 'bm-notify bm-notify-' + (opts.type || 'info');
    toast.textContent = opts.message;

    document.body.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('visible'));

    setTimeout(() => {
      toast.classList.remove('visible');
      setTimeout(() => toast.remove(), 300);
    }, opts.timeout || 4000);
  };

})(Drupal);
