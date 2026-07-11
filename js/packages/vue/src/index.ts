import '@cluion/turing-element';
import { defineComponent, h, onMounted, ref } from 'vue';

/**
 * <Turing url type field autostart no-worker label… @solved @error /> —
 * thin wrapper around <turing-captcha>.
 */
export default defineComponent({
  name: 'Turing',
  props: {
    url: { type: String, required: true },
    type: { type: String, default: undefined },
    field: { type: String, default: undefined },
    autostart: { type: Boolean, default: false },
    noWorker: { type: Boolean, default: false },
    /** Shorthand idle checkbox label. */
    label: { type: String, default: undefined },
    labelLoading: { type: String, default: undefined },
    labelIdle: { type: String, default: undefined },
    labelSolving: { type: String, default: undefined },
    labelSolved: { type: String, default: undefined },
    labelError: { type: String, default: undefined },
    labelAria: { type: String, default: undefined },
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
    return () => {
      const attrs: Record<string, string> = {
        url: props.url,
      };
      if (props.type) attrs.type = props.type;
      if (props.field) attrs.field = props.field;
      if (props.autostart) attrs.autostart = '';
      if (props.noWorker) attrs['no-worker'] = '';
      if (props.label) attrs.label = props.label;
      if (props.labelLoading) attrs['label-loading'] = props.labelLoading;
      if (props.labelIdle) attrs['label-idle'] = props.labelIdle;
      if (props.labelSolving) attrs['label-solving'] = props.labelSolving;
      if (props.labelSolved) attrs['label-solved'] = props.labelSolved;
      if (props.labelError) attrs['label-error'] = props.labelError;
      if (props.labelAria) attrs['label-aria'] = props.labelAria;
      return h('turing-captcha', { ref: root, ...attrs });
    };
  },
});
