declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    title: string;
    icon: string;
    loading: boolean;
    subtitle: string;
    submitText: string;
    route: string;
    titleInstructions: string;
    handleInstructions: string;
    withoutHandle: boolean;
    $props: {
        readonly title?: string | undefined;
        readonly icon?: string | undefined;
        readonly loading?: boolean | undefined;
        readonly subtitle?: string | undefined;
        readonly submitText?: string | undefined;
        readonly route?: string | undefined;
        readonly titleInstructions?: string | undefined;
        readonly handleInstructions?: string | undefined;
        readonly withoutHandle?: boolean | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    default?: ((props: {}) => any) | undefined;
} & {
    footer?: ((props: {}) => any) | undefined;
};
