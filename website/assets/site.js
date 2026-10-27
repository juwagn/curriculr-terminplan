// Curricu:lr Terminplan Website: kleine Hilfen, keine Abhängigkeiten.
(function () {
  'use strict';

  // Druck: alle FAQ-Antworten aufklappen und danach den Zustand wiederherstellen.
  var opened = [];
  function openAll() {
    opened = [];
    document.querySelectorAll('details').forEach(function (d) {
      if (!d.open) { d.open = true; opened.push(d); }
    });
  }
  function restore() {
    opened.forEach(function (d) { d.open = false; });
    opened = [];
  }
  window.addEventListener('beforeprint', openAll);
  window.addEventListener('afterprint', restore);

  // Download-Seite: Zustimmung vor dem Download.
  var box = document.getElementById('disclaimer-cb');
  var btn = document.getElementById('dl-btn');
  if (box && btn) {
    var sync = function () {
      if (box.checked) {
        btn.removeAttribute('aria-disabled');
        btn.setAttribute('aria-label', 'Plugin herunterladen (ZIP)');
      } else {
        btn.setAttribute('aria-disabled', 'true');
        btn.setAttribute('aria-label', 'Plugin herunterladen (erst nach Zustimmung verfügbar)');
      }
    };
    box.addEventListener('change', sync);
    btn.addEventListener('click', function (e) {
      if (btn.getAttribute('aria-disabled') === 'true') {
        e.preventDefault();
        box.focus();
      }
    });
    sync();
  }
})();
