<?php

namespace Aura\Base\Fields;

class Json extends Field
{
    public $edit = 'aura::fields.json';

    public $view = 'aura::fields.json-view';

    /**
     * Convert validated JSON editor text back to its native PHP value.
     */
    public function dehydrate($value, $field = null)
    {
        if (! is_string($value)) {
            return $value;
        }

        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    public function display($field, $value, $model)
    {
        // The encoded payload is database-backed and view-value renders this
        // raw, so neutralise stored markup. ENT_NOQUOTES: the JSON quotes are
        // text content here, not an attribute value, and escaping them would
        // only make the rendered payload unreadable.
        $json = json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return htmlspecialchars($json === false ? '' : $json, ENT_NOQUOTES, 'UTF-8', false);
    }

    public function get($class, $value, $field = null)
    {
        if (optional($field)['decode'] === false || is_array($value) || $value === null) {
            return $value;
        }

        return json_decode($value, true);
    }

    /**
     * Wrap the tokens of display() output in syntax highlighting spans.
     *
     * Expects JSON that display() has already escaped: it contains no raw
     * markup, so only the wrapping spans are added here.
     */
    public static function highlight(string $json): string
    {
        return preg_replace_callback(
            '/("(?:[^"\\\\]|\\\\.)*")(\s*:)?|\b(true|false)\b|\b(null)\b|(-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)/',
            function (array $match) {
                [$token, $class] = match (true) {
                    ($match[2] ?? '') !== '' => [$match[1], 'text-primary-700 dark:text-primary-400'],
                    $match[1] !== '' => [$match[1], 'text-green-700 dark:text-green-400'],
                    ($match[3] ?? '') !== '' => [$match[3], 'text-purple-600 dark:text-purple-400'],
                    ($match[4] ?? '') !== '' => [$match[4], 'text-gray-500 dark:text-gray-400'],
                    default => [$match[5], 'text-blue-600 dark:text-blue-400'],
                };

                return '<span class="'.$class.'">'.$token.'</span>'.($match[2] ?? '');
            },
            $json
        ) ?? $json;
    }

    public function hydrate($value, $field = null)
    {
        $json = json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return $json === false ? '' : $json;
    }

    // public function getFields()
    // {
    //     return array_merge(parent::getFields(), [
    //         [
    //             'label' => 'JSON',
    //             'name' => 'JSON',
    //             'type' => 'Aura\\Base\\Fields\\Tab',
    //             'slug' => 'json',
    //             'style' => [],
    //         ],
    //         [
    //             'label' => 'Language',
    //             'name' => 'Language',
    //             'type' => 'Aura\\Base\\Fields\\Select',
    //             'validation' => 'required',
    //             'slug' => 'language',
    //             'options' => [
    //                 'html' => 'HTML',
    //                 'css' => 'CSS',
    //                 'javascript' => 'JavaScript',
    //                 'php' => 'PHP',
    //                 'json' => 'JSON',
    //                 'yaml' => 'YAML',
    //                 'markdown' => 'Markdown',
    //             ],
    //         ],
    //     ]);
    // }

    public function set($post, $field, $value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
