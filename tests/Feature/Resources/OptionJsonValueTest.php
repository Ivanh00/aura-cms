<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resources\Option;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
    Aura::fake();
    Aura::setModel(new Option);
});

it('hydrates a structured option as formatted JSON', function () {
    $option = Option::create([
        'name' => 'user.1.columns.Genre',
        'value' => ['title' => true, 'status' => false],
    ]);

    Livewire::test(Edit::class, ['slug' => 'option', 'id' => $option->id])
        ->assertSet('form.fields.value', <<<'JSON'
{
    "title": true,
    "status": false
}
JSON);
});

it('saves valid JSON without double encoding it', function () {
    $option = Option::create([
        'name' => 'user.1.columns.Genre',
        'value' => ['title' => true],
    ]);

    Livewire::test(Edit::class, ['slug' => 'option', 'id' => $option->id])
        ->set('form.fields.value', <<<'JSON'
{
    "title": false,
    "nested": {
        "count": 2,
        "labels": ["drama", "comedy"]
    }
}
JSON)
        ->call('save')
        ->assertHasNoErrors();

    expect($option->fresh()->value)->toBe([
        'title' => false,
        'nested' => [
            'count' => 2,
            'labels' => ['drama', 'comedy'],
        ],
    ]);
});

it('preserves the stored value when invalid JSON is rejected', function () {
    $original = ['title' => true, 'nested' => ['count' => 2]];
    $option = Option::create([
        'name' => 'user.1.columns.Genre',
        'value' => $original,
    ]);

    Livewire::test(Edit::class, ['slug' => 'option', 'id' => $option->id])
        ->set('form.fields.value', '{"title": true,}')
        ->call('save')
        ->assertHasErrors(['form.fields.value' => 'json']);

    expect($option->fresh()->value)->toBe($original);
});

it('keeps native scalar option values usable through an unchanged form save', function (mixed $value, string $editorValue) {
    $option = Option::create([
        'name' => 'scalar-option',
        'value' => $value,
    ]);

    Livewire::test(Edit::class, ['slug' => 'option', 'id' => $option->id])
        ->assertSet('form.fields.value', $editorValue)
        ->call('save')
        ->assertHasNoErrors();

    expect($option->fresh()->value)->toBe($value);
})->with([
    'string' => ['dark', '"dark"'],
    'integer' => [2, '2'],
    'boolean' => [true, 'true'],
]);

it('preserves JSON-looking strings written by application code', function (string $value) {
    $option = Option::create([
        'name' => 'native-string-option',
        'value' => 'initial',
    ]);

    $option->update(['value' => $value]);

    expect($option->fresh()->value)->toBeString()->toBe($value);
})->with([
    'boolean-looking string' => ['true'],
    'number-looking string' => ['2'],
    'array-looking string' => ['[]'],
    'null-looking string' => ['null'],
]);

it('preserves JSON null through an unchanged edit save and reopen', function () {
    $option = Option::create([
        'name' => 'null-option',
        'value' => null,
    ]);

    Livewire::test(Edit::class, ['slug' => 'option', 'id' => $option->id])
        ->assertSet('form.fields.value', 'null')
        ->call('save')
        ->assertHasNoErrors();

    expect($option->fresh()->value)->toBeNull();

    Livewire::test(Edit::class, ['slug' => 'option', 'id' => $option->id])
        ->assertSet('form.fields.value', 'null');
});
