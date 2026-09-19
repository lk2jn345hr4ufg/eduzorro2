@extends('layouts.admin')
@section('title', 'SEO / Мета-теги')

@section('content')
    <h1>SEO — мета-теги разделов</h1>
    <p class="muted" style="margin-top:-10px;margin-bottom:18px">
        Заголовок (title) и описание (description) для каждой страницы.
        Оставьте поле пустым — подставится значение по умолчанию.
        На страницах расписания и таблицы к описанию автоматически добавляется
        динамика (ближайший матч, место «Ливерпуля»).
    </p>

    <form method="post" action="{{ route('admin.seo.update') }}">
        @csrf @method('PUT')

        @foreach($sections as $key => $s)
            <div class="card">
                <h3>{{ $s['label'] }}</h3>

                <label>Заголовок (title)</label>
                <input name="title[{{ $key }}]" value="{{ old("title.$key", $s['title']) }}"
                       maxlength="120" placeholder="{{ $s['defaultTitle'] }}">
                <p class="muted" style="margin:4px 0 0">По умолчанию: {{ $s['defaultTitle'] }}</p>

                <label style="margin-top:12px">Описание (description)</label>
                <textarea name="description[{{ $key }}]" style="min-height:70px" maxlength="300"
                          placeholder="{{ $s['defaultDesc'] }}">{{ old("description.$key", $s['description']) }}</textarea>
                <p class="muted" style="margin:4px 0 0">По умолчанию: {{ $s['defaultDesc'] }}</p>
            </div>
        @endforeach

        <button class="btn primary">Сохранить</button>
    </form>
@endsection
