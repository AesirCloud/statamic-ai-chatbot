declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    $emit: (event: "update:modelValue", ...args: any[]) => void;
    id: string;
    focus: boolean;
    size: string;
    append: string;
    type: string;
    variant: string;
    icon: string;
    required: boolean;
    iconAppend: string;
    prepend: string;
    disabled: boolean;
    loading: boolean;
    iconPrepend: string;
    modelValue: string | number;
    placeholder: string;
    limit: number;
    readOnly: boolean;
    clearable: boolean;
    badge: string;
    copyable: boolean;
    viewable: boolean;
    inputAttrs: string | Record<string, any>;
    inputClass: string;
    $props: {
        readonly id?: string | undefined;
        readonly focus?: boolean | undefined;
        readonly size?: string | undefined;
        readonly append?: string | undefined;
        readonly type?: string | undefined;
        readonly variant?: string | undefined;
        readonly icon?: string | undefined;
        readonly required?: boolean | undefined;
        readonly iconAppend?: string | undefined;
        readonly prepend?: string | undefined;
        readonly disabled?: boolean | undefined;
        readonly loading?: boolean | undefined;
        readonly iconPrepend?: string | undefined;
        readonly modelValue?: string | number | undefined;
        readonly placeholder?: string | undefined;
        readonly limit?: number | undefined;
        readonly readOnly?: boolean | undefined;
        readonly clearable?: boolean | undefined;
        readonly badge?: string | undefined;
        readonly copyable?: boolean | undefined;
        readonly viewable?: boolean | undefined;
        readonly inputAttrs?: string | Record<string, any> | undefined;
        readonly inputClass?: string | undefined;
    };
} & {
    focus: () => void;
    select: () => void;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    prepend?: ((props: {}) => any) | undefined;
} & {
    append?: ((props: {}) => any) | undefined;
};
