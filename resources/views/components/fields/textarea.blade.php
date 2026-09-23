<x-aura::fields.wrapper :field="$field">
    <textarea wire:model="form.fields.{{ optional($field)['slug'] }}" error="form.fields.{{ optional($field)['slug'] }}" rows="{{ $field['rows'] ?? '4' }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" class="block p-3 w-full rounded-md shadow-sm border-gray-500/30 focus:border-primary-300 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:border-gray-700 dark:bg-gray-900 disabled:cursor-not-allowed disabled:opacity-75 disabled:bg-gray-100 disabled:dark:bg-gray-800" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}" @disabled($field['field']->isDisabled($form, $field))></textarea>
</x-aura::fields.wrapper>


{{-- border-gray-500/30 focus:border-primary-300 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 rounded-md shadow-sm --}}
