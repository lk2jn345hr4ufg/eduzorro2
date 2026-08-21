<p class="tool-hint">{{ __('tools.converter_hint') }}</p>

<div class="tool-panel" id="grade-tool">
    <div class="tool-field">
        <label for="gc-percent">{{ __('tools.enter_percent') }}</label>
        <input type="number" id="gc-percent" class="inp inp-lg" min="0" max="100" step="1" value="85">
    </div>

    <table class="tool-table tool-table-result">
        <thead>
            <tr>
                <th>{{ __('tools.system') }}</th>
                <th>{{ __('tools.result') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>{{ __('tools.us_letter') }}</td><td><strong id="gc-us">—</strong></td></tr>
            <tr><td>{{ __('tools.us_gpa') }}</td><td><strong id="gc-gpa">—</strong></td></tr>
            <tr><td>{{ __('tools.uk_class') }}</td><td><strong id="gc-uk">—</strong></td></tr>
            <tr><td>{{ __('tools.ects') }}</td><td><strong id="gc-ects">—</strong></td></tr>
            <tr><td>{{ __('tools.ua_12') }}</td><td><strong id="gc-ua">—</strong></td></tr>
            <tr><td>{{ __('tools.five_point') }}</td><td><strong id="gc-five">—</strong></td></tr>
        </tbody>
    </table>
</div>

<script>
(function () {
    var input = document.getElementById('gc-percent');

    // Indicative mappings; institutions vary, hence the note above the tool.
    function usLetter(p) {
        if (p >= 93) return 'A';
        if (p >= 90) return 'A-';
        if (p >= 87) return 'B+';
        if (p >= 83) return 'B';
        if (p >= 80) return 'B-';
        if (p >= 77) return 'C+';
        if (p >= 73) return 'C';
        if (p >= 70) return 'C-';
        if (p >= 67) return 'D+';
        if (p >= 60) return 'D';
        return 'F';
    }

    var GPA = { 'A': '4.0', 'A-': '3.7', 'B+': '3.3', 'B': '3.0', 'B-': '2.7',
                'C+': '2.3', 'C': '2.0', 'C-': '1.7', 'D+': '1.3', 'D': '1.0', 'F': '0.0' };

    function ukClass(p) {
        if (p >= 70) return 'First (1st)';
        if (p >= 60) return 'Upper second (2:1)';
        if (p >= 50) return 'Lower second (2:2)';
        if (p >= 40) return 'Third (3rd)';
        return 'Fail';
    }

    function ects(p) {
        if (p >= 90) return 'A';
        if (p >= 80) return 'B';
        if (p >= 70) return 'C';
        if (p >= 60) return 'D';
        if (p >= 50) return 'E';
        return 'F';
    }

    function ua12(p) {
        if (p >= 95) return '12';
        if (p >= 90) return '11';
        if (p >= 85) return '10';
        if (p >= 80) return '9';
        if (p >= 75) return '8';
        if (p >= 70) return '7';
        if (p >= 65) return '6';
        if (p >= 60) return '5';
        if (p >= 50) return '4';
        if (p >= 40) return '3';
        if (p >= 30) return '2';
        return '1';
    }

    function five(p) {
        if (p >= 90) return '5';
        if (p >= 75) return '4';
        if (p >= 60) return '3';
        return '2';
    }

    function set(id, v) { document.getElementById(id).textContent = v; }

    function update() {
        var p = parseFloat(input.value);

        if (isNaN(p) || p < 0 || p > 100) {
            ['gc-us', 'gc-gpa', 'gc-uk', 'gc-ects', 'gc-ua', 'gc-five'].forEach(function (id) { set(id, '—'); });
            return;
        }

        var letter = usLetter(p);
        set('gc-us', letter);
        set('gc-gpa', GPA[letter]);
        set('gc-uk', ukClass(p));
        set('gc-ects', ects(p));
        set('gc-ua', ua12(p));
        set('gc-five', five(p));
    }

    input.addEventListener('input', update);
    update();
})();
</script>
