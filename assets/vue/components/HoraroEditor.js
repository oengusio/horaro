import { ref, useTemplateRef } from 'vue';

export default {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const customId = crypto.randomUUID();
    const displayValue = ref(props.modelValue);
    const targetEl = useTemplateRef('targetEl');

    window.addEventListener('editorSaved', (e) => {
      const { id, content } = e.detail;

      if (id === customId) {
        emit('update:modelValue', content);
        displayValue.value = content;
      }
    });

    function openEditor() {
      window.dispatchEvent(new CustomEvent(
        'inputTrigger',
        {
          detail: {
            id: customId,
            content: displayValue.value,
            element: targetEl.value,
          },
        },
      ));
    }

    return {
      displayValue,
      openEditor,
    };
  },

  // language=vue
  template: `<div class="h-editor">
    <!-- TODO: render markdown -->
    <a href="#" ref="targetEl" @click.prevent="openEditor" class="editable-click" :class="{
      'editable-empty': !displayValue,
    }">
      {{ displayValue || 'Empty' }}
    </a>
  </div>`,
};
