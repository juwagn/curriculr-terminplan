/* global gshDeleteWarn */
(function () {
    var config = window.gshDeleteWarn || {};
    var backupUrl = config.backupUrl || '';
    var pluginSlug = 'gsh-terminplan';

    function findDeleteLink() {
        var row = document.querySelector('tr[data-slug="' + pluginSlug + '"]');
        if (!row) { return null; }
        var links = row.querySelectorAll('.delete a, .deactivate + span a, td.plugin-title + td a');
        for (var i = 0; i < row.querySelectorAll('a').length; i++) {
            var a = row.querySelectorAll('a')[i];
            if (a.href && a.href.indexOf('action=delete-plugin') !== -1) {
                return a;
            }
        }
        return null;
    }

    function buildModal(deleteHref) {
        var overlay = document.createElement('div');
        overlay.id = 'gsh-delete-warn-overlay';
        overlay.innerHTML =
            '<div id="gsh-delete-warn-box">' +
            '<h2>Plugin löschen</h2>' +
            '<p>Vor dem Löschen Einstellungen exportieren.<br>' +
            'Curriculr-Planungsdokumente bleiben erhalten.</p>' +
            '<div class="gsh-delete-warn-actions">' +
            (backupUrl ? '<a href="' + backupUrl + '" target="_blank" class="button button-primary">Einstellungen exportieren</a> ' : '') +
            '<a href="' + deleteHref + '" class="button gsh-delete-warn-confirm">Trotzdem löschen</a> ' +
            '<button type="button" class="button gsh-delete-warn-cancel">Abbrechen</button>' +
            '</div>' +
            '</div>';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100000;display:flex;align-items:center;justify-content:center';
        var box = overlay.querySelector('#gsh-delete-warn-box');
        box.style.cssText = 'background:#fff;padding:24px 28px;border-radius:4px;max-width:420px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,.25)';
        overlay.querySelector('.gsh-delete-warn-cancel').addEventListener('click', function () {
            document.body.removeChild(overlay);
        });
        return overlay;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var deleteLink = findDeleteLink();
        if (!deleteLink) { return; }
        deleteLink.addEventListener('click', function (e) {
            e.preventDefault();
            document.body.appendChild(buildModal(deleteLink.href));
        });
    });
}());
