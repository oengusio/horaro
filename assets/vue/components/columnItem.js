import { ref, computed } from 'vue';

export default {
  props: ['column', 'index'],
  setup(props) {
     /**
     * @typedef {import('../../js/backend/Column.js')}
     * @var {Column}
     */
    const column = props.column;
    const busy = column.busy;
    const fixed = column.fixed;
    const hidden = column.hidden;
    const isOptionsColumn = column.isOptionsColumn;
    const isFull = window.viewModel.computedIsFull;

    const deleting = ref(false);

    const rowClass = computed(() => {
      if (busy.value) {
        return 'bg-warning';
      }

      if (column.errors.value) {
        return 'bg-danger h-has-errors';
      }

      if (deleting.value) {
        return 'bg-danger';
      }

      return '';
    });

    const deleteButtonDisabled = computed(() => {
      return column.fixed || column.id.value === -1 || window.viewModel.computedIsMinimal.value;
    });

    return {
      column,
      isFull,
      isOptionsColumn,
      bodyClass: 'h-column ' + (props.index % 2 === 1 ? 'h-odd' : 'h-even'),
      rowClass,
      fixed,
      hidden,
      deleting,
      deleteButtonDisabled,
    };
  },
  // language=vue
  template: `
  <tbody :class="bodyClass" draggable="false">
    <tr class="h-primary">
      <td :class="\`h-name \${rowClass}\`">
        {{ column.name }}
      </td>
      <td :class="\`h-ishidden \${rowClass}\`">
        <input v-if="!fixed"
               type="checkbox"
               v-model="hidden"
               :disabled="isOptionsColumn || (hidden && isFull)"
        />
      </td>
      <td :class="\`text-right h-controls \${rowClass}\`">
        <template v-if="deleting">
          <button class="btn btn-danger btn-sm"
                  data-bind="click: doDelete, activate: doDelete">
            <i class="fa-solid fa-trash"></i>
          </button>
          <button class="btn btn-secondary btn-sm"
                  @click.prevent="deleting = false;"
          >
            <i class="fa-solid fa-rotate-left"></i>
          </button>
        </template>
        <template v-else>
          <template v-if="!fixed">
            <button
              class="btn btn-move-up btn-sm btn-secondary"
              data-bind="click: moveUp, activate: moveUp, attr: { class: 'btn move-up btn-sm' + (first() && ' disabled' || ' btn-secondary') }">
              <i class="fa-solid fa-arrow-up"></i>
            </button>
            <button
              class="btn btn-move-down btn-sm btn-secondary"
              data-bind="click: moveDown, activate: moveDown, attr: { class: 'btn move-down btn-sm' + (last() && ' disabled' || ' btn-secondary') }">
              <i class="fa-solid fa-arrow-down"></i>
            </button>
          </template>
          <button
            @click.prevent="deleting = true;"
            class="btn btn-danger btn-sm"
            :class="{'disabled': deleteButtonDisabled}"
            :disabled="deleteButtonDisabled"
          >
            <i class="fa-solid fa-trash"></i>
          </button>
        </template>
      </td>
    </tr>
  </tbody>
`,
}
