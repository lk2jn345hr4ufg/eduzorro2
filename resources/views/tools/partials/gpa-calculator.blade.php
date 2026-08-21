<p class="tool-hint">{{ __('tools.gpa_hint') }}</p>

<div class="tool-panel" id="gpa-tool">
    <table class="tool-table">
        <thead>
            <tr>
                <th>{{ __('tools.course') }}</th>
                <th class="w-grade">{{ __('tools.grade') }}</th>
                <th class="w-credits">{{ __('tools.credits') }}</th>
                <th aria-label="{{ __('tools.remove') }}"></th>
            </tr>
        </thead>
        <tbody id="gpa-rows"></tbody>
    </table>

    <div class="tool-actions">
        <button type="button" class="btn" id="gpa-add">+ {{ __('tools.add_course') }}</button>
        <button type="button" class="btn btn-ghost" id="gpa-reset">{{ __('tools.reset') }}</button>
    </div>

    <div class="tool-result">
        <div class="tool-result-main">
            <span class="tool-result-label">{{ __('tools.your_gpa') }}</span>
            <span class="tool-result-value" id="gpa-value">—</span>
        </div>
        <div class="tool-result-sub">
            {{ __('tools.total_credits') }}: <strong id="gpa-credits">0</strong>
        </div>
    </div>
</div>

<script>
(function () {
    // US 4.0 scale points per letter grade.
    var GRADES = [
        ['A',  4.0], ['A-', 3.7], ['B+', 3.3], ['B', 3.0], ['B-', 2.7],
        ['C+', 2.3], ['C',  2.0], ['C-', 1.7], ['D+', 1.3], ['D', 1.0], ['F', 0.0]
    ];

    var rows   = document.getElementById('gpa-rows');
    var addBtn = document.getElementById('gpa-add');
    var resetBtn = document.getElementById('gpa-reset');
    var out    = document.getElementById('gpa-value');
    var outCr  = document.getElementById('gpa-credits');

    function options() {
        return GRADES.map(function (g) {
            return '<option value="' + g[1] + '">' + g[0] + '</option>';
        }).join('');
    }

    function addRow(name, gradeValue, credits) {
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" class="inp" placeholder="{{ __('tools.course_name') }}" value="' + (name || '') + '"></td>' +
            '<td><select class="inp gpa-grade">' + options() + '</select></td>' +
            '<td><input type="number" class="inp gpa-credits" min="0" step="0.5" value="' + (credits || 3) + '"></td>' +
            '<td><button type="button" class="btn-icon" aria-label="{{ __('tools.remove') }}">×</button></td>';
        rows.appendChild(tr);

        if (gradeValue != null) {
            tr.querySelector('.gpa-grade').value = gradeValue;
        }

        tr.querySelector('.btn-icon').addEventListener('click', function () {
            tr.remove();
            if (!rows.children.length) addRow();
            calc();
        });
        tr.addEventListener('input', calc);
        tr.addEventListener('change', calc);
    }

    function calc() {
        var points = 0, credits = 0;

        Array.prototype.forEach.call(rows.querySelectorAll('tr'), function (tr) {
            var g = parseFloat(tr.querySelector('.gpa-grade').value);
            var c = parseFloat(tr.querySelector('.gpa-credits').value);
            if (isNaN(g) || isNaN(c) || c <= 0) return;
            points  += g * c;
            credits += c;
        });

        outCr.textContent = credits ? (Math.round(credits * 100) / 100) : 0;
        out.textContent   = credits ? (points / credits).toFixed(2) : '—';
    }

    addBtn.addEventListener('click', function () { addRow(); calc(); });
    resetBtn.addEventListener('click', function () {
        rows.innerHTML = '';
        addRow(); addRow(); addRow();
        calc();
    });

    addRow(); addRow(); addRow();
    calc();
})();
</script>
