declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    $emit: (event: "update:open" | "opened" | "closed", ...args: any[]) => void;
    title: string;
    size: string;
    icon: string | null;
    inset: boolean;
    open: boolean;
    beforeClose: Function;
    showCloseButton: boolean;
    wrapSlot: boolean;
    $props: {
        readonly title?: string | undefined;
        readonly size?: string | undefined;
        readonly icon?: string | null | undefined;
        readonly inset?: boolean | undefined;
        readonly open?: boolean | undefined;
        readonly beforeClose?: Function | undefined;
        readonly showCloseButton?: boolean | undefined;
        readonly wrapSlot?: boolean | undefined;
    };
} & {
    open: typeof open;
    close: typeof close;
    runCloseCallback: typeof runCloseCallback;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    trigger?: ((props: {}) => any) | undefined;
} & {
    'header-actions'?: ((props: {}) => any) | undefined;
} & {
    default?: ((props: {
        close: typeof close;
    }) => any) | undefined;
} & {
    default?: ((props: {
        close: typeof close;
    }) => any) | undefined;
} & {
    default?: ((props: {
        close: typeof close;
    }) => any) | undefined;
} & {
    default?: ((props: {
        close: typeof close;
    }) => any) | undefined;
} & {
    'footer-start'?: ((props: {}) => any) | undefined;
} & {
    'footer-end'?: ((props: {}) => any) | undefined;
};
declare function open(): void;
declare function close(): void;
declare function runCloseCallback(): boolean;
