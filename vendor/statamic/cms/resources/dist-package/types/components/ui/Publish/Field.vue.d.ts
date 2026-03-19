declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    config: Record<string, any>;
    fieldPathPrefix?: string | undefined;
    metaPathPrefix?: string | undefined;
    $props: {
        readonly config?: Record<string, any> | undefined;
        readonly fieldPathPrefix?: string | undefined;
        readonly metaPathPrefix?: string | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    default?: ((props: {
        fieldtypeComponent: any;
        fieldtypeComponentProps: any;
        fieldtypeComponentEvents: any;
        shouldShowField: any;
    }) => any) | undefined;
};
