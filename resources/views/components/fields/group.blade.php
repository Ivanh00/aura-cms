@php
$slug = $field['slug'];
if (!isset($this->form['fields'][$slug])) {
  $this->form['fields'][$slug] = [];
}

$model = $this->model;
@endphp

<style >
  #resource-field-{{ Str::slug(optional($field)['slug']) }}-wrapper {
    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
  }

  @media screen and (max-width: 768px) {
    #resource-field-{{ Str::slug(optional($field)['slug']) }}-wrapper {
      width: 100%;
    }
  }
</style>


<div class="px-2" id="resource-field-{{ Str::slug(optional($field)['slug']) }}-wrapper">
  <div @class([
    'p-0' => ($field['variant'] ?? null) !== 'section',
    'p-4 mb-6 rounded-xl border border-gray-200/80 bg-gray-50/60 dark:border-gray-700/80 dark:bg-gray-900/40' => ($field['variant'] ?? null) === 'section',
  ])>
    @if(($field['variant'] ?? null) === 'section')
      <div class="px-2 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __($field['name']) }}</h3>
        @if(filled($field['description'] ?? null))
          <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ __($field['description']) }}</p>
        @endif
      </div>
    @endif
    <div class="flex flex-wrap items-start -mx-2">
      @if(optional($field)['fields'])
      @foreach($field['fields'] as $key => $field)
          @checkCondition($this->model, $field, $this->form)
            <x-dynamic-component :component="$field['field']->edit()" mode="edit" :field="$field" :form="$form" />
          @endcheckCondition
        @endforeach
        @else
        <span>{{ $field['name'] }}</span>
        @endif
      </div>
    </div>
  </div>
