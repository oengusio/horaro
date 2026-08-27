import { computed, shallowReactive } from 'vue';
import { hasNewModel } from '../utils/itemUtils.js';
import { ReadableTime } from '../readableTimeJs.js';
import moment from 'moment';

/**
 * WARNING: reactive :D (also cursed LOL)
 */
export default class ItemsViewModel {
  /**
   * @type {Item[]}
   */
  items = shallowReactive([]);
  maxItems = 50;

  #computedHasNewItem = computed(() => hasNewModel(this.items));
  #computedIsFull = computed(() => this.items.length >= maxItems);

  /**
   * @param {Item[]} items
   */
  constructor(items) {
    this.items = shallowReactive(items);

    this.calculateSchedule();
  }

  get hasNewItem() {
    return this.#computedHasNewItem;
  }

  get isFull() {
    return this.#computedIsFull;
  }

  calculateSchedule(startIdx = 0) {
    const items = this.items;
    let start;

    if (startIdx === 0) {
      start = scheduleStart.getTime();
    } else {
      start = items[startIdx].scheduled.value + (items[startIdx].length.value * 1000);
    }

    let scheduled = start;
    let prev = null;

    for (let i = startIdx, len = items.length; i < len; i++) {
      const item = items[i];

      if (optionsColumnId) {
				const columnId = 'col_' + optionsColumnId
        const optionsValue = item[columnId].value;

        if (optionsValue) {
          try {
            const { setup } = JSON.parse(optionsValue);

            if (setup) {
              item.setupTime.value = ReadableTime.parse(setup);
            }
          } catch (ignored) {
            // We are being little shits and silently ignoring user errors
          }
        } else {
          item.setupTime.value = 0;
        }
      }

      item.scheduled.value = scheduled;
      item.dateSwitch.value = false;

      const date = moment.unix(scheduled / 1000).utcOffset(scheduleTZ);
      const dayOfYear = date.dayOfYear();
      const pickedSetupTime = item.setupTime.value || scheduleSetupTime;

      scheduled += ((item.length.value + pickedSetupTime) * 1000);

      if (prev !== null && prev !== dayOfYear) {
				item.dateSwitch.value = date.format('dddd, ll');
			}

			prev = dayOfYear;
    }
  }
}
