import './bootstrap';

import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import ui from '@alpinejs/ui';

import modal from './modal.js';

import { Sortable } from '@shopify/draggable';
import tippy from 'tippy.js';

window.Sortable = Sortable;
window.tippy = tippy;

// Register Alpine plugins via livewire:init event
// Livewire loads its own Alpine instance via @livewireScripts
document.addEventListener('livewire:init', () => {
  const Alpine = window.Alpine;

  Alpine.plugin(collapse);
  Alpine.plugin(focus);
  Alpine.plugin(ui);
  Alpine.plugin(modal);

  Alpine.data('aura', () => ({
    loading: false,
    success: false,

    error: null,

    showSearch: false,

    rightSidebar: true,
    showFilters: false,

    init() {
    },

    search() {
      $dispatch('search');
    },
    insetSidebar(event) {
      var sidebar = event.detail.element;
      let body = document.querySelector('body');
      setTimeout(function() {
        body.style.paddingRight = sidebar.offsetWidth + 'px';
      }, 10);
      setTimeout(function() {
        body.style.paddingRight = sidebar.offsetWidth + 'px';
      }, 50);
      setTimeout(function() {
        body.style.paddingRight = sidebar.offsetWidth + 'px';
      }, 100);
      setTimeout(function() {
        body.style.paddingRight = sidebar.offsetWidth + 'px';
      }, 250);

    },
    toggleRightSidebar() {
      this.rightSidebar = !this.rightSidebar;
    },
    closeSearch() {
      this.showSearch = false;
    }
  }))

});


function updateVisitedPages(title, url) {
  const key = 'visitedPages';
  const maxVisitedPages = 5;
  let visitedPages = JSON.parse(localStorage.getItem(key)) || [];

  visitedPages = visitedPages.filter(page => page.url !== url);
  visitedPages.unshift({ title, url });
  visitedPages = visitedPages.slice(0, maxVisitedPages);

  localStorage.setItem(key, JSON.stringify(visitedPages));
}

document.addEventListener('DOMContentLoaded', () => {
  const pageTitle = document.title;
  const pageUrl = window.location.href;
  updateVisitedPages(pageTitle, pageUrl);
});


// after 100ms trigger a window resize event to force the chart to redraw
setTimeout(function() {
    window.dispatchEvent(new Event('resize'));
}, 0);
setTimeout(function() {
    window.dispatchEvent(new Event('resize'));
}, 100);
