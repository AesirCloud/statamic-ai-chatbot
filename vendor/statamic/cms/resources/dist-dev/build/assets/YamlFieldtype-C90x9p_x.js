import { _ as _export_sfc, C as CodeEditor, F as Fieldtype, r as resolveComponent, o as openBlock, c as createBlock } from "./index-DwP14X7s.js";
const _sfc_main = {
  mixins: [Fieldtype],
  components: { CodeEditor }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_CodeEditor = resolveComponent("CodeEditor");
  return openBlock(), createBlock(_component_CodeEditor, {
    ref: "codeEditor",
    "allow-mode-selection": false,
    disabled: _ctx.config.disabled,
    "indent-type": "spaces",
    mode: "yaml",
    "model-value": _ctx.value,
    "read-only": _ctx.isReadOnly,
    "show-mode-label": false,
    "tab-size": 2,
    theme: _ctx.config.color_mode,
    "onUpdate:modelValue": _ctx.update
  }, null, 8, ["disabled", "model-value", "read-only", "theme", "onUpdate:modelValue"]);
}
const YamlFieldtype = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "YamlFieldtype.vue"]]);
export {
  YamlFieldtype as default
};
