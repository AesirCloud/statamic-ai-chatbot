declare const _default: import("vue").DefineComponent<{}, {
    $emit: (event: "confirmed", ...args: any[]) => void;
    action: Record<string, any>;
    selections: number;
    isDirty: boolean;
    errors?: Record<string, any> | undefined;
    $props: {
        readonly action?: Record<string, any> | undefined;
        readonly selections?: number | undefined;
        readonly isDirty?: boolean | undefined;
        readonly errors?: Record<string, any> | undefined;
    };
} & {
    handle: any;
    confirm: typeof confirm;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
declare function confirm(): void;
