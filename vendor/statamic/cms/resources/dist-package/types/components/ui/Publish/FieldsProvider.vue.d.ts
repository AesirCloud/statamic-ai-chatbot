export const injectFieldsContext: string | ((context: any) => void);
export const provideFieldsContext: string | ((context: any) => void);
declare const _default: __VLS_WithSlots<import("vue").DefineComponent<{}, {
    readOnly: boolean;
    fields: unknown[];
    fieldPathPrefix?: string | undefined;
    metaPathPrefix?: string | undefined;
    asConfig?: boolean | undefined;
    $props: {
        readonly readOnly?: boolean | undefined;
        readonly fields?: unknown[] | undefined;
        readonly fieldPathPrefix?: string | undefined;
        readonly metaPathPrefix?: string | undefined;
        readonly asConfig?: boolean | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>, {
    default?: (props: {}) => any;
}>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
