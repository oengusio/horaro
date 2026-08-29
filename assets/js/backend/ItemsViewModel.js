import { computed, shallowRef } from 'vue';
import { hasNewModel } from '../utils/itemUtils.js';
import { ReadableTime } from '../readableTimeJs.js';
import moment from 'moment';

/**
 * WARNING: reactive :D (also cursed LOL)
 */
export default class ItemsViewModel {
  items = shallowRef([]);
  maxItems = 50;

  #computedHasNewItem = computed(() => hasNewModel(this.items.value));
  #computedIsFull = computed(() => this.items.value.length >= maxItems);

  /**
   * @param {Item[]} items
   */
  constructor(items) {
    this.items.value = items;

    this.calculateSchedule();
  }

  get hasNewItem() {
    return this.#computedHasNewItem;
  }

  get isFull() {
    return this.#computedIsFull;
  }

  async move(itemId, newPos) {
    const items = [...this.items.value];
    const item = items.find((item) => item.id.value === itemId);
    const data = {
      item: itemId,
      position: newPos,
    };
    const oldPos = item.position.value;

    // illegal move
		if (newPos < 1 || newPos > items.length) {
			return;
		}

    // Even if we don't actually move the item, we need to re-generate a fresh tbody element
		// because the old one was detached from the DOM during the dragging.

		const insertAt = newPos - 1; // -1 because splice() uses the internal, 0-based array

		items.splice(items.indexOf(item), 1);
		items.splice(insertAt, 0, item);

		// Now we can stop.
		if (oldPos === newPos) {
			return;
		}

		data[csrfTokenName] = csrfToken;

    item.busy.value = true;

    await fetch(`/-/schedules/${scheduleId}/items/move`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(data),
    });

    // reset positions
    let pos = 1;
    items.forEach((itm) => {
      itm.position.value = pos;
      pos++;
    });

    item.busy.value = false;
    this.items.value = [...items];
  }

  calculateSchedule(startIdx = 0) {
    const items = this.items.value;
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
