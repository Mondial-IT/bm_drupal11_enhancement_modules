// modules/custom/bm_gmaps_fix/js/gmaps-fix.js
(function(){
  // If the map is inside a hidden container at first paint, force a resize after it becomes visible.

  console.log('bm_gmaps_fix running');

  function visible(el){
    console.log('visible?');
    if (!el) return false;
    var r = el.getBoundingClientRect();
    return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden';
  }

  function ensureResize(){
    console.log('ensureResize');
    if (!window.google || !google.maps) return;
    setTimeout(function(){ window.dispatchEvent(new Event('resize')); }, 200);
  }

  function initWhenVisible(selector, cb){
    console.log('initWhenVisible');
    var el = document.querySelector(selector);
    if (!el) return;
    if (visible(el)) return cb(el);
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        if (e.isIntersecting) { cb(el); io.disconnect(); }
      });
    }, { root: null, threshold: 0.01 });
    io.observe(el);
  }

  document.addEventListener('DOMContentLoaded', function(){
    // Common Gavias/Ziston map wrappers (adjust if needed).
    console.log('DomContentLoaded');
    initWhenVisible('.gva-google-map, #ziston-map, .map-container', ensureResize);
  });
})();
