import { computed, shallowRef, watch } from 'vue';
import { hasNewModel } from '../utils/itemUtils.js';
import Column from './Column.js';

const MAX_COLUMN = 10;

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

  computedNumFlexColumns = computed(() => this.computedFixedColumns.value.length);

  computedIsFull = computed(() => this.rawColumns.value.filter(
    (col) => col.fixed === false && !col.hidden.value,
  ).length >= MAX_COLUMN);

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
      })
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

  initDragAndDrop() {
    console.log('TODO: Implement drag and drop for columnist');
  }
}
