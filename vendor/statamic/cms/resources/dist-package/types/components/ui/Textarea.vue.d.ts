declare const _default: import("vue").DefineComponent<{}, {
    $emit: (event: "update:modelValue", ...args: any[]) => void;
    id: string;
    resize: string;
    required: boolean;
    disabled: boolean;
    modelValue: string;
    limit: number;
    readOnly: boolean;
    copyable: boolean;
    elastic: boolean;
    rows: string | number;
    $props: {
        readonly id?: string | undefined;
        readonly resize?: string | undefined;
        readonly required?: boolean | undefined;
        readonly disabled?: boolean | undefined;
        readonly modelValue?: string | undefined;
        readonly limit?: number | undefined;
        readonly readOnly?: boolean | undefined;
        readonly copyable?: boolean | undefined;
        readonly elastic?: boolean | undefined;
        readonly rows?: string | number | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
