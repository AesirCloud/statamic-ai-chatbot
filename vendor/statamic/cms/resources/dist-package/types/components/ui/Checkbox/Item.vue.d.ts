declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    $emit: (event: "keydown" | "update:modelValue", ...args: any[]) => void;
    name: string;
    label: string;
    size: string;
    description: string;
    disabled: boolean;
    modelValue: boolean | null;
    align: string;
    indeterminate: boolean;
    readOnly: boolean;
    solo: boolean;
    value?: string | number | boolean | undefined;
    $props: {
        readonly name?: string | undefined;
        readonly label?: string | undefined;
        readonly size?: string | undefined;
        readonly description?: string | undefined;
        readonly disabled?: boolean | undefined;
        readonly modelValue?: boolean | null | undefined;
        readonly align?: string | undefined;
        readonly indeterminate?: boolean | undefined;
        readonly readOnly?: boolean | undefined;
        readonly solo?: boolean | undefined;
        readonly value?: string | number | boolean | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    default?: ((props: {}) => any) | undefined;
};
