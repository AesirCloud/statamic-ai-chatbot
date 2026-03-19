declare const _default: import("vue").DefineComponent<{}, {
    $emit: (event: "changed" | "cleared", ...args: any[]) => void;
    values?: Record<string, any> | undefined;
    config?: Record<string, any> | undefined;
    $props: {
        readonly values?: Record<string, any> | undefined;
        readonly config?: Record<string, any> | undefined;
    };
} & {
    focusAddFieldCombobox: typeof focusAddFieldCombobox;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
declare function focusAddFieldCombobox(): Promise<void>;
