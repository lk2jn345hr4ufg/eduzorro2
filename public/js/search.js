// Live search suggestions for Eduzorro quick-search inputs.
(function () {
    function debounce(fn, wait) {
        var t;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    document.querySelectorAll('.quick-search input[data-suggest-url]').forEach(function (input) {
        var url = input.dataset.suggestUrl;
        var list = input.parentElement.querySelector('.suggestions');
        if (!list) return;

        var items = [];
        var activeIndex = -1;

        function close() {
            list.hidden = true;
            list.innerHTML = '';
            activeIndex = -1;
        }

        function render(results) {
            items = results || [];
            if (!items.length) { close(); return; }
            list.innerHTML = items.map(function (r) {
                return '<li role="option" data-url="' + r.url + '">' +
                    '<span>' + escapeHtml(r.label) + '</span>' +
                    '<span class="s-type">' + escapeHtml(r.type) + '</span></li>';
            }).join('');
            list.hidden = false;
            activeIndex = -1;
            Array.prototype.forEach.call(list.children, function (li) {
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    window.location = li.dataset.url;
                });
            });
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        var run = debounce(function () {
            var q = input.value.trim();
            if (q.length < 2) { close(); return; }
            fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(render)
                .catch(close);
        }, 200);

        input.addEventListener('input', run);

        input.addEventListener('keydown', function (e) {
            if (list.hidden) return;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex += (e.key === 'ArrowDown' ? 1 : -1);
                if (activeIndex < 0) activeIndex = items.length - 1;
                if (activeIndex >= items.length) activeIndex = 0;
                Array.prototype.forEach.call(list.children, function (li, i) {
                    li.classList.toggle('active', i === activeIndex);
                });
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                window.location = list.children[activeIndex].dataset.url;
            } else if (e.key === 'Escape') {
                close();
            }
        });

        document.addEventListener('click', function (e) {
            if (!input.parentElement.contains(e.target)) close();
        });
    });
})();
