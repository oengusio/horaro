import { ref } from 'vue';

const focus = {
  mounted: (el) => el.focus()
}

export default {
  props: ['modelValue'],
  emits: ['update:modelValue'],
   directives: {
    // enables v-focus in template
    focus,
  },
  setup(props, { emit }) {
    const isOpen = ref(false);
    const inputCache = ref(props.modelValue);

    function saveValue() {
      emit('update:modelValue', inputCache.value)
      isOpen.value = false;
    }

    function cancelEdit() {
      inputCache.value = props.modelValue;
      isOpen.value = false;
    }

    return {
      isOpen,
      inputCache,
      saveValue,
      cancelEdit,
    };
  },

  // language=vue
  template: `<div class="h-editor">
    <div class="input-holder" v-if="isOpen">
      <input v-focus type="text" name="input" v-model.trim="inputCache" @keydown.enter="saveValue" @keydown.esc="cancelEdit" />
      <button type="button" @click.prevent="saveValue">save</button>
      <button type="button" @click.prevent="cancelEdit">cancel</button>
    </div>

    <a v-else href="#" @click.prevent="isOpen = true" class="editable-click" :class="{ 'editable-empty': !inputCache }">
      {{ inputCache || 'Empty' }}
    </a>
  </div>`,
};
