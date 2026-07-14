(function () {
    'use strict';

    var toggle = document.querySelector('[data-category-tree-toggle]');
    if (!toggle) {
        return;
    }

    toggle.addEventListener('click', function () {
        var expanded = toggle.getAttribute('aria-expanded') === 'true';

        document.querySelectorAll('[data-category-children]').forEach(function (children) {
            children.hidden = expanded;
        });

        toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        toggle.textContent = expanded ? 'Expand children' : 'Collapse children';
    });
}());
