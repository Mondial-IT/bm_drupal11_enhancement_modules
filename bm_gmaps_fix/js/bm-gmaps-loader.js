// modules/custom/bm_gmaps_fix/js/gmaps-loader.js
(function () {
  document.addEventListener('DOMContentLoaded', function () {

    console.log("bm_gmaps_fix loader running");

    var s = (window.drupalSettings && drupalSettings.bmGmapsFix) || {};

    if (!s.apiKey){
      console.warn("return No google apiKey was passed: ",s);
      return;
    }

    if (document.querySelector('script[data-bm-gmaps-fix]')){
      console.log("return found script[data-bm-gmaps-fix]")
      return;
    }

    var el = document.createElement('script');
    el.src =
      'https://maps.googleapis.com/maps/api/js?key=' +
      encodeURIComponent(s.apiKey) +
      '&v=quarterly&libraries=marker';
    el.defer = true;
    el.setAttribute('data-bm-gmaps-fix', '1');
    document.head.appendChild(el);
  });

})();
