import ScheduleItem from '../components/ScheduleItem.js';

export default {
  components: {
    ScheduleItem,
  },
  props: [
    'numCols',
    'scheduledText',
    'estimateText',
  ],
  setup(props) {
    const items = viewModel.items;

    return {
      items,
      columns: window.columns,
    };
  },
  // language=vue
  template: `<div id="h-scheduler-container" style="display: block">
    <table :class="\`table h-l\${numCols}\`">
        <thead>
            <tr>
                <th class="h-s">{{ scheduledText }}</th>
                <th class="h-l">{{ estimateText }}</th>
                <th v-for="(column, idx) in columns" :key="idx" :class="\`h-\${idx}\`">
                    {{ column.name }}
                </th>
                <th class="h-co">&nbsp;</th>
            </tr>
        </thead>
    </table>

    <table :class="\`table h-scheduler h-l\${numCols}\`">
        <ScheduleItem v-for="(item, idx) in items" :item="item" :last="idx === items.length - 1" :numCols="numCols" :index="idx" :key="item.id" />

        <tbody v-if="!items.length">
            <tr>
                <td :colspan="numCols + 4" class="text-center active">
                    Click on the button below to create the first row in this schedule.
                </td>
            </tr>
        </tbody>

    </table>
</div>`,
}
