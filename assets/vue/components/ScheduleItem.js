import { ref, computed } from 'vue';


export default {
  props: [
    'item',
    'index',
  ],
  setup(props) {
    const busy = ref(false);
    const errors = ref(false);
    const deleting = ref(false);
    const expanded = ref(false);

    // static values
    const formattedSchedule = ref('aaaa');
    const formattedLength = ref('PT10M');

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

    return {
      formattedSchedule,
      formattedLength,
      bodyClass: 'h-item ' + (props.index % 2 === 1 ? 'h-odd' : 'h-even'),
      rowClass,
      expanded,
      columns: window.columns,
      getDisplayText,
    };
  },
  template: `
<tbody :class="bodyClass" draggable="false">
    <tr class="h-primary">
        <td :class="\`h-s \${rowClass}\`">{{ formattedSchedule }}</td>
        <td :class="\`h-l \${rowClass}\`">
            <a href="#">{{ formattedSchedule }}</a>
        </td>
        <td :class="\`h-\${idx} \${rowClass}\`" v-for="(col, idx) in columns" :key="col.id"">
            <a href="#" :id="\`col_\${col.id}\`">{{ getDisplayText(col.id) }}</a>
        </td>
    </tr>

    <tr class="h-secondary" v-if="expanded"></tr>
</tbody>
  `,
};
