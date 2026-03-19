declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    $emit: (event: "search" | "update:modelValue" | "selected" | "added", ...args: any[]) => void;
    size: string;
    variant: string;
    icon: string;
    disabled: boolean;
    modelValue: string | number | Record<string, any>;
    multiple: boolean;
    readOnly: boolean;
    options: unknown[];
    clearable: boolean;
    discreteFocusOutline: boolean;
    ignoreFilter: boolean;
    labelHtml: boolean;
    maxSelections: number;
    optionLabel: string;
    optionValue: string;
    searchable: boolean;
    taggable: boolean;
    id?: string | undefined;
    placeholder?: string | undefined;
    closeOnSelect?: boolean | undefined;
    $props: {
        readonly size?: string | undefined;
        readonly variant?: string | undefined;
        readonly icon?: string | undefined;
        readonly disabled?: boolean | undefined;
        readonly modelValue?: string | number | Record<string, any> | undefined;
        readonly multiple?: boolean | undefined;
        readonly readOnly?: boolean | undefined;
        readonly options?: unknown[] | undefined;
        readonly clearable?: boolean | undefined;
        readonly discreteFocusOutline?: boolean | undefined;
        readonly ignoreFilter?: boolean | undefined;
        readonly labelHtml?: boolean | undefined;
        readonly maxSelections?: number | undefined;
        readonly optionLabel?: string | undefined;
        readonly optionValue?: string | undefined;
        readonly searchable?: boolean | undefined;
        readonly taggable?: boolean | undefined;
        readonly id?: string | undefined;
        readonly placeholder?: string | undefined;
        readonly closeOnSelect?: boolean | undefined;
    };
} & {
    searchQuery: import("vue").Ref<string, string>;
    filteredOptions: import("vue").ComputedRef<unknown[]>;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    'selected-option'?: ((props: {
        option: any;
    }) => any) | undefined;
} & {
    'no-options'?: ((props: {
        searchQuery: string;
    }) => any) | undefined;
} & {
    option?: ((props: any) => any) | undefined;
} & {
    'selected-options'?: ((props: {
        disabled: boolean;
        readOnly: boolean;
        getOptionLabel: (option: any) => any;
        getOptionValue: (option: any) => any;
        labelHtml: boolean;
        deselect: typeof deselect;
    }) => any) | undefined;
};
declare function deselect(option: any): void;
