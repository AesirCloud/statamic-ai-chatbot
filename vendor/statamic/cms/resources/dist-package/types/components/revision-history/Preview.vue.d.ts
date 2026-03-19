declare const _default: import("vue").DefineComponent<import("vue").ExtractPropTypes<{
    revision: ObjectConstructor;
}>, {}, {
    creating: boolean;
    readOnly: boolean;
    method: string;
    action: string;
    itemUrl: any;
}, {
    title(): any;
}, {
    saved(response: any): void;
}, import("vue").DefineComponent<import("vue").ExtractPropTypes<{
    component: StringConstructor;
    componentProps: ObjectConstructor;
    stackSize: StringConstructor;
}>, {}, {
    loading: boolean;
    readOnly: boolean;
    componentPropValues: {};
    closingWithChanges: boolean;
}, {
    publishContainer(): string;
}, {
    getItem(): void;
    close(): void;
    confirmClose(): void;
    shouldClose(): boolean;
    confirmCloseWithChanges(): void;
}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<import("vue").ExtractPropTypes<{
    component: StringConstructor;
    componentProps: ObjectConstructor;
    stackSize: StringConstructor;
}>> & Readonly<{}>, {}, {}, {
    Button: {
        new (...args: any[]): import("vue").CreateComponentPublicInstanceWithMixins<Readonly<{}> & Readonly<{}>, {
            text: string | number | boolean | null;
            target: string;
            size: string;
            type: string;
            variant: string;
            icon: string;
            as: string;
            href: string;
            iconAppend: string;
            disabled: boolean;
            iconOnly: boolean;
            inset: boolean;
            loading: boolean;
            round: boolean;
            $props: {
                readonly text?: string | number | boolean | null | undefined;
                readonly target?: string | undefined;
                readonly size?: string | undefined;
                readonly type?: string | undefined;
                readonly variant?: string | undefined;
                readonly icon?: string | undefined;
                readonly as?: string | undefined;
                readonly href?: string | undefined;
                readonly iconAppend?: string | undefined;
                readonly disabled?: boolean | undefined;
                readonly iconOnly?: boolean | undefined;
                readonly inset?: boolean | undefined;
                readonly loading?: boolean | undefined;
                readonly round?: boolean | undefined;
            };
        }, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, import("vue").PublicProps, {}, true, {}, {}, import("vue").GlobalComponents, import("vue").GlobalDirectives, string, {}, any, import("vue").ComponentProvideOptions, {
            P: {};
            B: {};
            D: {};
            C: {};
            M: {};
            Defaults: {};
        }, Readonly<{}> & Readonly<{}>, {
            text: string | number | boolean | null;
            target: string;
            size: string;
            type: string;
            variant: string;
            icon: string;
            as: string;
            href: string;
            iconAppend: string;
            disabled: boolean;
            iconOnly: boolean;
            inset: boolean;
            loading: boolean;
            round: boolean;
            $props: {
                readonly text?: string | number | boolean | null | undefined;
                readonly target?: string | undefined;
                readonly size?: string | undefined;
                readonly type?: string | undefined;
                readonly variant?: string | undefined;
                readonly icon?: string | undefined;
                readonly as?: string | undefined;
                readonly href?: string | undefined;
                readonly iconAppend?: string | undefined;
                readonly disabled?: boolean | undefined;
                readonly iconOnly?: boolean | undefined;
                readonly inset?: boolean | undefined;
                readonly loading?: boolean | undefined;
                readonly round?: boolean | undefined;
            };
        }, {}, {}, {}, {}>;
        __isFragment?: never;
        __isTeleport?: never;
        __isSuspense?: never;
    } & import("vue").ComponentOptionsBase<Readonly<{}> & Readonly<{}>, {
        text: string | number | boolean | null;
        target: string;
        size: string;
        type: string;
        variant: string;
        icon: string;
        as: string;
        href: string;
        iconAppend: string;
        disabled: boolean;
        iconOnly: boolean;
        inset: boolean;
        loading: boolean;
        round: boolean;
        $props: {
            readonly text?: string | number | boolean | null | undefined;
            readonly target?: string | undefined;
            readonly size?: string | undefined;
            readonly type?: string | undefined;
            readonly variant?: string | undefined;
            readonly icon?: string | undefined;
            readonly as?: string | undefined;
            readonly href?: string | undefined;
            readonly iconAppend?: string | undefined;
            readonly disabled?: boolean | undefined;
            readonly iconOnly?: boolean | undefined;
            readonly inset?: boolean | undefined;
            readonly loading?: boolean | undefined;
            readonly round?: boolean | undefined;
        };
    }, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, {}, {}, string, {}, import("vue").GlobalComponents, import("vue").GlobalDirectives, string, import("vue").ComponentProvideOptions> & import("vue").VNodeProps & import("vue").AllowedComponentProps & import("vue").ComponentCustomProps & (new () => {
        $slots: {
            default?: (props: {}) => any;
        };
    });
    Icon: import("vue").DefineComponent<{}, {
        name: string;
        set: string;
        $props: {
            readonly name?: string | undefined;
            readonly set?: string | undefined;
        };
    }, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<{}> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
    Stack: {
        new (...args: any[]): import("vue").CreateComponentPublicInstanceWithMixins<Readonly<{}> & Readonly<{}>, {
            $emit: (event: "update:open" | "opened" | "closed", ...args: any[]) => void;
            title: string;
            size: string;
            icon: string | null;
            inset: boolean;
            open: boolean;
            beforeClose: Function;
            showCloseButton: boolean;
            wrapSlot: boolean;
            $props: {
                readonly title?: string | undefined;
                readonly size?: string | undefined;
                readonly icon?: string | null | undefined;
                readonly inset?: boolean | undefined;
                readonly open?: boolean | undefined;
                readonly beforeClose?: Function | undefined;
                readonly showCloseButton?: boolean | undefined;
                readonly wrapSlot?: boolean | undefined;
            };
        } & {
            open: () => void;
            close: () => void;
            runCloseCallback: () => boolean;
        }, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, import("vue").PublicProps, {}, true, {}, {}, import("vue").GlobalComponents, import("vue").GlobalDirectives, string, {}, any, import("vue").ComponentProvideOptions, {
            P: {};
            B: {};
            D: {};
            C: {};
            M: {};
            Defaults: {};
        }, Readonly<{}> & Readonly<{}>, {
            $emit: (event: "update:open" | "opened" | "closed", ...args: any[]) => void;
            title: string;
            size: string;
            icon: string | null;
            inset: boolean;
            open: boolean;
            beforeClose: Function;
            showCloseButton: boolean;
            wrapSlot: boolean;
            $props: {
                readonly title?: string | undefined;
                readonly size?: string | undefined;
                readonly icon?: string | null | undefined;
                readonly inset?: boolean | undefined;
                readonly open?: boolean | undefined;
                readonly beforeClose?: Function | undefined;
                readonly showCloseButton?: boolean | undefined;
                readonly wrapSlot?: boolean | undefined;
            };
        } & {
            open: () => void;
            close: () => void;
            runCloseCallback: () => boolean;
        }, {}, {}, {}, {}>;
        __isFragment?: never;
        __isTeleport?: never;
        __isSuspense?: never;
    } & import("vue").ComponentOptionsBase<Readonly<{}> & Readonly<{}>, {
        $emit: (event: "update:open" | "opened" | "closed", ...args: any[]) => void;
        title: string;
        size: string;
        icon: string | null;
        inset: boolean;
        open: boolean;
        beforeClose: Function;
        showCloseButton: boolean;
        wrapSlot: boolean;
        $props: {
            readonly title?: string | undefined;
            readonly size?: string | undefined;
            readonly icon?: string | null | undefined;
            readonly inset?: boolean | undefined;
            readonly open?: boolean | undefined;
            readonly beforeClose?: Function | undefined;
            readonly showCloseButton?: boolean | undefined;
            readonly wrapSlot?: boolean | undefined;
        };
    } & {
        open: () => void;
        close: () => void;
        runCloseCallback: () => boolean;
    }, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {}, string, {}, {}, string, {}, import("vue").GlobalComponents, import("vue").GlobalDirectives, string, import("vue").ComponentProvideOptions> & import("vue").VNodeProps & import("vue").AllowedComponentProps & import("vue").ComponentCustomProps & (new () => {
        $slots: {
            trigger?: (props: {}) => any;
        } & {
            'header-actions'?: (props: {}) => any;
        } & {
            default?: (props: {
                close: () => void;
            }) => any;
        } & {
            default?: (props: {
                close: () => void;
            }) => any;
        } & {
            default?: (props: {
                close: () => void;
            }) => any;
        } & {
            default?: (props: {
                close: () => void;
            }) => any;
        } & {
            'footer-start'?: (props: {}) => any;
        } & {
            'footer-end'?: (props: {}) => any;
        };
    });
}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>, import("vue").ComponentOptionsMixin, {}, string, import("vue").PublicProps, Readonly<import("vue").ExtractPropTypes<{
    revision: ObjectConstructor;
}>> & Readonly<{}>, {}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
export default _default;
