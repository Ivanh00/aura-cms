<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\Json;

describe('Json Field Configuration', function () {
    test('has correct edit and view properties', function () {
        $field = new Json;

        expect($field->edit)->toBe('aura::fields.json')
            ->and($field->view)->toBe('aura::fields.json-view');
    });
});

describe('Json Field Value Handling', function () {
    test('get decodes JSON strings to arrays', function () {
        $field = new Json;

        expect($field->get(null, '{"a":1,"b":2}'))->toBe(['a' => 1, 'b' => 2]);
    });

    test('get returns arrays and null unchanged', function () {
        $field = new Json;

        expect($field->get(null, ['a' => 1]))->toBe(['a' => 1])
            ->and($field->get(null, null))->toBeNull();
    });

    test('get can preserve values already decoded by a model cast', function () {
        $field = new Json;

        expect($field->get(null, 'dark', ['decode' => false]))->toBe('dark')
            ->and($field->get(null, true, ['decode' => false]))->toBeTrue();
    });

    test('set encodes arrays to JSON', function () {
        $field = new Json;

        expect($field->set(null, [], ['a' => 1]))->toBe('{"a":1}');
    });

    test('set returns non-array values unchanged', function () {
        $field = new Json;

        expect($field->set(null, [], 'plain'))->toBe('plain');
    });

    test('round-trips an array through set then get', function () {
        $field = new Json;
        $value = ['nested' => ['x' => 1], 'list' => [1, 2, 3]];

        expect($field->get(null, $field->set(null, [], $value)))->toBe($value);
    });

    test('hydrate formats native values as editable JSON', function () {
        $field = new Json;

        expect($field->hydrate(['nested' => ['enabled' => true]]))->toBe(<<<'JSON'
{
    "nested": {
        "enabled": true
    }
}
JSON);
    });

    test('display pretty prints and escapes JSON', function () {
        $field = new Json;

        expect($field->display([], ['markup' => '<script>alert(1)</script>'], null))->toBe(<<<'JSON'
{
    "markup": "&lt;script&gt;alert(1)&lt;/script&gt;"
}
JSON);
    });
});
