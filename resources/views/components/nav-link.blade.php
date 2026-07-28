@props(['active'])
@php $classes = ($active ?? false) ? 'gc-tab is-active' : 'gc-tab'; @endphp
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
