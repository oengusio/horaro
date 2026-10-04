import { ref, watch } from 'vue';

export default class Column {
  id = ref(-1);
  name = ref('MISSINGNO');
  position = ref(-1);
  hidden = ref(true);
  fixed = false;

  busy = ref(false);
  errors = ref(false);
  suspended = false;
  nextFocus = false;

  constructor(id, name, pos, hidden, fixed) {
    this.id.value = id;
    this.name.value = name;
    this.position.value = pos;
    this.hidden.value = hidden;
    this.fixed = !!fixed; // force boolean just in case

    this.#startChangeListener();
  }

  #startChangeListener() {
    //
  }
}
