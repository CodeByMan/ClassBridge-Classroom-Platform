@props(['active'])
@php $classes = ($active ?? false) ? 'gc-nav-item is-active' : 'gc-nav-item'; @endphp
<a {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
