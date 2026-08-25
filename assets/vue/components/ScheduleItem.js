import { ref, computed } from 'vue';


export default {
  props: [
    'item',
    'index',
    'last',
    'position',
    'numCols',
  ],
  setup(props) {
    const busy = ref(false);
    const errors = ref(false);
    const deleting = ref(false);
    const expanded = ref(true);

    // TODO dateSwitch
    // TODO: inline editor component

    // static values
    const formattedSchedule = ref('aaaa');
    const formattedLength = ref('PT10M');

    const position = computed(() => props.index + 1);
    const first = computed(() => props.index === 0);

    const rowClass = computed(() => {
      if (busy.value) {
        return 'bg-warning';
      }

      if (errors.value) {
        return 'bg-danger h-has-errors';
      }

      if (deleting.value) {
        return 'bg-danger';
      }

      return '';
    });

    function getDisplayText(colId) {
      if (!props.item[2]) {
        return 'Missing data?';
      }

      return props.item[2][colId];
    }

    function doDelete() {}

    function move(direction) {
        const newPos = position.value + (direction === 'up' ? -1 : 1);
    }

    return {
      formattedSchedule,
      formattedLength,
      bodyClass: 'h-item ' + (props.index % 2 === 1 ? 'h-odd' : 'h-even'),
      rowClass,
      expanded,
      position,
      first,
      columns: window.columns,
      getDisplayText,
      move,
      doDelete,
      deleting,
    };
  },
  // language=vue
  template: `
<tbody :class="bodyClass" draggable="true">
    <tr class="h-primary">
        <td class="h-s" :class="rowClass">{{ formattedSchedule }}</td>
        <td class="h-l" :class="rowClass">
            <a href="#">{{ formattedLength }}</a>
        </td>
        <td :class="\`h-\${idx} \${rowClass}\`" v-for="(col, idx) in columns" :key="col.id">
            <a href="#"
               :id="\`col_\${col.id}\`"
            >{{ getDisplayText(col.id) }}</a>
        </td>

      <td class="h-co text-right" :class="rowClass">
        <template v-if="deleting">
          <button class="btn btn-danger btn-sm"
                  data-bind="click: doDelete, activate: doDelete"><i class="fa-solid fa-trash"></i></button>
          <button @click.prevent="deleting = false"
                  class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i></button>
        </template>
        <template v-else>
          <span>
            <button v-if="expanded"
                    @click.prevent="expanded = false"
                    class="btn btn-link btn-sm">
              <i class="fa-solid fa-angles-up"></i> less</button>
            <button v-else
                    @click.prevent="expanded = true"
                    class="btn btn-link btn-sm"><i class="fa-solid fa-angles-down"></i> more</button>
          </span>
          <button
            class="btn move-up btn-sm"
            :class="{ 'disabled': first, 'btn-secondary': !first }"
            @click.prevent="move('up')">
            <i class="fa-solid fa-arrow-up"></i></button>
          <button
            class="btn move-down btn-sm"
            @click.prevent="move('down')"
            :class="{ 'disabled': last, 'btn-secondary': !last }">
            <i class="fa-solid fa-arrow-down"></i></button>
          <button
            @click.prevent="deleting = true"
            class="btn btn-danger btn-sm"
            :class="{ disabled: item.id === -1 }">
            <i class="fa-solid fa-trash"></i></button>
        </template>
      </td>

    </tr>

    <tr class="h-secondary" v-if="expanded">
      <td :colspan="numCols + 3">
        <dl class="row">
          <template v-for="(col, idx) in columns.slice(1)">
            <dt :class="\`h-e-\${idx + 1} col-2 h-np\`">{{ col.name }}:</dt>
            <dd :class="\`h-e-\${idx + 1} col-10 h-np\`">
              <a href="#"
                 data-bind="editable: col_{{ column.id|obscurify('schedule.column') }}, editableOptions: { hidden: onEditableHidden, display: getDisplayText }">
                {{ getDisplayText(col.id) }}
              </a>
            </dd>
          </template>
        </dl>
      </td>
    </tr>
</tbody>
  `,
};
