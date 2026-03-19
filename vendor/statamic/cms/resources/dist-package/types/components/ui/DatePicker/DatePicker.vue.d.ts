declare const _default: import("vue").DefineComponent<{}, {
    $emit: (event: "update:modelValue", ...args: any[]) => void;
    required: boolean;
    disabled: boolean;
    modelValue: string | Record<string, any>;
    min: string | Record<string, any>;
    max: string | Record<string, any>;
    numberOfMonths: number;
    inline: boolean;
    readOnly: boolean;
    clearable: boolean;
    badge: string;
    granularity: string;
    $props: {
        readonly required?: boolean | undefined;
        readonly disabled?: boolean | undefined;
        readonly modelValue?: string | Record<string, any> | undefined;
        readonly min?: string | Record<string, any> | undefined;
        readonly max?: string | Record<string, any> | undefined;
        readonly numberOfMonths?: number | undefined;
        readonly inline?: boolean | undefined;
        readonly readOnly?: boolean | undefined;
        readonly clearable?: boolean | undefined;
        readonly badge?: string | undefined;
        readonly granularity?: string | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
