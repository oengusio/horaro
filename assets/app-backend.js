import { registerVueControllerComponents } from '@symfony/ux-vue';
// I NEED MOTHER FUCKING STIMULUS FOR VUE TO WORK SYMFONY PLS
import './bootstrap.js';
import jQuery from 'jquery';
import 'bootstrap';
import { initScheduler } from './js/backend/scheduler.js';
import { mirrorColumnWidths } from './js/utils/itemUtils.js';

jQuery('#h-scheduler-container-old').remove();

// backwards compat for old code
window.jQuery = jQuery;
window.$ = jQuery;

window.csrfToken = $('meta[name="csrf_token"]').attr('content');
window.csrfTokenName = $('meta[name="csrf_token_name"]').attr('content');

console.log('This log comes from assets/app-backend.js. IF assetmapper did not screw me over :D');

registerVueControllerComponents();

const ui = document.body.dataset.ui;

function resizeColumns() {
  const dataNode = $('.h-scheduler');
  mirrorColumnWidths(dataNode, $('tr:first > *', dataNode.prev()));
}

if (ui) {
  if (ui === 'scheduler') {
    initScheduler();

    window.addEventListener('editorSaved', () => {
      setTimeout(() => {
        resizeColumns();
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
