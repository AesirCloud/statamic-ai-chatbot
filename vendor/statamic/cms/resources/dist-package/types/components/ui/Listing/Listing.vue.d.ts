export const injectListingContext: string | ((context: any) => void);
export const provideListingContext: string | ((context: any) => void);
declare const _default: __VLS_WithSlots<import("vue").DefineComponent<{}, {
    $emit: (event: "reordered" | "update:columns" | "update:sortColumn" | "update:sortDirection" | "update:selections" | "update:searchQuery" | "requestCompleted" | "refreshing", ...args: any[]) => void;
    filters: unknown[];
    sortable: boolean;
    maxSelections: number;
    searchQuery: string;
    actionContext: Record<string, any>;
    reorderable: boolean;
    sortColumn: string;
    sortDirection: string;
    allowActionsWhileReordering: boolean;
    showPaginationTotals: boolean;
    showPaginationPageLinks: boolean;
    showPaginationPerPageSelector: boolean;
    allowPresets: boolean;
    allowBulkActions: boolean;
    allowCustomizingColumns: boolean;
    pushQuery: boolean;
    additionalParameters: Record<string, any>;
    allowSearch: boolean;
    filtersForReordering: Function;
    url?: string | undefined;
    perPage?: number | undefined;
    selections?: unknown[] | undefined;
    columns?: unknown[] | undefined;
    actionUrl?: string | undefined;
    preferencesPrefix?: string | undefined;
    items?: unknown[] | undefined;
    $props: {
        readonly filters?: unknown[] | undefined;
        readonly sortable?: boolean | undefined;
        readonly maxSelections?: number | undefined;
        readonly searchQuery?: string | undefined;
        readonly actionContext?: Record<string, any> | undefined;
        readonly reorderable?: boolean | undefined;
        readonly sortColumn?: string | undefined;
        readonly sortDirection?: string | undefined;
        readonly allowActionsWhileReordering?: boolean | undefined;
        readonly showPaginationTotals?: boolean | undefined;
        readonly showPaginationPageLinks?: boolean | undefined;
        readonly showPaginationPerPageSelector?: boolean | undefined;
        readonly allowPresets?: boolean | undefined;
        readonly allowBulkActions?: boolean | undefined;
        readonly allowCustomizingColumns?: boolean | undefined;
        readonly pushQuery?: boolean | undefined;
        readonly additionalParameters?: Record<string, any> | undefined;
        readonly allowSearch?: boolean | undefined;
        readonly filtersForReordering?: Function | undefined;
        readonly url?: string | undefined;
        readonly perPage?: number | undefined;
        readonly selections?: unknown[] | undefined;
        readonly columns?: unknown[] | undefined;
        readonly actionUrl?: string | undefined;
        readonly preferencesPrefix?: string | undefined;
        readonly items?: unknown[] | undefined;
    };
} & {
    refresh: () => void;
    setFilter: (handle: any, values: any) => void;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>, {
    initializing?: (props: {}) => any;
} & {
    default?: (props: {
        items: unknown[] | undefined;
        isColumnVisible: (column: any) => unknown;
        loading: boolean;
    }) => any;
} & {
    'prepended-row-actions'?: (props: {
        row: Record<string, any>;
    }) => any;
}>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
