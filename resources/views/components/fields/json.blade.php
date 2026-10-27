<x-aura::fields.wrapper :field="$field">
    @php
        $fieldSlug = $field['slug'];
        $fieldContent = $form['fields'][$fieldSlug] ?? '';
        $content = is_string($fieldContent) && json_validate($fieldContent)
            ? $fieldContent
            : json_encode($fieldContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $minHeight = (int) (optional($field)['min_height'] ?? 250);
        $maxHeight = (int) (optional($field)['max_height'] ?? 600);
    @endphp

    <div class="overflow-hidden w-full bg-white rounded-lg border appearance-none border-gray-500/30 shadow-xs focus-within:border-primary-300 focus-within:ring focus-within:ring-primary-300 focus-within:ring-opacity-50 dark:focus-within:ring-primary-500 dark:focus-within:ring-opacity-50 dark:bg-gray-900">
        <div
            wire:ignore
            x-data="{ fieldSlug: @js($fieldSlug), minHeight: {{ $minHeight }}, maxHeight: {{ $maxHeight }}, error: null }"
            x-init="
                // The bundled Monaco build ships without web workers, so keep the
                // JSON language features that run on the main thread and validate
                // with JSON.parse below instead of the worker-backed diagnostics.
                monaco.languages.json.jsonDefaults.setModeConfiguration({
                    tokens: true,
                    colors: false,
                    completionItems: false,
                    hovers: false,
                    documentSymbols: false,
                    documentFormattingEdits: false,
                    documentRangeFormattingEdits: false,
                    foldingRanges: false,
                    diagnostics: false,
                    selectionRanges: false,
                });

                const editor = monaco.editor.create($refs.editor, {
                    value: @js($content),
                    language: 'json',
                    automaticLayout: true,
                    minimap: { enabled: false },
                    padding: { top: 12, bottom: 12 },
                    scrollBeyondLastLine: false,
                    fontSize: 14,
                    lineHeight: 22,
                    lineNumbers: 'on',
                    lineNumbersMinChars: 3,
                    tabSize: 4,
                    folding: true,
                    matchBrackets: 'always',
                    autoIndent: 'full',
                    formatOnPaste: true,
                    wordWrap: 'on',
                    renderWhitespace: 'none',
                    guides: { indentation: true },
                });

                monaco.editor.setTheme(document.documentElement.classList.contains('dark') ? 'github-dark' : 'github-light');

                const resize = () => {
                    const height = Math.min(Math.max(editor.getContentHeight(), minHeight), maxHeight);
                    $refs.editor.style.height = height + 'px';
                    editor.layout();
                };

                editor.onDidContentSizeChange(resize);
                resize();

                const validate = (value) => {
                    try {
                        JSON.parse(value);
                        error = null;
                    } catch (e) {
                        error = e.message;
                    }
                };

                editor.onDidChangeModelContent(() => {
                    const value = editor.getValue();
                    validate(value);
                    $wire.$set('form.fields.' + fieldSlug, value, false);
                });
            "
        >
            <div x-ref="editor" class="w-full" style="height: {{ $minHeight }}px;"></div>
            <p x-show="error" x-cloak class="py-2 px-4 text-sm text-red-600 border-t border-gray-500/30" x-text="'Invalid JSON: ' + error"></p>
        </div>
    </div>

    @assets
        @vite(['resources/js/monaco.js'], 'vendor/aura/libs')
    @endassets
</x-aura::fields.wrapper>
