import { registerVueControllerComponents } from '@symfony/ux-vue';
// I NEED MOTHER FUCKING STIMULUS FOR VUE TO WORK SYMFONY PLS
import './bootstrap.js';
import $ from 'jquery';
import 'bootstrap';
import { initScheduler } from './js/backend/scheduler.js';
import { mirrorColumnWidths } from './js/utils/itemUtils.js';

// backwards compat for old code
window.jQuery = $;
window.$ = $;

window.csrfToken = $('meta[name="csrf_token"]').attr('content');
window.csrfTokenName = $('meta[name="csrf_token_name"]').attr('content');

console.log('This log comes from assets/app-backend.js. IF assetmapper did not screw me over :D');

registerVueControllerComponents();

const ui = document.body.dataset.ui;

function resizeColumns() {
  const dataNode = $('.h-scheduler');
  mirrorColumnWidths(dataNode, $('tr:first > *', dataNode.prev()));
}

function openNextEditor(currentId) {
  const current = $(`#h-e-${currentId}`);
  const root    = current.closest('table')
  const links   = root.find('a.editable-click:visible');
  const selfIdx = links.index(current);
  const next    = (selfIdx < (links.length - 1)) ? $(links[selfIdx+1]) : $('#h-add-model');

  if (next.is('.editable-click')) {
    next[0].click();
  } else {
    next.focus();

    // in case this saving triggers an ajax call to create the element,
    // the add button is still disabled right now. We set a flag to let
    // the success handler of the create call do the focussing.
    // TODO: self.nextFocus = true; (doesn't actually seem needed?)
  }
}

window.addEventListener('editorCanceled', (e) => {
  const current = $(`#h-e-${e.detail.id}`);

  current.focus();
});

if (ui) {
  if (ui === 'scheduler') {
    initScheduler();

    window.addEventListener('editorSaved', (e) => {
      setTimeout(() => {
        resizeColumns();
        openNextEditor(e.detail.id);
      }, 100);
    });

    window.addEventListener('resize', () => {
      resizeColumns();
    });

    setTimeout(() => {
      resizeColumns();
    }, 100);
  }
}
