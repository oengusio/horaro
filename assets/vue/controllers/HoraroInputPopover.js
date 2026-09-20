import { ref, nextTick, useTemplateRef } from 'vue';

const selectAndFocus = {
  /**
   * @param {HTMLInputElement} el
   */
  mounted: (el) => {
    el.focus();
    window.requestAnimationFrame(() => {
      el.select();
    });
  },
};

export default {
  directives: {
    // enables v-select-and-focus in template
    selectAndFocus,
  },
  setup() {
    const currentEditId = ref(null);
    const inputCache = ref('');
    const isOpen = ref(false);
    const itemTop = ref(0);
    const arrowLeft = ref(0);
    const inputRef = useTemplateRef('input');

    window.addEventListener('inputTrigger', (e) => {
      const { content, id, element } = e.detail;

      isOpen.value = false;

      nextTick(() => {
        currentEditId.value = id;
        inputCache.value = content;

        const rect = element.getBoundingClientRect();

        itemTop.value = rect.top - 70;
        arrowLeft.value = rect.left;
        isOpen.value = true;
      });
    });

    function cancelEdit() {
      isOpen.value = false;
      const storedEditorId = `${currentEditId.value}`;
      currentEditId.value = ''

      nextTick(() => {
        window.dispatchEvent(new CustomEvent(
          'editorCanceled',
          {
            detail: {
              id: storedEditorId,
            },
          },
        ));
      });
    }

    function clearValue() {
      inputCache.value = '';
      nextTick(() => {
        inputRef.value.focus();
      });
    }

    function saveValue() {
      // prevent edits when we don't know where they go
      if (!currentEditId.value) {
        return;
      }

      // clone it
      const storedValue = `${inputCache.value}`;
      const storedEditorId = `${currentEditId.value}`;
      isOpen.value = false;
      currentEditId.value = ''

      nextTick(() => {
        window.dispatchEvent(new CustomEvent(
          'editorSaved',
          {
            detail: {
              id: storedEditorId,
              content: storedValue,
            },
          },
        ));
      });
    }

    return {
      inputCache,
      currentEditId,
      clearValue,
      saveValue,
      cancelEdit,
      isOpen,
      itemTop,
      arrowLeft,
    };
  },
  // language=vue
  template: `
    <div class="h-popover-editor editableform" v-if="isOpen" :style="{
      top: \`\${itemTop}px\`,
    }">
      <div class="editable-input">
        <input v-select-and-focus
               ref="input"
               type="text"
               name="input"
               v-model.trim="inputCache"
               @keydown.enter="saveValue"
               @keydown.esc="cancelEdit"
        />
        <button type="button"
                v-show="inputCache"
                @click.prevent="clearValue"
                class="btn btn-sm btn-link"
        >
          <i class="fa-solid fa-circle-xmark"></i>
        </button>
      </div>

      <div class="editable-buttons">
        <button type="button"
                class="btn btn-primary btn-sm"
                @click.prevent="saveValue"
        >
          <i class="fa-solid fa-check"></i>
        </button>
        <button type="button"
                class="btn btn-secondary btn-sm"
                @click.prevent="cancelEdit"
        >
          <i class="fa-solid fa-ban"></i>
        </button>
      </div>

      <span class="arrow" :style="{
        left: \`\${arrowLeft}px\`,
      }"></span>
    </div>
  `,
};
