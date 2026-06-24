/* global gshDeleteWarn */
(function () {
    var config = window.gshDeleteWarn || {};
    var backupUrl = config.backupUrl || '';
    var pluginSlug = 'gsh-terminplan';

    function findDeleteLink() {
        var row = document.querySelector('tr[data-slug="' + pluginSlug + '"]');
        if (!row) { return null; }
        var links = row.querySelectorAll('a');
        for (var i = 0; i < links.length; i++) {
            var a = links[i];
            if (a.href && a.href.indexOf('action=delete-plugin') !== -1) {
                return a;
            }
        }
        return null;
    }

    function buildModal(deleteHref) {
        var overlay = document.createElement('div');
        overlay.id = 'gsh-delete-warn-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100000;display:flex;align-items:center;justify-content:center';

        var box = document.createElement('div');
        box.id = 'gsh-delete-warn-box';
        box.style.cssText = 'background:#fff;padding:24px 28px;border-radius:4px;max-width:420px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,.25)';

        var heading = document.createElement('h2');
        heading.textContent = 'Plugin löschen';

        var body = document.createElement('p');
        body.innerHTML = 'Vor dem Löschen Einstellungen exportieren.<br>Curriculr-Planungsdokumente bleiben erhalten.';

        var actions = document.createElement('div');
        actions.className = 'gsh-delete-warn-actions';

        if (backupUrl) {
            var exportLink = document.createElement('a');
            exportLink.href = backupUrl;
            exportLink.target = '_blank';
            exportLink.className = 'button button-primary';
            exportLink.textContent = 'Einstellungen exportieren';
            actions.appendChild(exportLink);
            actions.appendChild(document.createTextNode(' '));
        }

        var confirmLink = document.createElement('a');
        confirmLink.href = deleteHref;
        confirmLink.className = 'button gsh-delete-warn-confirm';
        confirmLink.textContent = 'Trotzdem löschen';
        actions.appendChild(confirmLink);
        actions.appendChild(document.createTextNode(' '));

        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'button gsh-delete-warn-cancel';
        cancelBtn.textContent = 'Abbrechen';
        cancelBtn.addEventListener('click', function () {
            document.body.removeChild(overlay);
        });
        actions.appendChild(cancelBtn);

        box.appendChild(heading);
        box.appendChild(body);
        box.appendChild(actions);
        overlay.appendChild(box);
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
