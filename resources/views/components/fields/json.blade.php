<x-aura::fields.wrapper :field="$field">
    <div
        class="overflow-hidden w-full bg-white rounded-lg border appearance-none border-gray-500/30 shadow-xs focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900">

        @php
            $fieldSlug = $field['slug'];
            $fieldContent = $form['fields'][$fieldSlug] ?? '';
            $content = is_string($fieldContent) && json_validate($fieldContent)
                ? $fieldContent
                : json_encode($fieldContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // dump($content);

            // This component is from https://devdojo.com/pines/docs/monaco-editor
            // Huge thanks to DevDojo for this component.

        @endphp

        <div wire:ignore x-data="{
            monacoContent: @js($content),
            monacoLanguage: 'json',
            monacoPlaceholder: true,
            monacoPlaceholderText: '',
            monacoLoader: true,
            monacoFontSize: '14px',
            monacoId: $id('monaco-editor'),
            monacoEditor(editor) {
                editor.onDidChangeModelContent((e) => {
                    this.monacoContent = editor.getValue();
                    this.updatePlaceholder(editor.getValue());
                    {{-- console.log(this.monacoContent); --}}
                    $wire.$set('form.fields.{{ $field['slug'] }}', this.monacoContent);
                });
                editor.onDidBlurEditorWidget(() => {
                    this.updatePlaceholder(editor.getValue());
                });
                editor.onDidFocusEditorWidget(() => {
                    this.updatePlaceholder(editor.getValue());
                });
            },
            updatePlaceholder: function(value) {
                if (value == '') {
                    this.monacoPlaceholder = true;
                    return;
                }
                this.monacoPlaceholder = false;
            },
            monacoEditorFocus() {
                document.getElementById(this.monacoId).dispatchEvent(new CustomEvent('monaco-editor-focused', { monacoId: this.monacoId }));
            },
            monacoEditorAddLoaderScriptToHead() {
                script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/loader.min.js';
                document.head.appendChild(script);
            }
        }" x-init="if (typeof _amdLoaderGlobal == 'undefined') {
            monacoEditorAddLoaderScriptToHead();
        }
        monacoLoaderInterval = setInterval(function() {
            if (typeof _amdLoaderGlobal !== 'undefined') {
                // Based on https://jsfiddle.net/developit/bwgkr6uq/ which works without needing service worker. Provided by loader.min.js.
                require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs' } });
                let proxy = URL.createObjectURL(new Blob([` self.MonacoEnvironment = { baseUrl: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min' }; importScripts('https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/base/worker/workerMain.min.js');`], { type: 'text/javascript' }));
                window.MonacoEnvironment = { getWorkerUrl: () => proxy };
                require(['vs/editor/editor.main'], function() {
                    monacoTheme = {'base':'vs','inherit':true,'rules':[{'background':'ffffff','token':''},{'foreground':'6a737d','token':'comment'},{'foreground':'6a737d','token':'punctuation.definition.comment'},{'foreground':'6a737d','token':'string.comment'},{'foreground':'005cc5','token':'constant'},{'foreground':'005cc5','token':'entity.name.constant'},{'foreground':'005cc5','token':'variable.other.constant'},{'foreground':'005cc5','token':'variable.language'},{'foreground':'6f42c1','token':'entity'},{'foreground':'6f42c1','token':'entity.name'},{'foreground':'24292e','token':'variable.parameter.function'},{'foreground':'22863a','token':'entity.name.tag'},{'foreground':'d73a49','token':'keyword'},{'foreground':'d73a49','token':'storage'},{'foreground':'d73a49','token':'storage.type'},{'foreground':'24292e','token':'storage.modifier.package'},{'foreground':'24292e','token':'storage.modifier.import'},{'foreground':'24292e','token':'storage.type.java'},{'foreground':'032f62','token':'string'},{'foreground':'032f62','token':'punctuation.definition.string'},{'foreground':'032f62','token':'string punctuation.section.embedded source'},{'foreground':'005cc5','token':'support'},{'foreground':'005cc5','token':'meta.property-name'},{'foreground':'e36209','token':'variable'},{'foreground':'24292e','token':'variable.other'},{'foreground':'b31d28','fontStyle':'bold italic underline','token':'invalid.broken'},{'foreground':'b31d28','fontStyle':'bold italic underline','token':'invalid.deprecated'},{'foreground':'fafbfc','background':'b31d28','fontStyle':'italic underline','token':'invalid.illegal'},{'foreground':'fafbfc','background':'d73a49','fontStyle':'italic underline','token':'carriage-return'},{'foreground':'b31d28','fontStyle':'bold italic underline','token':'invalid.unimplemented'},{'foreground':'b31d28','token':'message.error'},{'foreground':'24292e','token':'string source'},{'foreground':'005cc5','token':'string variable'},{'foreground':'032f62','token':'source.regexp'},{'foreground':'032f62','token':'string.regexp'},{'foreground':'032f62','token':'string.regexp.character-class'},{'foreground':'032f62','token':'string.regexp constant.character.escape'},{'foreground':'032f62','token':'string.regexp source.ruby.embedded'},{'foreground':'032f62','token':'string.regexp string.regexp.arbitrary-repitition'},{'foreground':'22863a','fontStyle':'bold','token':'string.regexp constant.character.escape'},{'foreground':'005cc5','token':'support.constant'},{'foreground':'005cc5','token':'support.variable'},{'foreground':'005cc5','token':'meta.module-reference'},{'foreground':'735c0f','token':'markup.list'},{'foreground':'005cc5','fontStyle':'bold','token':'markup.heading'},{'foreground':'005cc5','fontStyle':'bold','token':'markup.heading entity.name'},{'foreground':'22863a','token':'markup.quote'},{'foreground':'24292e','fontStyle':'italic','token':'markup.italic'},{'foreground':'24292e','fontStyle':'bold','token':'markup.bold'},{'foreground':'005cc5','token':'markup.raw'},{'foreground':'b31d28','background':'ffeef0','token':'markup.deleted'},{'foreground':'b31d28','background':'ffeef0','token':'meta.diff.header.from-file'},{'foreground':'b31d28','background':'ffeef0','token':'punctuation.definition.deleted'},{'foreground':'22863a','background':'f0fff4','token':'markup.inserted'},{'foreground':'22863a','background':'f0fff4','token':'meta.diff.header.to-file'},{'foreground':'22863a','background':'f0fff4','token':'punctuation.definition.inserted'},{'foreground':'e36209','background':'ffebda','token':'markup.changed'},{'foreground':'e36209','background':'ffebda','token':'punctuation.definition.changed'},{'foreground':'f6f8fa','background':'005cc5','token':'markup.ignored'},{'foreground':'f6f8fa','background':'005cc5','token':'markup.untracked'},{'foreground':'6f42c1','fontStyle':'bold','token':'meta.diff.range'},{'foreground':'005cc5','token':'meta.diff.header'},{'foreground':'005cc5','fontStyle':'bold','token':'meta.separator'},{'foreground':'005cc5','token':'meta.output'},{'foreground':'586069','token':'brackethighlighter.tag'},{'foreground':'586069','token':'brackethighlighter.curly'},{'foreground':'586069','token':'brackethighlighter.round'},{'foreground':'586069','token':'brackethighlighter.square'},{'foreground':'586069','token':'brackethighlighter.angle'},{'foreground':'586069','token':'brackethighlighter.quote'},{'foreground':'b31d28','token':'brackethighlighter.unmatched'},{'foreground':'b31d28','token':'sublimelinter.mark.error'},{'foreground':'e36209','token':'sublimelinter.mark.warning'},{'foreground':'959da5','token':'sublimelinter.gutter-mark'},{'foreground':'032f62','fontStyle':'underline','token':'constant.other.reference.link'},{'foreground':'032f62','fontStyle':'underline','token':'string.other.link'}],'colors':{'editor.foreground':'#24292e','editor.background':'#ffffff','editor.selectionBackground':'#c8c8fa','editor.inactiveSelectionBackground':'#fafbfc','editor.lineHighlightBackground':'#fafbfc','editorCursor.foreground':'#24292e','editorWhitespace.foreground':'#959da5','editorIndentGuide.background':'#959da5','editorIndentGuide.activeBackground':'#24292e','editor.selectionHighlightBorder':'#fafbfc'}};
                    monaco.editor.defineTheme('blackboard', monacoTheme);
                    document.getElementById(monacoId).editor = monaco.editor.create($refs.monacoEditorElement, {
                        value: monacoContent,
                        theme: 'blackboard',
                        fontSize: monacoFontSize,
                        renderIndentGuides: false, // Disable rendering of indent guides
                        lineNumbersMinChars: 3,
                        automaticLayout: true,
                        language: monacoLanguage
                    });
                    monacoEditor(document.getElementById(monacoId).editor);
                    document.getElementById(monacoId).addEventListener('monaco-editor-focused', function(event) {
                        document.getElementById(monacoId).editor.focus();
                    });
                    updatePlaceholder(document.getElementById(monacoId).editor.getValue());
                });
                clearInterval(monacoLoaderInterval);
                monacoLoader = false;
            }
        }, 5);" :id="monacoId"
            class="flex flex-col items-center relative justify-start w-full bg-white min-h-[250px] pt-3 h-100">
            <div x-show="monacoLoader"
                class="flex absolute inset-0 z-20 justify-center items-center w-full h-full duration-1000 ease-out">
                <svg class="w-4 h-4 text-gray-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>
            <div x-show="!monacoLoader" class="relative z-10 w-full h-full">
                <div x-ref="monacoEditorElement" class="w-full h-full text-lg editor"></div>
                <div x-ref="monacoPlaceholderElement" x-show="monacoPlaceholder" @click="monacoEditorFocus()"
                    :style="'font-size: ' + monacoFontSize"
                    class="absolute top-0 left-0 z-50 mt-0.5 ml-14 w-full font-mono text-sm text-gray-500 -translate-x-0.5"
                    x-text="monacoPlaceholderText"></div>
            </div>
        </div>

    </div>
        @once
            @push('styles')

            {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/loader.min.js"></script> --}}

                <style type="text/css" media="screen">
                    .editor {
                        min-height: {{ $field['height'] ?? '300'}}px;
                    }
                </style>
            @endpush
        @endonce

</x-aura::fields.wrapper>
