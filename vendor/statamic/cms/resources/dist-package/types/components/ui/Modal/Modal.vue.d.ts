declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    $emit: (event: "update:open" | "opened" | "dismissed", ...args: any[]) => void;
    title: string;
    blur: boolean;
    icon: string | null;
    open: boolean;
    beforeClose: Function;
    dismissible: boolean;
    $props: {
        readonly title?: string | undefined;
        readonly blur?: boolean | undefined;
        readonly icon?: string | null | undefined;
        readonly open?: boolean | undefined;
        readonly beforeClose?: Function | undefined;
        readonly dismissible?: boolean | undefined;
    };
} & {
    open: typeof open;
    close: typeof close;
    runCloseCallback: typeof runCloseCallback;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    trigger?: ((props: {}) => any) | undefined;
} & {
    default?: ((props: {}) => any) | undefined;
} & {
    footer?: ((props: {}) => any) | undefined;
};
declare function open(): void;
declare function close(): void;
declare function runCloseCallback(): boolean;
