import { Controller } from '@hotwired/stimulus';
import moment from 'moment';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    index: Number,
    item: Array,
  };

  connect() {
    // this.element.textContent = 'Hello Stimulus!';
    console.log('schedule item connected~', this.itemValue);

    // TODO: odd/even classes
    this.element.classList.add('h-item', this.bodyClass);
    this.element.dataset.itemid = this.id;

    this.update();
  }

  update() {
    this.scheduledContainer.innerText = this.formattedSchedule;
    this.estimateContainer.innerText = this.formattedLength;

    Object.entries(this.dataObj).forEach(([column, value]) => {
      this.element.querySelector(`#col_${column}`).innerText = value;
    });
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
  get dataObj() {
    return this.itemValue[2];
  }

  get formattedSchedule() {
    return 'test';
  }

  get formattedLength() {
    return 'test_length';
  }

  get bodyClass() {
    return this.indexValue % 2 === 1 ? 'h-odd' : 'h-even'
  }

  get rowClass() {
    return ``;
  }



  // element notations
  /**
   * @return {HTMLElement}
   */
  get scheduledContainer() {
    return this.element.querySelector('.h-s');
  }

  /**
   * Also known as the "length"
   * @return {HTMLElement}
   */
  get estimateContainer() {
    return this.element.querySelector('.h-l a');
  }
}
