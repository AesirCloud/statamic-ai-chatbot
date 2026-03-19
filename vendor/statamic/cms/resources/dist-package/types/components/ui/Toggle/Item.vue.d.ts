declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    label: string;
    value: string;
    icon: string;
    disabled: boolean;
    ariaDescribedby: string;
    $props: {
        readonly label?: string | undefined;
        readonly value?: string | undefined;
        readonly icon?: string | undefined;
        readonly disabled?: boolean | undefined;
        readonly ariaDescribedby?: string | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    default?: ((props: {}) => any) | undefined;
};
