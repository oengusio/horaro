import {Controller} from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    id: String,
    start: String,
    setupTime: Number, // in seconds
    timeZone: String,
    columns: Array,
    maxItems: Number,
    itemData: Array,
  };
  static outlets = ['schedule-item'];
  static targets = [
    // 'item',
    'itemTemplate',
    'empty',
  ];


  #items = [];

  connect() {
    // this.element.textContent = 'Hello Stimulus!';
    console.log('items', this.itemDataValue);
    console.log('columns', this.columnsValue);
    console.log(`Max items: ${this.maxItemsValue}`);

    this.#items = [];

    this.initItems();

    console.log(this.#items);

    console.log(this.emptyTarget);

    // console.log('OUTLETS', this.scheduleItemOutlets);

    this.syncItemTemplates();

    // todo: actual logic
    this.loadCompleted();
  }

  scheduleItemOutletConnected(outlet, element) {
    // console.log('An item outlet was added', element)
  }

  scheduleItemOutletDisconnected(outlet, element) {
    //
  }


  initItems() {
    //
  }

  loadCompleted() {
    // this.itemsUpdated();

    document.querySelector('#h-scheduler-loading').style.display = 'none';
    this.element.parentNode.style.display = 'block';
  }

  itemsUpdated() {
    this.emptyTarget.hidden = this.#items.length > 0;
  }

  syncItemTemplates() {
    // data-schedule-item-item-value

    const target = this.element;
    const template = this.itemTemplateTarget;

    for (const itemData of this.itemDataValue) {
      const clone = document.importNode(template.content, true);

      const tbody = clone.querySelector('tbody');

      tbody.dataset.scheduleItemItemValue = JSON.stringify(itemData);

      target.appendChild(clone);
    }
  }
}
