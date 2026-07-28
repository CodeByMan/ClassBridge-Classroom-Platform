@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])
@php
    $alignmentClasses = match ($align) {
        'left' => 'origin-top-left left-0',
        'top' => 'origin-top',
        default => 'origin-top-right right-0',
    };
    $widthClass = match ((string) $width) {
        '24' => 'w-24',
        '32' => 'w-32',
        '40' => 'w-40',
        default => 'w-56',
    };
@endphp

<div class="relative gc-dropdown" data-dropdown>
    <div data-dropdown-trigger aria-expanded="false">
        {{ $trigger }}
    </div>

    <div data-dropdown-menu hidden
        class="absolute z-[75] mt-2 {{ $widthClass }} rounded-lg shadow-xl {{ $alignmentClasses }}">
        <div class="rounded-lg border border-gray-200 overflow-hidden {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
