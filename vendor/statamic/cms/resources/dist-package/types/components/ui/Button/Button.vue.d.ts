declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    text: string | number | boolean | null;
    target: string;
    size: string;
    type: string;
    variant: string;
    icon: string;
    as: string;
    href: string;
    iconAppend: string;
    disabled: boolean;
    iconOnly: boolean;
    inset: boolean;
    loading: boolean;
    round: boolean;
    $props: {
        readonly text?: string | number | boolean | null | undefined;
        readonly target?: string | undefined;
        readonly size?: string | undefined;
        readonly type?: string | undefined;
        readonly variant?: string | undefined;
        readonly icon?: string | undefined;
        readonly as?: string | undefined;
        readonly href?: string | undefined;
        readonly iconAppend?: string | undefined;
        readonly disabled?: boolean | undefined;
        readonly iconOnly?: boolean | undefined;
        readonly inset?: boolean | undefined;
        readonly loading?: boolean | undefined;
        readonly round?: boolean | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    default?: ((props: {}) => any) | undefined;
};
