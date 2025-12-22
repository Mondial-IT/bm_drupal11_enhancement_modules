((Drupal, drupalSettings) => {
  Drupal.aggridAjax = {
    saveCell(endpoint, payload) {
      return fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': drupalSettings.aggridDisplay?.csrfToken || '',
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin',
      })
        .then((response) => response.json());
    },
  };
})(Drupal, drupalSettings);
