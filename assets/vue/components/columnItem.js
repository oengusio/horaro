import { ref, computed } from 'vue';
import HoraroEditor from './HoraroEditor.js';

export default {
  components: {
    HoraroEditor,
  },
  props: ['column', 'index', 'last'],
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

    function doDelete() {
      column.deleteColumn();
    }

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
      move: (pos) => column.move(pos),
      doDelete,
    };
  },
  // language=vue
  template: `
  <tbody :class="bodyClass" draggable="false">
    <tr class="h-primary">
      <td :class="\`h-name \${rowClass}\`">
        <HoraroEditor
          :id="\`col_\${column.id.value}\`"
          v-model="column.name.value"
        />
      </td>
      <td :class="\`h-ishidden \${rowClass}\`">
        <input v-if="!fixed"
               type="checkbox"
               v-model="hidden"
               :disabled="isOptionsColumn || (hidden && isFull) || deleting"
        />
      </td>
      <td :class="\`text-right h-controls \${rowClass}\`">
        <template v-if="deleting">
          <button class="btn btn-danger btn-sm"
                  @click.prevent="doDelete"
          >
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
              :disabled="index === 0"
              :class="{ 'disabled': index === 0 }"
              @click.prevent="move('up')"
            >
              <i class="fa-solid fa-arrow-up"></i>
            </button>
            <button
              class="btn btn-move-down btn-sm btn-secondary"
              :disabled="last"
              :class="{ 'disabled': last }"
              @click.prevent="move('down')"
            >
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
