declare const _default: import("vue").DefineComponent<{}, {
    $emit: (event: "cancel" | "submit" | "update:modelValue" | "edit", ...args: any[]) => void;
    modelValue: string;
    startWithEditMode: boolean;
    submitMode: string;
    placeholder?: string | undefined;
    $props: {
        readonly modelValue?: string | undefined;
        readonly startWithEditMode?: boolean | undefined;
        readonly submitMode?: string | undefined;
        readonly placeholder?: string | undefined;
    };
} & {
    edit: typeof edit;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
declare function edit(): void;
