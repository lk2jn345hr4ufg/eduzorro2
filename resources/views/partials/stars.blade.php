{{--
    Expects $rating (float) and optional $count (int).
    When $count is given, renders the signature "rating stamp" (aggregate use:
    company cards, profile header). When $count is omitted, renders a compact
    star row (single-review use).
--}}
@php
    $rating   = round((float) ($rating ?? 0), 1);
    $hasCount = isset($count);
@endphp

@if ($hasCount)
    <span class="rating-stamp" role="img" aria-label="{{ $rating }} out of 5, {{ $count }} reviews">
        <span class="rating-stamp-ring">
            <span class="rating-stamp-value">{{ $rating > 0 ? number_format($rating, 1) : '&mdash;' }}</span>
        </span>
        <span class="rating-stamp-count">{{ $count }} {{ __('messages.reviews') }}</span>
    </span>
@else
    @php
        $full = (int) floor($rating);
        $half = ($rating - $full) >= 0.5;
    @endphp
    <span class="stars" title="{{ $rating }} / 5" aria-label="{{ $rating }} / 5">
        @for ($i = 1; $i <= 5; $i++)
            @if ($i <= $full)
                <span class="star full">&#9733;</span>
            @elseif ($i === $full + 1 && $half)
                <span class="star half">&#9733;</span>
            @else
                <span class="star empty">&#9734;</span>
            @endif
        @endfor
    </span>
@endif
