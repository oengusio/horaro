import { computed, shallowRef } from 'vue';
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
  #columns = shallowRef([]);

  computedFixedColumns = computed(() => this.#columns.value.filter((col) => col.fixed === true));
  computedFlexibleColumns = computed(() => this.#columns.value.filter((col) => col.fixed === false));
  computedHasNew = computed(() => hasNewModel(this.#columns.value));

  computedIsFull = computed(() => this.#columns.value.filter(
    (col) => col.fixed === false && !col.hidden.value,
  ).length >= MAX_COLUMN);

  constructor(columns) {
    this.#columns.value = [...columns];
  }

  /**
   * @return {Column[]}
   */
  get columns() {
    return [...this.#columns.value];
  }

  /**
   * @param {Column[]} newVal
   */
  set columns(newVal) {
    this.#columns.value = [...newVal];
  }
}
