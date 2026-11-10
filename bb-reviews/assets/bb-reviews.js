/* BB Reviews: „Mehr lesen“ und Karussell ohne Abhängigkeiten. */
(function () {
  'use strict';

  function initMore(root) {
    root.querySelectorAll('.bbr-text.is-clamped').forEach(function (text) {
      var button = text.nextElementSibling;
      if (!button || !button.classList.contains('bbr-more')) { return; }
      // Knopf nur zeigen, wenn wirklich Text abgeschnitten ist.
      if (text.scrollHeight - text.clientHeight > 2) { button.hidden = false; }
      button.addEventListener('click', function () {
        var open = text.classList.toggle('is-clamped') === false;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.textContent = open ? 'Weniger' : 'Mehr lesen';
      });
    });
  }

  function initCarousel(root) {
    var track = root.querySelector('.bbr-track');
    if (!track) { return; }
    var prev = root.querySelector('.bbr-prev');
    var next = root.querySelector('.bbr-next');
    var seconds = parseInt(root.getAttribute('data-autoplay') || '0', 10);
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var timer = null;
    var paused = false;

    function step() {
      var card = track.querySelector('.bbr-card');
      if (!card) { return 0; }
      var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
      return card.getBoundingClientRect().width + gap;
    }
    function atEnd() { return track.scrollLeft + track.clientWidth >= track.scrollWidth - 4; }
    function update() {
      var scrollable = track.scrollWidth - track.clientWidth > 4;
      if (prev) { prev.disabled = !scrollable || track.scrollLeft <= 4; }
      if (next) { next.disabled = !scrollable || atEnd(); }
      root.classList.toggle('bbr--static', !scrollable);
    }
    function go(direction) {
      if (direction > 0 && atEnd()) { track.scrollTo({ left: 0 }); return; }
      track.scrollBy({ left: direction * step() });
    }

    if (prev) { prev.addEventListener('click', function () { go(-1); }); }
    if (next) { next.addEventListener('click', function () { go(1); }); }
    track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
    window.addEventListener('resize', update);
    update();

    if (seconds > 0 && !reduced) {
      ['mouseenter', 'focusin', 'touchstart'].forEach(function (name) {
        root.addEventListener(name, function () { paused = true; }, { passive: true });
      });
      ['mouseleave', 'focusout'].forEach(function (name) {
        root.addEventListener(name, function () { paused = false; });
      });
      timer = window.setInterval(function () {
        if (paused || document.hidden) { return; }
        if (track.scrollWidth - track.clientWidth <= 4) { return; }
        go(1);
      }, seconds * 1000);
    }
  }

  function init() {
    document.querySelectorAll('.bbr').forEach(function (root) {
      if (root.getAttribute('data-bbr-ready')) { return; }
      root.setAttribute('data-bbr-ready', '1');
      initMore(root);
      if (root.classList.contains('bbr--carousel')) { initCarousel(root); }
    });
  }

  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
