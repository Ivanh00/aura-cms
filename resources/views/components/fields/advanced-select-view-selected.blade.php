@php
    $thumbnailField = $field['thumbnail'] ?? null;
    $thumbnailValue = ($thumbnailField && $thumbnailField !== '')
        ? ($item->{$thumbnailField} ?? null)
        : null;

    if (is_array($thumbnailValue)) {
        $thumbnailId = $thumbnailValue[0] ?? null;
    } elseif (is_object($thumbnailValue) && method_exists($thumbnailValue, 'first')) {
        $first = $thumbnailValue->first();
        $thumbnailId = is_object($first) ? ($first->id ?? $first->getKey() ?? null) : $first;
    } else {
        $thumbnailId = $thumbnailValue;
    }
@endphp

<div class="flex items-center space-x-2 advanced-select-view-selected">
    @if($thumbnailField)
        @if($thumbnailId)
            <x-aura::image :id="$thumbnailId" alt="{{ $item->title() }}" class="object-cover w-6 h-6 rounded-full shrink-0" />
        @else
            <div class="flex justify-center items-center w-6 h-6 bg-gray-300 rounded-full">
            </div>
        @endif
    @endif
    <span class="font-medium text-gray-800">{{ $item->title() }}</span>
</div>
