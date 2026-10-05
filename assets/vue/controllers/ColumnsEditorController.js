import { ref, onMounted } from 'vue';
import ColumnItem from '../components/columnItem.js';

export default {
  components: {
    ColumnItem,
  },
  setup() {
    const columns = viewModel.rawColumns;
    const fixedColumns = viewModel.computedFixedColumns;
    const flexibleColumns = viewModel.computedFlexibleColumns;
    const hasNewColumn = viewModel.computedHasNew;


    onMounted(() => {
      window.dispatchEvent(new CustomEvent('ui-ready'));
    });

    return {
      hasNewColumn,
      fixedColumns,
      flexibleColumns,
      addModel: () => viewModel.add(),
    };
  },
  // language=vue
  template: `
<table class="table">
  <thead>
  <tr>
    <th class="h-name">Column Name</th>
    <th class="h-ishidden">hidden</th>
    <th class="h-controls">&nbsp;</th>
  </tr>
  </thead>

  <ColumnItem v-for="(column, idx) in fixedColumns" :column="column" :index="idx" :key="column.id" />
</table>

<table class="table h-columnist">
  <ColumnItem v-for="(column, idx) in flexibleColumns" :column="column" :index="idx" :key="column.id" />
</table>

<div class="row">
  <div class="col-lg-4 offset-lg-4 col-md-4 offset-md-4 col-sm-6 offset-sm-3 col-6 offset-3 text-center">
    <a href="#"
       id="h-add-model"
       @click.prevent="addModel"
       class="btn btn-success btn-block btn-sm"
       :class="{'disabled': hasNewColumn}"
       :disabled="hasNewColumn"
    ><i class="fa-solid fa-plus"></i> add column</a>
  </div>
</div>
`,
};
