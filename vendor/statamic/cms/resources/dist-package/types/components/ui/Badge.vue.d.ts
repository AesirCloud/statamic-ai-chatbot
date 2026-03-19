declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    text: string | number | boolean | null;
    target: string;
    size: string;
    append: string | number | boolean | null;
    icon: string;
    as: string;
    color: string;
    href: string;
    iconAppend: string;
    pill: boolean;
    prepend: string | number | boolean | null;
    $props: {
        readonly text?: string | number | boolean | null | undefined;
        readonly target?: string | undefined;
        readonly size?: string | undefined;
        readonly append?: string | number | boolean | null | undefined;
        readonly icon?: string | undefined;
        readonly as?: string | undefined;
        readonly color?: string | undefined;
        readonly href?: string | undefined;
        readonly iconAppend?: string | undefined;
        readonly pill?: boolean | undefined;
        readonly prepend?: string | number | boolean | null | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    default?: ((props: {}) => any) | undefined;
};
