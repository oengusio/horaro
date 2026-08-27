import { ref, computed } from 'vue';
import moment from 'moment';
// imported for IDE type hinting
import Item from '../../js/backend/Item.js';
import { parseLength } from '../../js/utils/itemUtils.js';


export default {
  props: [
    'item',
    'index',
    'last',
    'position',
    'numCols',
  ],
  setup(props) {
    /**
     * @var {Item}
     */
    const item = props.item;
    const busy = item.busy;
    const errors = ref(false);
    const deleting = ref(false);
    const expanded = ref(true);

    // TODO: inline editor component

    // static values
    const formattedSchedule = computed(() => {
      return moment.unix(item.scheduled.value / 1000).utcOffset(scheduleTZ).format('LT');
    });
    // TODO: https://vuejs.org/guide/essentials/computed.html#writable-computed
    const formattedLength = computed({
      get() {
        return moment.unix(item.length.value).utc().format('HH:mm:ss');
      },
      set(newValue) {
        item.length.value = parseLength(newValue);
      },
    });

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
      return props.item[`col_${colId}`]?.value;
    }

    function doDelete() {}

    function move(direction) {
        const newPos = item.position.value + (direction === 'up' ? -1 : 1);
    }

    return {
      item,
      formattedSchedule,
      formattedLength,
      bodyClass: 'h-item ' + (props.index % 2 === 1 ? 'h-odd' : 'h-even'),
      rowClass,
      expanded,
      position: item.position,
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
    <tr class="h-new-day" v-if="item.dateSwitch.value">
      <td :colspan="numCols + 4">{{ item.dateSwitch }}</td>
    </tr>

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
