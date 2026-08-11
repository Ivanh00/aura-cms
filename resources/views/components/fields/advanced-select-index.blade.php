@php
    $slug = $field['slug'];
    $fieldItems = collect();

    // Reuse the relation eager-loaded by the table when available, otherwise
    // fall back to a scoped id lookup.
    if ($row instanceof \Illuminate\Database\Eloquent\Model && $row->relationLoaded($slug)) {
        $fieldItems = $row->getRelation($slug);
    } elseif (!empty($field['resource'])) {
        $fieldItemIds = $row->fields[$slug] ?? [];

        if (!is_array($fieldItemIds)) {
            $fieldItemIds = [$fieldItemIds];
        }

        $resourceClass = $field['resource'];
        $fieldItems = $resourceClass::whereIn('id', $fieldItemIds)->get();
    }
@endphp

<div class="flex space-x-2">
    @foreach($fieldItems as $item)
        @if(isset($field['view_index']))
            @include($field['view_index'], ['item' => $item])
        @else
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
            <div class="flex items-center px-1 py-1 bg-gray-100 rounded-full">
                @if($thumbnailField)
                    @if($thumbnailId)
                        <x-aura::image :id="$thumbnailId" alt="{{ $item->title() }}" class="thumbnail-image object-cover w-6 h-6 rounded-full shrink-0" />
                    @else
                        <div class="flex justify-center items-center w-6 h-6 bg-gray-300 rounded-full">
                        </div>
                    @endif
                @endif
                <span class="px-2 text-sm font-medium text-gray-800 truncate">{{ $item->title() }}</span>
            </div>
        @endif
    @endforeach
</div>
