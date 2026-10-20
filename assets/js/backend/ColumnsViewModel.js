import { computed, shallowRef, watch } from 'vue';
import { hasNewModel } from '../utils/itemUtils.js';
import Column from './Column.js';

const MAX_COLUMN = 10;
// const MAX_COLUMN = 9;

/**
 * WARNING: reactive :D
 */
export default class ColumnsViewModel {
  /**
   * @type {{ value: Column[] }}
   */
  rawColumns = shallowRef([]);

  computedFixedColumns = computed(() => this.rawColumns.value.filter((col) => col.fixed === true));
  computedFlexibleColumns = computed(() => this.rawColumns.value.filter((col) => col.fixed === false));
  computedHasNew = computed(() => hasNewModel(this.rawColumns.value));

  computedNumFlexColumns = computed(() => this.computedFlexibleColumns.value.length);

  // TODO: seems to have some whacky behaviour where it counts 9 instead of 10.
  // This could also be weird backend behaviour, haven't quite figured that out
  computedIsFull = computed(() => this.rawColumns.value.filter((col) => col.fixed === false && col.hidden.value === false).length >= MAX_COLUMN);

  // TODO: this can probably be optimised
  computedIsMinimal = computed(() => {
    const cols = this.rawColumns.value;
    let acc = 0;

    for (let i = 0; i < cols.length; i++) {
      if (cols[i].fixed === false && cols[i].id.value !== -1) {
        acc++;
      }
    }

    return acc <= 1;
  });

  constructor(columns) {
    this.rawColumns.value = columns;

    watch(this.rawColumns, () => {
      let pos = 1;

      this.rawColumns.value.forEach((col) => {
        if (!col.fixed) {
          col.position.value = pos;
          pos++;
        }
      });
    });
  }

  /**
   * @return {Column[]}
   */
  get columns() {
    return [...this.rawColumns.value];
  }

  /**
   * @param {Column[]} newVal
   */
  set columns(newVal) {
    this.rawColumns.value = [...newVal];
  }

  add() {
    const isFull = this.computedIsFull.value;
    const newCol = new Column(-1, '', this.computedNumFlexColumns.value + 1, isFull, false);

    // Insert column the reactive way
    const curCols = this.columns;
    curCols.push(newCol);
    this.columns = curCols;

    newCol.name.value = 'New Column'; // trigger storing the column immediately
  }

  remove(col) {
    const items = this.columns;

    items.splice(items.indexOf(col), 1);
    this.columns = items;
  }

  async move(columnId, newPos) {
    const cols = this.columns;
    const col = cols.find((it) => it.id.value === columnId);

    if (!col) {
      //wat?
      return;
    }

    const oldPos = col.position.value;

    // illegal move
    if (newPos < 1 || newPos > this.computedNumFlexColumns.value) {
      return;
    }

    // Even if we don't actually move the column, we need to re-generate a fresh tbody element
    // because the old one was detached from the DOM during the dragging.

    const insertAt = newPos + this.computedFixedColumns.value.length - 1; // -1 because splice() uses the internal, 0-based array

    cols.splice(cols.indexOf(col), 1);
    cols.splice(insertAt, 0, col);

    this.columns = cols;

    // Now we can stop.
		if (oldPos === newPos) {
			return;
		}

    const data = {
      column: columnId,
      position: newPos,
      [csrfTokenName]: csrfToken,
    };

    col.busy.value = true;

    await fetch(`/-/schedules/${scheduleId}/columns/move`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(data),
    });

    col.busy.value = false;
  }

  initDragAndDrop() {
    console.log('TODO: Implement drag and drop for columnist');
  }
}
