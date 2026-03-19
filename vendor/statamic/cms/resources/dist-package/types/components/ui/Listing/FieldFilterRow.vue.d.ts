declare const _default: import("vue").DefineComponent<{}, {
    $emit: (event: "update:values" | "removed" | "enter-pressed", ...args: any[]) => void;
    values: Record<string, any>;
    meta: Record<string, any>;
    fields: unknown[];
    display: string;
    $props: {
        readonly values?: Record<string, any> | undefined;
        readonly meta?: Record<string, any> | undefined;
        readonly fields?: unknown[] | undefined;
        readonly display?: string | undefined;
    };
} & {
    focusFirstField: () => void;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
