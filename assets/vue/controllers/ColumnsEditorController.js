import { ref } from 'vue';

export default {
  setup() {
    const hasNewColumn = ref(true);

    function addModel() {
      //
    }

    return {
      hasNewColumn,
      addModel: () => addModel(),
    };
  },
  // language=vue
  template: `
<div class="row">
  <div class="col-lg-5 col-md-6 col-sm-6" id="h-columnist-container">
    <table class="table">
      <thead>
      <tr>
        <th class="h-name">Column Name</th>
        <th class="h-ishidden">hidden</th>
        <th class="h-controls">&nbsp;</th>
      </tr>
      </thead>

      <!-- ko foreach: fixedColumns -->
      {% include 'schedule/column.twig' with {'schedule': schedule, 'columns': schedule.columns} %}
      <!-- /ko -->
    </table>

    <table class="table h-columnist" data-id="{{ schedule.id|obscurify('schedule') }}">
      <!-- ko foreach: flexibleColumns -->
      {% include 'schedule/column.twig' with {'schedule': schedule, 'columns': schedule.columns} %}
      <!-- /ko -->
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
  </div>
</div>
`,
};
