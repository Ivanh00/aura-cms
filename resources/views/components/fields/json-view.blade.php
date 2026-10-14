<x-aura::fields.wrapper :field="$field">
    @php
        $value = $this->model->display($field['slug']);
    @endphp

    @if($value === null || $value === '')
        <span class="text-gray-400">–</span>
    @else
        <pre class="overflow-x-auto whitespace-pre-wrap break-words font-mono text-sm leading-6 text-gray-700 dark:text-gray-200">{!! $value !!}</pre>
    @endif
</x-aura::fields.wrapper>
