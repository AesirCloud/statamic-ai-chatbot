declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    openNewTab: boolean;
    trackRecent: boolean;
    prioritize: boolean;
    keys?: string | undefined;
    text?: string | unknown[] | undefined;
    icon?: string | undefined;
    badge?: string | undefined;
    url?: string | undefined;
    action?: Function | undefined;
    when?: Function | undefined;
    category?: string | undefined;
    $props: {
        readonly openNewTab?: boolean | undefined;
        readonly trackRecent?: boolean | undefined;
        readonly prioritize?: boolean | undefined;
        readonly keys?: string | undefined;
        readonly text?: string | unknown[] | undefined;
        readonly icon?: string | undefined;
        readonly badge?: string | undefined;
        readonly url?: string | undefined;
        readonly action?: Function | undefined;
        readonly when?: Function | undefined;
        readonly category?: string | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    default?: ((props: {
        category: string | undefined;
        icon: string | undefined;
        when: Function | undefined;
        text: string | unknown[] | undefined;
        url: string | undefined;
        action: Function | undefined;
        badge: string | undefined;
        keys: string | undefined;
    }) => any) | undefined;
};
