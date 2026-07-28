@props(['messages'])
@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-sm text-red-600 mt-1']) }}>
        @foreach ((array) $messages as $message)<li>{{ $message }}</li>@endforeach
    </ul>
@endif
