declare const _default: __VLS_WithSlots<typeof __VLS_component, __VLS_Slots>;
export default _default;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_component: import("vue").DefineComponent<{}, {
    required: boolean;
    disabled: boolean;
    inline: boolean;
    readOnly: boolean;
    badge: string;
    fullWidthSetting: boolean;
    instructions: string;
    instructionsBelow: boolean;
    id?: string | undefined;
    error?: string | undefined;
    errors?: Record<string, any> | undefined;
    label?: string | undefined;
    $props: {
        readonly required?: boolean | undefined;
        readonly disabled?: boolean | undefined;
        readonly inline?: boolean | undefined;
        readonly readOnly?: boolean | undefined;
        readonly badge?: string | undefined;
        readonly fullWidthSetting?: boolean | undefined;
        readonly instructions?: string | undefined;
        readonly instructionsBelow?: boolean | undefined;
        readonly id?: string | undefined;
        readonly error?: string | undefined;
        readonly errors?: Record<string, any> | undefined;
        readonly label?: string | undefined;
    };
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    label?: ((props: {}) => any) | undefined;
} & {
    actions?: ((props: {}) => any) | undefined;
} & {
    label?: ((props: {}) => any) | undefined;
} & {
    default?: ((props: {}) => any) | undefined;
};
