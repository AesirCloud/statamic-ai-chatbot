import { _ as _export_sfc, C as CodeEditor, F as Fieldtype, r as resolveComponent, o as openBlock, c as createBlock } from "./index-DwP14X7s.js";
const _sfc_main = {
  mixins: [Fieldtype],
  components: { CodeEditor },
  data() {
    return {
      escBinding: null
    };
  },
  computed: {
    mode() {
      return this.value.mode || this.config.mode;
    },
    replicatorPreview() {
      if (!this.showFieldPreviews) return;
      return this.value.code ? truncate(this.value.code, 60) : "";
    },
    internalFieldActions() {
      return [
        {
          title: __("Toggle Fullscreen Mode"),
          icon: ({ vm }) => vm.$refs.codeEditor.fullScreenMode ? "fullscreen-close" : "fullscreen-open",
          quick: true,
          visible: this.config.fullscreen,
          visibleWhenReadOnly: true,
          run: ({ vm }) => vm.toggleFullscreen()
        }
      ];
    }
  },
  methods: {
    toggleFullscreen() {
      const wasFullscreen = this.$refs.codeEditor.fullScreenMode;
      this.$refs.codeEditor.toggleFullscreen();
      if (wasFullscreen) {
        if (this.escBinding) {
          this.escBinding.destroy();
          this.escBinding = null;
        }
      } else {
        this.escBinding = this.$keys.bindGlobal("esc", this.toggleFullscreen);
      }
    },
    modeUpdated(mode) {
      this.updateDebounced({ code: this.value.code, mode });
    },
    codeUpdated(code) {
      this.updateDebounced({ code, mode: this.mode });
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_CodeEditor = resolveComponent("CodeEditor");
  return openBlock(), createBlock(_component_CodeEditor, {
    ref: "codeEditor",
    "color-mode": _ctx.config.color_mode,
    rulers: _ctx.config.rulers,
    disabled: _ctx.config.disabled,
    "read-only": _ctx.config.read_only,
    "key-map": _ctx.config.key_map,
    "tab-size": _ctx.config.indent_size,
    "indent-type": _ctx.config.indent_type,
    "line-numbers": _ctx.config.line_numbers,
    "line-wrapping": _ctx.config.line_wrapping,
    "allow-mode-selection": _ctx.config.mode_selectable,
    "show-mode-label": _ctx.config.show_mode_label,
    mode: $options.mode,
    "model-value": _ctx.value.code,
    title: _ctx.config.display,
    "field-actions": _ctx.fieldActions,
    "onUpdate:mode": $options.modeUpdated,
    "onUpdate:modelValue": $options.codeUpdated
  }, null, 8, ["color-mode", "rulers", "disabled", "read-only", "key-map", "tab-size", "indent-type", "line-numbers", "line-wrapping", "allow-mode-selection", "show-mode-label", "mode", "model-value", "title", "field-actions", "onUpdate:mode", "onUpdate:modelValue"]);
}
const CodeFieldtype = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "CodeFieldtype.vue"]]);
export {
  CodeFieldtype as default
};
