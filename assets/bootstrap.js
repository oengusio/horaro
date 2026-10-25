import { startStimulusApp } from '@symfony/stimulus-bundle';
import $ from 'jquery';

// backwards compat for old code
window.jQuery = $;
window.$ = $;

// Needs this for vue :/
const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
