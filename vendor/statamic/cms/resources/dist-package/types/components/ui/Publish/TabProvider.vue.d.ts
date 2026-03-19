export const injectTabContext: string | ((context: any) => void);
export const provideTabContext: string | ((context: any) => void);
declare const _default: __VLS_WithSlots<import("vue").DefineComponent<{}, {
    tab: Record<string, any>;
    $props: {
        readonly tab?: Record<string, any> | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>, {
    default?: (props: {}) => any;
}>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
