declare const _default: import("vue").DefineComponent<{}, {
    $emit: (event: "update:modelValue", ...args: any[]) => void;
    modelValue: string | Record<string, any>;
    min: string | Record<string, any>;
    max: string | Record<string, any>;
    components: Record<string, any>;
    numberOfMonths: number;
    inline: boolean;
    $props: {
        readonly modelValue?: string | Record<string, any> | undefined;
        readonly min?: string | Record<string, any> | undefined;
        readonly max?: string | Record<string, any> | undefined;
        readonly components?: Record<string, any> | undefined;
        readonly numberOfMonths?: number | undefined;
        readonly inline?: boolean | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
