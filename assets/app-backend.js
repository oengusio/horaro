import { registerVueControllerComponents } from '@symfony/ux-vue';
// I NEED MOTHER FUCKING STIMULUS FOR VUE TO WORK SYMFONY PLS
import './bootstrap.js';
import jQuery from 'jquery';
import 'bootstrap';
import { initScheduler } from './js/backend/scheduler.js';

// backwards compat for old code
window.jQuery = jQuery;
window.$ = jQuery;

window.csrfToken     = $('meta[name="csrf_token"]').attr('content');
window.csrfTokenName = $('meta[name="csrf_token_name"]').attr('content');

console.log('This log comes from assets/app-backend.js. IF assetmapper did not screw me over :D');

registerVueControllerComponents();

const ui = document.body.dataset.ui;

if (ui) {
  if (ui === 'scheduler') {
    initScheduler();
  }
}
