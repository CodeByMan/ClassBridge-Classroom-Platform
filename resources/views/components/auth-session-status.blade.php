@props(['status'])
@if ($status)
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800']) }}>{{ $status }}</div>
@endif
