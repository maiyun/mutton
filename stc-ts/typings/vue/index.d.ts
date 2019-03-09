declare class Vue {
    constructor(opt: any);
    static component(name: string, opt: any): any;
    static extend(opt: any): any;
    static use(obj: any, opt?: any): any;

    readonly $el: HTMLElement;
    $refs: any
    $data: any;
    $props: any;
    $slots: any;
    $parent: any;
    $children: any[];
    $watch: any;

    $emit(event: string, ...args: any[]): this;
    $nextTick(callback: (this: this) => void): void;
    $nextTick(): Promise<void>;
    $mount(c: string): any;

    $ELEMENT: any;
}

declare namespace Vuex {
    class Store {
        constructor(opt: any);
        commit(...arg: any[]): any;
        state: any;
    }
}