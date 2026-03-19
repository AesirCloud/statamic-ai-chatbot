declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    unstyled: boolean;
    contained: boolean;
    $props: {
        readonly unstyled?: boolean | undefined;
        readonly contained?: boolean | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    'tbody-start'?: ((props: {}) => any) | undefined;
} & {
    'prepended-row-actions'?: ((props: {
        row: Record<string, any>;
    }) => any) | undefined;
};
