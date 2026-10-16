<?php

namespace Aura\Base\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class Navigation extends Component
{
    public $iconClass;

    public $toggledGroups = [];

    #[Computed]
    public function compact(): bool
    {
        return in_array($this->sidebarSize(), ['compact', 'dense'], true);
    }

    #[Computed]
    public function darkmodeType(): string
    {
        return $this->settings['darkmode-type'] ?? config('aura.theme.darkmode-type');
    }

    public function getIconClass($sidebarType)
    {
        return '';
    }

    public function isToggled($group)
    {
        return ! in_array($group, $this->toggledGroups);
    }

    public function mount($query = null)
    {
        $this->dispatch('NavigationMounted');

        if (auth()->check() && auth()->user()->getOptionSidebar()) {
            $this->toggledGroups = auth()->user()->getOptionSidebar();
        } else {
            $this->toggledGroups = [];
            // $this->sidebarToggled = true;
        }

        if (auth()->check()) {
            $this->sidebarToggled = auth()->user()->getOptionSidebarToggled();
        }

        $this->iconClass = $this->getIconClass($this->sidebarType);
    }

    public function render()
    {
        return view('aura::livewire.navigation');
    }

    #[Computed]
    public function settings()
    {
        if (config('aura.teams')) {
            return app('aura')::getOption('settings');
        }

        return app('aura')::getOption('settings');
    }

    #[Computed]
    public function sidebarDarkmodeType(): string
    {
        return $this->settings['sidebar-darkmode-type'] ?? config('aura.theme.sidebar-darkmode-type');
    }

    #[Computed]
    public function sidebarSize(): string
    {
        $size = $this->settings['sidebar-size'] ?? config('aura.theme.sidebar-size');

        return in_array($size, ['standard', 'compact', 'dense'], true) ? $size : 'standard';
    }

    #[Computed]
    public function sidebarToggled()
    {
        return auth()->check() ? auth()->user()->getOptionSidebarToggled() : true;
    }

    #[Computed]
    public function sidebarType(): string
    {
        return $this->settings['sidebar-type'] ?? config('aura.theme.sidebar-type');
    }

    public function toggleGroup($group)
    {
        if (in_array($group, $this->toggledGroups)) {
            $this->toggledGroups = array_diff($this->toggledGroups, [$group]);
        } else {
            $this->toggledGroups[] = $group;
        }

        auth()->user()->updateOption('sidebar', $this->toggledGroups);
    }

    public function toggleSidebar()
    {
        $this->sidebarToggled = ! $this->sidebarToggled;

        auth()->user()->updateOption('sidebarToggled', $this->sidebarToggled);
    }
}
