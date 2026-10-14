<?php

test('the sidebar toggles immediately and keeps desktop state in the browser', function () {
    $this->actingAs(createSuperAdmin());

    $page = visit('/admin')->resize(1280, 900);

    $page->script("() => localStorage.setItem('leftSidebar', 'true')");

    $page->refresh()->wait(1)
        ->assertAttribute('.aura-sidebar', 'data-sidebar-initial-open', 'true')
        ->assertAttribute('.aura-sidebar', 'data-sidebar-hydrated', 'true')
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'true')
        ->click('[data-sidebar-toggle-collapse]')
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'false')
        ->assertScript("localStorage.getItem('leftSidebar') === 'false'")
        ->refresh()->wait(1)
        ->assertAttribute('.aura-sidebar', 'data-sidebar-initial-open', 'false')
        ->assertAttribute('.aura-sidebar', 'data-sidebar-hydrated', 'true')
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'false')
        ->assertScript("getComputedStyle(document.querySelector('.aura-content > .overflow-y-auto')).scrollbarGutter === 'stable'")
        ->assertScript("!document.querySelector('.aura-sidebar-heading .show-collapsed').hasAttribute('x-cloak')")
        ->assertScript(<<<'JS'
            (() => {
                const root = document.querySelector('.aura-sidebar');
                const layout = document.querySelector('[data-sidebar-layout]');
                const panel = document.querySelector('[data-sidebar-panel]');
                const content = document.querySelector('.aura-content');
                const contentScroller = content.querySelector('.overflow-y-auto');
                const icons = [...panel.querySelectorAll('svg')].slice(0, 12);
                const hydrated = root.dataset.sidebarHydrated;
                const snapshot = () => JSON.stringify({
                    layout: layout.getBoundingClientRect().toJSON(),
                    panel: panel.getBoundingClientRect().toJSON(),
                    content: content.getBoundingClientRect().toJSON(),
                    scrollerClientWidth: contentScroller.clientWidth,
                    scrollerClientHeight: contentScroller.clientHeight,
                    scrollerScrollHeight: contentScroller.scrollHeight,
                    icons: icons.map((icon) => icon.getBoundingClientRect().toJSON()),
                });

                root.dataset.sidebarHydrated = 'false';
                const beforeHydration = snapshot();
                root.dataset.sidebarHydrated = hydrated;

                return beforeHydration === snapshot();
            })()
            JS)
        ->click('[data-sidebar-toggle-expand]')
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'true')
        ->assertScript("localStorage.getItem('leftSidebar') === 'true'")
        ->click('[data-sidebar-toggle-collapse]')
        ->click('[data-sidebar-toggle-expand]')
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'true');

    $page->script(<<<'JS'
        async () => {
            const navigation = document.querySelector('[data-sidebar-state]').closest('[wire\\:id]');
            await window.Livewire.find(navigation.getAttribute('wire:id')).$refresh();
        }
        JS);

    $page->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'true')
        ->navigate('/admin/user')->wait(1)
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'true')
        ->resize(390, 844)->wait(1)
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'false')
        ->assertScript("localStorage.getItem('leftSidebar') === 'true'")
        ->click('[data-sidebar-toggle-mobile]')
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'true')
        ->assertScript("localStorage.getItem('leftSidebar') === 'true'")
        ->resize(1280, 900)->wait(1)
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'true');
});
