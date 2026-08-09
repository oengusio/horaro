import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    id: String,
    item: String,
  };

  connect() {
    // this.element.textContent = 'Hello Stimulus!';
    console.log('schedule item connected~', this.itemValue);
  }
}
