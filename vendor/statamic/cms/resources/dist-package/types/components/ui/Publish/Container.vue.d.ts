export const injectContainerContext: string | ((context: any) => void);
export const provideContainerContext: string | ((context: any) => void);
export const containerContextKey: string | ((context: any) => void);
declare const _default: __VLS_WithSlots<import("vue").DefineComponent<{}, {
    $emit: (event: "update:modelValue" | "update:visibleValues" | "update:modifiedFields", ...args: any[]) => void;
    name: string;
    errors: Record<string, any>;
    meta: Record<string, any>;
    modelValue: Record<string, any>;
    readOnly: boolean;
    provide: Record<string, any>;
    blueprint: Record<string, any>;
    asConfig: boolean;
    extraValues: Record<string, any>;
    rememberTab: boolean;
    trackDirtyState: boolean;
    reference?: string | undefined;
    originValues?: Record<string, any> | undefined;
    originMeta?: Record<string, any> | undefined;
    site?: string | undefined;
    modifiedFields?: unknown[] | undefined;
    syncFieldConfirmationText?: string | undefined;
    $props: {
        readonly name?: string | undefined;
        readonly errors?: Record<string, any> | undefined;
        readonly meta?: Record<string, any> | undefined;
        readonly modelValue?: Record<string, any> | undefined;
        readonly readOnly?: boolean | undefined;
        readonly provide?: Record<string, any> | undefined;
        readonly blueprint?: Record<string, any> | undefined;
        readonly asConfig?: boolean | undefined;
        readonly extraValues?: Record<string, any> | undefined;
        readonly rememberTab?: boolean | undefined;
        readonly trackDirtyState?: boolean | undefined;
        readonly reference?: string | undefined;
        readonly originValues?: Record<string, any> | undefined;
        readonly originMeta?: Record<string, any> | undefined;
        readonly site?: string | undefined;
        readonly modifiedFields?: unknown[] | undefined;
        readonly syncFieldConfirmationText?: string | undefined;
    };
} & {
    name: string;
    values: import("vue").Ref<Record<string, any>, Record<string, any>>;
    saving: () => void;
    saved: () => void;
    revealerFields: import("vue").Ref<never[], never[]>;
    setFieldValue: (path: any, value: any) => void;
    desyncField: (path: any) => void;
    clearDirtyState: () => void;
    pushComponent: (name: any, { props }: {
        props: any;
    }) => Component;
    visibleValues: import("vue").ComputedRef<any>;
    setValues: (newValues: any) => void;
    setExtraValues: (newValues: any) => void;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>, {
    default?: (props: {}) => any;
}>;
export default _default;
import Component from '@/components/Component.js';
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
