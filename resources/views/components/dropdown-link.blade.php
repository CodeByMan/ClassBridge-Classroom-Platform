@props(['as' => 'a', 'type' => 'button'])
@php
    $classes = 'block w-full px-4 py-2.5 text-left text-sm leading-5 text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out';
@endphp
@if ($as === 'button')
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@else
    <a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@endif
