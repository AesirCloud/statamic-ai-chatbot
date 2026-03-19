declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    $emit: (event: "update:open", ...args: any[]) => void;
    inset: boolean;
    align: string;
    delay: number;
    side: string;
    open: boolean;
    offset: number;
    arrow: boolean;
    $props: {
        readonly inset?: boolean | undefined;
        readonly align?: string | undefined;
        readonly delay?: number | undefined;
        readonly side?: string | undefined;
        readonly open?: boolean | undefined;
        readonly offset?: number | undefined;
        readonly arrow?: boolean | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    trigger?: ((props: {}) => any) | undefined;
} & {
    default?: ((props: {
        open: boolean;
    }) => any) | undefined;
};
