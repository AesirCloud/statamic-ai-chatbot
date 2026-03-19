declare const _default: import("vue").DefineComponent<{}, {
    $emit: (event: "page-selected" | "per-page-changed", ...args: any[]) => void;
    showTotals: boolean;
    resourceMeta: Record<string, any>;
    scrollToTop: boolean;
    showPageLinks: boolean;
    showPerPageSelector: boolean;
    perPage?: number | undefined;
    $props: {
        readonly showTotals?: boolean | undefined;
        readonly resourceMeta?: Record<string, any> | undefined;
        readonly scrollToTop?: boolean | undefined;
        readonly showPageLinks?: boolean | undefined;
        readonly showPerPageSelector?: boolean | undefined;
        readonly perPage?: number | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
