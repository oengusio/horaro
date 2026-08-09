import { Controller } from '@hotwired/stimulus';

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
  static outlets = [ 'item' ];
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

    console.log('OUTLETS', this.itemOutlets);

    // this.syncItemTemplates();

    // todo: actual logic
    this.loadCompleted();
  }

  itemOutletConnected(outlet, element) {
    console.log('An item outlet was added', element)
  }

  itemOutletDisconnected(outlet, element) {
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

    const clone = document.importNode(template.content, true);

    // TODO: figure this out
    // clone.dataset.scheduleItemItemValue = JSON.stringify(this.itemDataValue[0]);

    target.appendChild(clone);

    //
  }
}
