{{-- Generic chip tabs: [data-tabs] > [data-tab=key] buttons + [data-panel=key] panels.
     Without JS every panel is shown (with its own heading). --}}
@once
@push('scripts')
<script>
document.querySelectorAll('[data-tabs]').forEach(function (root) {
    var tabs = root.querySelectorAll('[data-tab]');
    var panels = root.querySelectorAll('[data-panel]');
    if (!tabs.length) return;
    root.classList.add('is-js');
    root.querySelectorAll('.chip-row').forEach(function (r) { r.hidden = false; });
    function show(key) {
        tabs.forEach(function (t) { t.classList.toggle('is-active', t.getAttribute('data-tab') === key); });
        panels.forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== key; });
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.getAttribute('data-tab')); }); });
    show(tabs[0].getAttribute('data-tab'));
});
</script>
@endpush
@endonce
