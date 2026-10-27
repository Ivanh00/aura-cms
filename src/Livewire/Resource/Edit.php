<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Aura\Base\Models\Post;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\MediaFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @method void notify(string $message, string $type = 'success')
 */
class Edit extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;
    use MediaFields;
    use RepeaterFields;

    // use Macroable;
    use WithFileUploads;

    public $form;

    public $inModal = false;

    public $mode = 'edit';

    public $model;

    public $slug;

    public $tab;

    public $tax;

    public function callMethod($method, $params = [], $captureReturnValueCallback = null)
    {
        // dd($method, $params, $captureReturnValueCallback);
        // If the method exists in this component, call it directly.
        if (method_exists($this, $method) || ! optional($params)[0]) {
            return parent::callMethod($method, $params, $captureReturnValueCallback);
        }

        // Assuming the first parameter is always the slug to identify the field.
        $slug = $params[0];

        // Get the corresponding field instance based on the slug.
        $field = $this->model->fieldBySlug($slug);

        // Forward the call to the field's method.
        if ($field) {

            $fieldTypeInstance = app($field['type']);

            // If the method exists in the field type, call it directly.
            if (method_exists($fieldTypeInstance, $method)) {
                $post = call_user_func_array([$fieldTypeInstance, $method], array_merge([$this->model, $this->form], $params));

                // If the field type method returns a post, update the post.
                if ($post) {
                    $this->form = $post;
                }

                // Make sure to return here, otherwise the parent callMethod will be called.
                return;
            }
        }

        // Run parent callMethod
        return parent::callMethod($method, $params, $captureReturnValueCallback);
    }

    public function hydrate()
    {
        // ray('edit hydrate');
        if ($this->model && isset($this->model->id)) {
            // $this->model = Auth::user();
            // ray($this->model);
        }
    }

    public function initializeModelFields()
    {
        foreach ($this->model->inputFields() as $field) {

            // If the method exists in the field type, call it directly.
            if (method_exists($field['field'], 'hydrate') && array_key_exists($field['slug'], $this->form['fields'] ?? [])) {
                // dd('hier');
                $this->form['fields'][$field['slug']] = $field['field']->hydrate($this->form['fields'][$field['slug']], $field);
            }

            if ($field['field']->on_forms === false) {
                unset($this->form['fields'][$field['slug']]);
            }

            if (optional($field)['on_forms'] === false) {
                unset($this->form['fields'][$field['slug']]);
            }
        }
    }

    public function mount($id, $slug = null)
    {
        // Use provided slug or get from current route
        if ($slug) {
            $this->slug = $slug;
        } elseif (! $this->slug) {
            $routeName = request()->route()->getName();
            $this->slug = explode('.', $routeName)[1] ?? null;
        }

        $this->model = Aura::findResourceBySlug($this->slug)->find($id);

        // Authorize
        $this->authorize('update', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->attributesToArray();

        // foreach fields, call the hydration method on the field
        $this->initializeModelFields();

        // foreach fields, call the hydration method on the field

        // Set on model instead of here
        // if $this->form['terms']['tag'] is not set, set it to null
    }

    #[On('refreshComponent')]
    public function refreshComponent()
    {
        // Livewire will handle $refresh automatically
    }

    #[On('reload')]
    public function reload()
    {
        $this->model = $this->model->fresh();
        // $this->form = $this->model->attributesToArray();
        // The GET method is not supported for this route. Only POST is supported.
        // Therefore, we cannot use redirect()->to(url()->current()).
        // Instead, we will refresh the component.
        $this->dispatch('refreshComponent');
    }

    public function render()
    {
        return view($this->model->editView())->layout('aura::components.layout.app');
    }

    public function rules()
    {
        $rules = collect($this->model->validationRules())->mapWithKeys(function ($rule, $key) {
            return ["form.fields.$key" => $rule];
        })->toArray();

        // Modify rules if the model implements it
        if (method_exists($this->model, 'modifyValidationRules')) {
            $rules = $this->model->modifyValidationRules($rules, $this->form, $this);
        }

        return $this->ignoreCurrentModelInUniqueRules($rules);
    }

    #[On('saveModel')]
    public function save()
    {
        $this->validate();

        // unset this post fields group
        if ($this->model->usesCustomTable()) {
            $this->model->update($this->dehydrateCustomTableFormFields($this->form['fields']));
        } else {
            $this->model->update($this->form);
        }

        $this->notify(__('Successfully updated'));

        if ($this->inModal) {
            $this->dispatch('closeModal');
            $this->dispatch('refreshTable');
        }

        $this->model = $this->model->refresh();
        $this->form = $this->model->attributesToArray();
        $this->initializeModelFields();

        $this->dispatch('refreshComponent');
    }

    #[On('saveBeforeAction')]
    public function saveBeforeAction()
    {
        // Implementation is in HasActions trait
    }

    public function updatedPost($value, $array) {}

    protected function callComponentMethod($method, $params)
    {
        $callbacks = array_filter(
            $params,
            fn ($param) => $param instanceof Closure
        );

        $params = array_filter(
            $params,
            fn ($param) => ! $param instanceof Closure
        );

        $result = parent::callMethod($method, $params);

        foreach ($callbacks as $callback) {
            $callback($this);
        }

        return $result;
    }

    protected function ignoreCurrentModelInRule($rule, string $attribute)
    {
        if (is_string($rule)) {
            return $this->ignoreCurrentModelInRuleString($rule, $attribute);
        }

        if (is_array($rule)) {
            return array_map(function ($nestedRule) use ($attribute) {
                return $this->ignoreCurrentModelInRule($nestedRule, $attribute);
            }, $rule);
        }

        if ($rule instanceof Unique) {
            return (clone $rule)->ignore($this->model->getKey(), $this->model->getKeyName());
        }

        return $rule;
    }

    protected function ignoreCurrentModelInRuleString(string $rule, string $attribute): string
    {
        return preg_replace_callback('/(^|\|)(unique:[^|]+)(?=\||$)/', function ($matches) use ($attribute) {
            $parameters = str_getcsv(substr($matches[2], strlen('unique:')), escape: '\\');

            if (isset($parameters[2]) && $parameters[2] !== '' && strtoupper((string) $parameters[2]) !== 'NULL') {
                return $matches[0];
            }

            $parameters[1] = $parameters[1] ?? str($attribute)->afterLast('.')->toString();
            $parameters[2] = (string) $this->model->getKey();
            $parameters[3] = $parameters[3] ?? $this->model->getKeyName();

            return $matches[1].'unique:'.implode(',', $parameters);
        }, $rule) ?? $rule;
    }

    protected function ignoreCurrentModelInUniqueRules(array $rules): array
    {
        if (! $this->model?->exists) {
            return $rules;
        }

        return collect($rules)->mapWithKeys(function ($rule, $attribute) {
            return [$attribute => $this->ignoreCurrentModelInRule($rule, $attribute)];
        })->all();
    }
}
