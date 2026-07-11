import '@cluion/turing-element';
import { defineComponent, h, onMounted, ref } from 'vue';

/**
 * <Turing url type field autostart no-worker @solved @error /> — renders the
 * turing-captcha custom element and forwards its events as Vue emits.
 */
export default defineComponent({
  name: 'Turing',
  props: {
    url: { type: String, required: true },
    type: { type: String, default: undefined },
    field: { type: String, default: undefined },
    autostart: { type: Boolean, default: false },
    noWorker: { type: Boolean, default: false },
  },
  emits: ['solved', 'error', 'ready'],
  setup(props, { emit }) {
    const root = ref<HTMLElement | null>(null);
    onMounted(() => {
      const el = root.value;
      if (!el) return;
      el.addEventListener('turing:solved', () => emit('solved'));
      el.addEventListener('turing:error', (e) => emit('error', (e as CustomEvent).detail?.error));
      el.addEventListener('turing:ready', () => emit('ready'));
    });
    return () =>
      h('turing-captcha', {
        ref: root,
        url: props.url,
        type: props.type,
        field: props.field,
        // Boolean attributes: presence means true on the custom element.
        ...(props.autostart ? { autostart: '' } : {}),
        ...(props.noWorker ? { 'no-worker': '' } : {}),
      });
  },
});
