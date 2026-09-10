import { ref, useTemplateRef } from 'vue';

const focus = {
  mounted: (el) => el.focus(),
};

const select = {
  mounted: (el) => {
    // hack :D
    setTimeout(() => {
      el.select();
    }, 1);
  },
};

export default {
  props: ['modelValue'],
  emits: ['update:modelValue'],
   directives: {
    // enables v-focus in template
    focus,
    select,
  },
  setup(props, { emit }) {
    const isOpen = ref(false);
    const inputCache = ref(props.modelValue);
    const displayValue = ref(props.modelValue);
    const inputRef = useTemplateRef('input');

    function saveValue() {
      emit('update:modelValue', inputCache.value)
      displayValue.value = inputCache.value;
      isOpen.value = false;
    }

    function cancelEdit() {
      inputCache.value = props.modelValue;
      isOpen.value = false;
    }

    function clearValue() {
      inputCache.value = '';
      inputRef.value.focus();
    }

    return {
      displayValue,
      isOpen,
      inputCache,
      saveValue,
      cancelEdit,
      clearValue,
    };
  },

  // language=vue
  template: `<div class="h-editor">
    <div class="input-holder editableform" v-if="isOpen">
      <div class="editable-input">
        <input v-focus v-select ref="input" type="text" name="input" v-model.trim="inputCache" @keydown.enter="saveValue" @keydown.esc="cancelEdit" />
        <button type="button" v-show="inputCache" @click.prevent="clearValue" class="btn btn-sm btn-link"><i class="fa-solid fa-circle-xmark"></i></button>
      </div>

      <div class="editable-buttons">
        <button type="button" class="btn btn-primary btn-sm" @click.prevent="saveValue"><i class="fa-solid fa-check"></i></button>
        <button type="button" class="btn btn-secondary btn-sm" @click.prevent="cancelEdit"><i class="fa-solid fa-ban"></i></button>
      </div>
    </div>

    <a href="#" :disabled="isOpen" @click.prevent="isOpen = true" class="editable-click" :class="{
      'editable-empty': !displayValue,
      'disabled': isOpen,
    }">
      {{ displayValue || 'Empty' }}
    </a>
  </div>`,
};
