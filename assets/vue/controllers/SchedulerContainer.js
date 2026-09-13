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
  setup() {
    const items = viewModel.items;
    const hasNewItem = viewModel.hasNewItem;
    const isFull = viewModel.isFull;

    // TODO: editor auto next

    return {
      items,
      hasNewItem,
      isFull,
      columns: window.columns,
      addItem: () => viewModel.add(),
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

    (full: {{isFull}} | hasNew: {{hasNewItem}})
    <div class="row">
      <div
        class="col-lg-2 col-lg-offset-5 col-md-2 col-md-offset-5 col-sm-4 col-sm-offset-4 col-4 offset-4 text-center"
      >
        <a href="#"
           id="h-add-model"
           data-bind="click: add, activate: add"
           class="btn btn-success btn-block btn-sm"
           :class="{
            'disabled': isFull || hasNewItem,
           }"
           :disabled="isFull || hasNewItem"
           @click.prevent="addItem"
        >
          <i class="fa-solid fa-plus"></i> add row
        </a>
      </div>
    </div>
</div>`,
}
