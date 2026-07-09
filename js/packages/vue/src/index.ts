import '@cluion/turing-element';
import { defineComponent, h, onMounted, ref } from 'vue';

/**
 * <Turing url type field @solved @error /> — renders the turing-captcha custom
 * element and forwards its events as Vue emits. Delegates all behavior to the
 * element; this is prop/event translation only.
 */
export default defineComponent({
  name: 'Turing',
  props: {
    url: { type: String, required: true },
    type: { type: String, default: undefined },
    field: { type: String, default: undefined },
  },
  emits: ['solved', 'error'],
  setup(props, { emit }) {
    const root = ref<HTMLElement | null>(null);
    onMounted(() => {
      const el = root.value;
      if (!el) return;
      el.addEventListener('turing:solved', () => emit('solved'));
      el.addEventListener('turing:error', (e) => emit('error', (e as CustomEvent).detail?.error));
    });
    return () => h('turing-captcha', { ref: root, url: props.url, type: props.type, field: props.field });
  },
});
