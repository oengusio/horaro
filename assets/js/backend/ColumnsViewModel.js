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

  computedNumFlexColumns = computed(() => this.computedFixedColumns.value.length);

  // TODO: seems to have some whacky behaviour where it counts 9 instead of 10.
  // This could also be weird backend behaviour, haven't quite figured that out
  computedIsFull = computed(() => this.rawColumns.value.filter(
    (col) => col.fixed === false && col.hidden.value === false,
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
