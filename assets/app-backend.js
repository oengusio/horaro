import { registerVueControllerComponents } from '@symfony/ux-vue';
// I NEED MOTHER FUCKING STIMULUS FOR VUE TO WORK SYMFONY PLS
import './bootstrap.js';
import $ from 'jquery';
import { initScheduler } from './js/backend/scheduler.js';
import { mirrorColumnWidths } from './js/utils/itemUtils.js';
import SpatialNavigation from './js/backend/SpatialNavigation.js';
import { initColumnist } from './js/backend/columnist.js';
import './trd_party/pickadate/picker.js';
import './trd_party/pickadate/picker.date.js';
import './trd_party/pickadate/picker.time.js';

window.csrfToken = $('meta[name="csrf_token"]').attr('content');
window.csrfTokenName = $('meta[name="csrf_token_name"]').attr('content');

console.log('This log comes from assets/app-backend.js. IF assetmapper did not screw me over :D');

registerVueControllerComponents();

let dataNode;

const ui = document.body.dataset.ui;

function resizeColumns() {
  mirrorColumnWidths(dataNode, $('tr:first > *', dataNode.prev()));
}

function openNextEditor(currentId) {
  const current = $(`#h-e-${currentId}`);
  const root = current.closest('table');
  const links = root.find('a.editable-click:visible');
  const selfIdx = links.index(current);
  const next = (selfIdx < (links.length - 1)) ? $(links[selfIdx + 1]) : $('#h-add-model');

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

    window.addEventListener('ui-ready', () => {
      dataNode = $('.h-scheduler');
      resizeColumns();
    }, { once: true });

    window.addEventListener('editorSaved', (e) => {
      setTimeout(() => {
        resizeColumns();
        openNextEditor(e.detail.id);
      }, 100);
    });

    window.addEventListener('resize', () => {
      resizeColumns();
    });
  } else if (ui === 'columnist') {
    initColumnist();

    window.addEventListener('ui-ready', () => {
      dataNode = $('.h-columnist');
    }, { once: true });

    window.addEventListener('editorSaved', (e) => {
      setTimeout(() => {
        resizeColumns();
        openNextEditor(e.detail.id);
      }, 100);
    });
  }

  // wait for vue
  window.addEventListener('ui-ready', () => {
    window.viewModel.initDragAndDrop();

    new SpatialNavigation(dataNode);
  }, { once: true });
}

// const horaroTimeFormat = "H:i a";

document.addEventListener('DOMContentLoaded', () => {
  $('#start_date').pickadate({
		formatSubmit: 'yyyy-mm-dd',
		hiddenName: true
	});

	$('#start_time').pickatime({
		interval: 15,
		formatSubmit: 'HH:i',
		format: horaroTimeFormat,
		formatLabel: horaroTimeFormat,
		hiddenName: true
	});
}, {  once: true })
