<?php

namespace Aura\Base\Livewire\Resource;

use Aura\Base\Facades\Aura;
use Aura\Base\RecordLayout\RecordLayoutResolver;
use Aura\Base\Traits\HasActions;
use Aura\Base\Traits\InteractsWithFields;
use Aura\Base\Traits\RepeaterFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @method void notify(string $message, string $type = 'success')
 */
class View extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use InteractsWithFields;
    use RepeaterFields;

    public $form;

    public $inModal = false;

    public $mode = 'view';

    public $model;

    public $slug;

    public $tax;

    public function getField($slug)
    {
        return $this->form['fields'][$slug];
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
        $this->authorize('view', $this->model);

        // Array instead of Eloquent Model
        $this->form = $this->model->attributesToArray();

        // $this->form['terms'] = $this->model->terms;
        // $this->form['terms']['tag'] = $this->form['terms']['tag'] ?? null;
        // $this->form['terms']['category'] = $this->form['terms']['category'] ?? null;
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
        $this->form = $this->model->attributesToArray();

        $this->dispatch('refreshComponent');
    }

    public function render()
    {
        // $view = "aura.{$this->slug}.view";

        // // if aura::aura.{$post->type}.view is set, use that view
        // if (view()->exists($view)) {
        //     return view($view)->layout('aura::components.layout.app');
        // }

        // if (view()->exists("aura::" . $view)) {
        //     return view("aura::" . $view)->layout('aura::components.layout.app');
        // }
        return view($this->model->viewView(), [
            'recordLayout' => app(RecordLayoutResolver::class)->resolve($this->model),
        ])->layout('aura::components.layout.app');

    }

    #[On('updateField')]
    public function updateField($field, $value)
    {
        // Implementation is in InteractsWithFields trait
    }
}
