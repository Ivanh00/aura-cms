<?php

namespace Aura\Base\Traits;

trait InteractsWithFields
{
    public function getCreateFieldsProperty()
    {
        return $this->model->createFields();
    }

    public function getEditFieldsProperty()
    {
        return $this->model->editFields();
    }

    public function getFieldsProperty()
    {
        $fields = $this->model->mappedFields();

        return $this->model->fieldsForView($fields);
    }

    public function getViewFieldsProperty()
    {
        return $this->model->viewFields();
    }

    public function validationAttributes()
    {
        $attributes = [];

        foreach ($this->model->inputFields() as $field) {
            $attributes['form.fields.'.$field['slug']] = __($field['slug']);
        }

        return $attributes;
    }

    /**
     * Convert editor representations before assigning custom-table columns.
     *
     * Meta-backed fields already run through their field type's set() method
     * during the model save pipeline. Custom-table fields are assigned
     * directly, so an explicit dehydration step keeps editor-only parsing out
     * of model mutators and ordinary application writes.
     */
    protected function dehydrateCustomTableFormFields(array $attributes): array
    {
        if (! $this->model->usesCustomTable()) {
            return $attributes;
        }

        foreach ($this->model->inputFields() as $field) {
            $slug = $field['slug'];

            if (array_key_exists($slug, $attributes) && method_exists($field['field'], 'dehydrate')) {
                $attributes[$slug] = $field['field']->dehydrate($attributes[$slug], $field);
            }
        }

        return $attributes;
    }
}
