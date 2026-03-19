declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    $emit: (event: "update:modelValue", ...args: any[]) => void;
    size: string;
    variant: string;
    icon: string;
    disabled: boolean;
    modelValue: string | number | Record<string, any>;
    readOnly: boolean;
    options: unknown[];
    clearable: boolean;
    optionLabel: string;
    optionValue: string;
    placeholder?: string | undefined;
    $props: {
        readonly size?: string | undefined;
        readonly variant?: string | undefined;
        readonly icon?: string | undefined;
        readonly disabled?: boolean | undefined;
        readonly modelValue?: string | number | Record<string, any> | undefined;
        readonly readOnly?: boolean | undefined;
        readonly options?: unknown[] | undefined;
        readonly clearable?: boolean | undefined;
        readonly optionLabel?: string | undefined;
        readonly optionValue?: string | undefined;
        readonly placeholder?: string | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    'selected-option'?: ((props: {
        option: any;
    }) => any) | undefined;
} & {
    'no-options'?: ((props: {}) => any) | undefined;
} & {
    option?: ((props: any) => any) | undefined;
};
