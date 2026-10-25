import { ref, watch, computed } from 'vue';
import $ from 'jquery';

export const OPTIONS_NAME = '[[options]]';

/**
 * WARNING: reactive :D
 */
export default class Column {
  id = ref(-1);
  name = ref('MISSINGNO');
  position = ref(-1);
  hidden = ref(true);
  fixed = false;

  busy = ref(false);
  errors = ref(false);
  suspended = false;
  nextFocus = false;

  isOptionsColumn = computed(() => this.name.value === OPTIONS_NAME);

  constructor(id, name, pos, hidden, fixed) {
    this.id.value = id;
    this.name.value = name;
    this.position.value = pos;
    this.hidden.value = hidden || name === OPTIONS_NAME;
    this.fixed = !!fixed; // force boolean just in case

    this.#startChangeListener();

    // Old bug resulted in broken state options columns.
    // This force fixes it, even if users manually re-enable the checkbox.
    if (name === OPTIONS_NAME && !hidden) {
      this.updateColumn();
    }
  }

  #startChangeListener() {
    watch(this.name, () => {
      if (this.isOptionsColumn.value) {
        this.suspended = true;
        this.hidden.value = true;
        this.suspended = false;
      }

      this.updateColumn();
    });

    watch(this.hidden, (newVal, oldVal) => {
      // Prevent a user manually overwriting the hidden value.
      // It's jank, but works.
      if (this.name.value === OPTIONS_NAME && oldVal) {
        return;
      }

      this.updateColumn();
    });
  }

  move(direction) {
    const newPos = this.position.value + (direction === 'up' ? -1 : 1);

    viewModel.move(this.id.value, newPos);
  }

  async deleteColumn() {
    if (this.suspended) {
      return;
    }

    const data = {
      [csrfTokenName]: csrfToken,
    };

    this.busy.value = true;

    try {
      await fetch(
        `/-/schedules/${scheduleId}/columns/${this.id.value}?_method=DELETE`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(data),
        },
      );

      viewModel.remove(this);
    } finally {
      this.busy.value = false;
    }
  }

  async updateColumn() {
    if (this.suspended) {
      return;
    }

    const colId = this.id.value;
    const isNew = colId === -1;
    let url = `/-/schedules/${window.scheduleId}/columns`;

    if (this.fixed) {
      url += '/fixed';
    }

    if (!isNew) {
			url += `/${colId}?_method=PUT`;
		}

    const patchData = {
			name: this.name.value,
			hidden: this.hidden.value,
      [csrfTokenName]: csrfToken,
		};

    this.busy.value = true;

    try {
       const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(patchData),
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
      this.name.value = data.name;
      this.errors.value = false;

      this.suspended = false;

      if (this.nextFocus) {
        $('#h-add-model').focus();
        this.nextFocus = false;
      }

    } catch (error) {
      console.log(error);
      this.errors.value = error;
    } finally {
      this.busy.value = false;
    }
  }
}
