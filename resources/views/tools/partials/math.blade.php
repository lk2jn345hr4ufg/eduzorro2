@php
    /** @var \App\Models\Tool $tool */
    $cfg = config('math_tools.'.$tool->slug);
@endphp

@if (! $cfg)
    <p class="notice">—</p>
@else
    <div class="tool-panel math-tool" id="math-tool">
        <div class="math-fields">
            @foreach ($cfg['fields'] as $f)
                @php($type = $f['type'] ?? 'number')
                <div class="tool-field">
                    <label for="mf-{{ $f['id'] }}">{{ __('math.'.$f['label']) }}</label>

                    @if ($type === 'select')
                        <select class="inp math-in" id="mf-{{ $f['id'] }}" data-id="{{ $f['id'] }}" data-type="text">
                            @foreach ($f['options'] as $val => $text)
                                <option value="{{ $val }}" @selected((string) $val === (string) ($f['default'] ?? ''))>{{ $text }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'text')
                        <input class="inp math-in" id="mf-{{ $f['id'] }}" data-id="{{ $f['id'] }}" data-type="text"
                               type="text" value="{{ $f['default'] ?? '' }}">
                    @else
                        <input class="inp math-in" id="mf-{{ $f['id'] }}" data-id="{{ $f['id'] }}" data-type="number"
                               type="number" step="any" value="{{ $f['default'] ?? '' }}">
                    @endif
                </div>
            @endforeach
        </div>

        <table class="tool-table tool-table-result math-results">
            <tbody>
                @foreach ($cfg['outputs'] as $o)
                    <tr>
                        <td>{{ __('math.'.$o['label']) }}</td>
                        <td><strong class="math-out" data-id="{{ $o['id'] }}">—</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
    (function () {
        var root = document.getElementById('math-tool');
        if (!root) return;

        var ids = @json(array_column($cfg['fields'], 'id'));
        // Outputs flagged as text are printed verbatim; numbers get formatted.
        var textOut = @json(array_values(array_map(
            fn ($o) => $o['id'],
            array_filter($cfg['outputs'], fn ($o) => ! empty($o['text']))
        )));

        // Formula body from config, compiled once.
        var fn;
        try {
            fn = new Function(ids.join(','), @js($cfg['js']));
        } catch (e) {
            return;
        }

        var inputs = root.querySelectorAll('.math-in');
        var outputs = root.querySelectorAll('.math-out');

        function fmt(v) {
            if (v === null || v === undefined || v === '' || (typeof v === 'number' && !isFinite(v))) return '—';
            if (typeof v !== 'number') return String(v);
            // Trim floating point noise, keep big/small numbers readable.
            var r = Math.round(v * 1e6) / 1e6;
            if (Math.abs(r) !== 0 && (Math.abs(r) >= 1e12 || Math.abs(r) < 1e-6)) return r.toExponential(4);
            return String(r);
        }

        function calc() {
            var args = [];

            for (var i = 0; i < inputs.length; i++) {
                var el = inputs[i];
                args.push(el.dataset.type === 'number' ? parseFloat(el.value) : el.value);
            }

            var res;
            try {
                res = fn.apply(null, args) || {};
            } catch (e) {
                res = {};
            }

            for (var j = 0; j < outputs.length; j++) {
                var out = outputs[j];
                var key = out.dataset.id;
                var val = res[key];
                out.textContent = textOut.indexOf(key) !== -1
                    ? (val === null || val === undefined || val === '' ? '—' : String(val))
                    : fmt(val);
            }
        }

        for (var k = 0; k < inputs.length; k++) {
            inputs[k].addEventListener('input', calc);
            inputs[k].addEventListener('change', calc);
        }

        calc();
    })();
    </script>
@endif
