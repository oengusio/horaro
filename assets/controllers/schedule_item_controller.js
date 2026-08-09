import { Controller } from '@hotwired/stimulus';
import moment from 'moment';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    item: Array,
  };

  connect() {
    // this.element.textContent = 'Hello Stimulus!';
    console.log('schedule item connected~', this.itemValue);

    // TODO: odd/even classes
    this.element.classList.add('h-item');
    this.element.dataset.itemid = this.id;
  }

  /**
   * @return {String}
   */
  get id() {
    return this.itemValue[0];
  }

  /**
   * @return {Number}
   */
  get length() {
    return this.itemValue[1];
  }

  /**
   * @return {Object}
   */
  get columns() {
    return this.itemValue[3];
  }
}
