<?php

test('the sidebar toggles immediately and keeps desktop state in the browser', function () {
    $this->actingAs(createSuperAdmin());

    $page = visit('/admin')->resize(1280, 900);

    $page->script("() => localStorage.setItem('leftSidebar', 'true')");

    $page->refresh()->wait(1)
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'true')
        ->click('[data-sidebar-toggle-collapse]')
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'false')
        ->assertScript("localStorage.getItem('leftSidebar') === 'false'")
        ->refresh()->wait(1)
        ->assertAttribute('[data-sidebar-state]', 'data-sidebar-open', 'false')
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
