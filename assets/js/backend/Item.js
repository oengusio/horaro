import { ref, watch } from 'vue';

/**
 * WARNING: this shit is reactive
 */
export default class Item {
  id = ref(-1);
  length = ref(0);
  scheduled = ref(0); // will be set by ViewModel.calculateSchedule()
  dateSwitch = ref(false); // will be set by ViewModel.calculateSchedule()
  setupTime = ref(0); // will be set by ViewModel.calculateSchedule()

  position = ref(0);
  busy = ref(false);
  errors = ref(false);
  suspended = false;
  nextFocus = false;

  /**
   *
   * @param id
   * @param length
   * @param columns
   * @param pos
   */
  constructor(id, length, columns, pos) {
    this.id.value = id;
    this.length.value = length;

    scheduleColumns.forEach(({ id: colId }) => {
      const name = `col_${colId}`;
      let value = '';

      if (columns.hasOwnProperty(colId)) {
        value = columns[colId];
      }

      // TODO: make this less cursed :D
      this[name] = ref(value);

      // and make sure to watch for changes
      watch(this[name], (newValue) => {
        this.save({
          columns: {
            [colId]: newValue,
          },
        });
      });
    });

    this.position.value = pos;

    watch(this.id, (newValue, oldValue) => {
      console.log(`ID UPDATED (is ${newValue}, was ${oldValue})`);
    })

    watch(this.length, (newValue) => {
      this.save({ length: newValue });
      viewModel.calculateSchedule(0);
    });
  }

  move(direction) {
    //
  }

  deleteItem() {
    //
  }

  // Old method name "sync"
  async save(patch) {
    if (this.suspended) {
      return;
    }

    const itemId = this.id.value;
    const isNew = itemId === -1;
    let url = '';

    if (isNew) {
      url = `/-/schedules/${scheduleId}/items`;

      patch = {
        length: this.length.value,
        columns: {},
      };

      scheduleColumns.forEach(({ id: colId }) => {
        const name = `col_${colId}`;

        patch.columns[name] = this[name].value;
      });
    } else {
      url = `/-/schedules/${scheduleId}/items/${itemId}?_method=PATCH`;
    }

    this.busy.value = true;

    patch[csrfTokenName] = csrfToken;

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(patch),
      });
      const jsonData = await response.json();

      // TODO: proper status code validation, this will do for now
      if (response.status > 299) {
        this.errors.value = jsonData.detail;
        return;
      }

      const { data } = jsonData;

      this.suspended = true;
      this.id.value = data.id;
      this.length.value = data.length;
      this.errors.value = false;

      scheduleColumns.forEach(({ id: colId }) => {
        const name = `col_${colId}`;

        this[name].value = colId in data.columns ? data.columns[colId] : '';
      });

      this.suspended = false;

      // TODO: next focus? Figure out what that is
      /*if (self.nextFocus) {
        $('#h-add-model').focus();
        self.nextFocus = false;
      }*/

    } catch (error) {
      console.log(error);
      this.errors.value = error;
    } finally {
      this.busy.value = false;
    }
  }
}
