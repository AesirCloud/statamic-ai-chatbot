declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    text: string | number | boolean | null;
    target: string;
    size: string;
    icon: string | null;
    href: string | null;
    level: number | null;
    $props: {
        readonly text?: string | number | boolean | null | undefined;
        readonly target?: string | undefined;
        readonly size?: string | undefined;
        readonly icon?: string | null | undefined;
        readonly href?: string | null | undefined;
        readonly level?: number | null | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    default?: ((props: {}) => any) | undefined;
};
