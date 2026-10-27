<x-aura::fields.wrapper :field="$field">
    @php
        // Read the field value directly: the generic display() path joins
        // arrays into a comma separated string before it reaches this view.
        $value = $this->model->fields[$field['slug']] ?? null;
        $json = $value === null || $value === '' ? null : $field['field']->display($field, $value, $this->model);
    @endphp

    @if($json === null || $json === '')
        <span class="text-gray-400">–</span>
    @else
        <pre style="max-height: 36rem; white-space: pre-wrap; word-break: break-word;" class="overflow-auto p-4 font-mono text-sm leading-6 text-gray-700 bg-gray-50 rounded-lg border border-gray-500/30 dark:bg-gray-900 dark:text-gray-200">{!! \Aura\Base\Fields\Json::highlight($json) !!}</pre>
    @endif
</x-aura::fields.wrapper>
