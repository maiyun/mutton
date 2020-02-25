window.onerror = (msg, uri, line, col, err) => {
    if (err) {
        alert("Error:\n" + err.message + "\n" + err.stack + "\nLine: " + line + "\nColumn: " + col);
    } else {
        console.log(msg);
    }
};

namespace __Mutton__ {

    /** head 标签 */
    let headElement: HTMLHeadElement;
    document.addEventListener("DOMContentLoaded", async function() {
        headElement = document.getElementsByTagName("head")[0];
        if (typeof fetch !== "function") {
            await loadScript(["https://cdn.jsdelivr.net/npm/whatwg-fetch@3.0.0/fetch.min.js"]);
        }
        // --- 组件注册 ---
        // --- Button ---
        Vue.component("mu-button", {
            template: `<div class="button" tabindex="0"><div class="button__in"><div class="button__txt"><slot></div></div></div>`
        });
        // --- Line ---
        Vue.component("mu-line", {
            template: `<div class="line"></div>`
        });
        // --- List ---
        Vue.component("mu-list", {
            model: {
                prop: "value",
                event: "change"
            },
            data: function () {
                return {
                    selectedIndex: 0
                };
            },
            props: {
                list: {
                    default: []
                },
                height: {
                    default: "200px"
                },
                value: {
                    default: 0
                }
            },
            watch: {
                value: function (this: any) {
                    this.selectedIndex = this.value;
                }
            },
            methods: {
                click: function(this: any, index: number) {
                    this.selectedIndex = index;
                    this.$emit("change", index);
                }
            },
            template: `<div class="list" tabindex="0">` +
                `<div class="list__in" :style="{\'height\': height}">` +
                    `<div v-for="(val, index) of list" class="list__item" :class="{\'selected\': selectedIndex === index}" @click="click(index)">{{val.label || val}}</div>` +
                `</div>` +
            `</div>`
        });
        // --- 清除空白 ---
        let vueEl = <HTMLDivElement>document.getElementById("vue");
        vueEl.innerHTML = vueEl.innerHTML.replace(/>\s+?</g, "><");
        // --- 创建 Vue 对象 ---
        new Vue({
            el: vueEl,
            data: {
                mask: false,
                alert: "",
                tab: tab,
                confirmTxt: "",
                confirmResolve: null,
                // --- Password ---
                password: "",
                // --- Check ---
                mindex: 0,
                mlist: [],
                list: [],
                // --- System ---
                latestVer: "0",
                updateList: [],
                updateing: false,
                updateIndex: 0,
                // --- Config ---
                configTxt: "<?php\nconst __MUTTON__PWD = 'Your password';\n\n"
            },
            methods: {
                // --- Check ---
                refresh: async function (this: any) {
                    this.mask = true;
                    let j = await post(URL_BASE + "__Mutton__/apiRefresh", {password: this.password});
                    this.mask = false;
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    this.mlist = j.list;
                    this.mlist.unshift({value: "master", label: "master"});
                },
                check: async function (this: any, mode: number = 0) {
                    if (!this.mlist[this.mindex]) {
                        this.alert = "Please select version.";
                        return;
                    }
                    if ((this.mlist[this.mindex].value === "master") && (!await this.confirm(l(`Please select a published version to check or upgrade, and "master" for the latest code does not necessarily work correctly. To continue using "master", click "OK" or click "Cancel".`)))) {
                        return;
                    }
                    this.mask = true;
                    let j = await post(URL_BASE + "__Mutton__/apiCheck", {password: this.password, ver: this.mlist[this.mindex].value, mode: mode});
                    this.mask = false;
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    let list = [];
                    for (let file of j.noMatch) {
                        list.push(file + " - " + l("File mismatch."));
                    }
                    for (let file of j.miss) {
                        list.push(file + " - " + l("File does not exist."));
                    }
                    for (let file in j.missConst) {
                        list.push(file + " - " + l("Missing constants: ?.", [j.missConst[file].join(",")]));
                    }
                    for (let lib of j.lib) {
                        list.push(l("Library: ?, current version: ?, latest version: ?.", [lib, j.lib[lib].localVer, j.lib[lib].ver]));
                    }
                    for (let lib of j.libFolder) {
                        list.push(l("Library: ?, existing but missing satellite folders.", [lib]));
                    }
                    this.list = list;
                    if (list.length === 0) {
                        this.alert = l("No problem.");
                    }
                },
                // --- System ---
                getLatestVer: async function (this: any) {
                    this.mask = true;
                    let j = await post(URL_BASE + "__Mutton__/apiGetLatestVer", {password: this.password});
                    this.mask = false;
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    this.latestVer = j.version;
                },
                build: async function (this: any) {
                    this.mask = true;
                    let j = await post(URL_BASE + "__Mutton__/apiBuild", {password: this.password});
                    this.mask = false;
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    this.alert = l("Successful.");
                },
                // --- 询问对话框 ---
                confirm: async function(this: any, txt: string) {
                    return new Promise(async (resolve, reject) => {
                        this.confirmTxt = txt;
                        this.confirmResolve = resolve;
                    });
                },
                // --- 自动升级 ---
                update: async function (this: any) {
                    
                }
            }
        });
    });
    document.addEventListener("touchstart", function() {});

    /**
     * --- 顺序加载 js ---
     * @param paths 要加载文件的路径数组
     */
    function loadScript(paths: string[]): Promise<void> {
        return new Promise(async (resolve, reject) => {
            try {
                if (paths.length > 0) {
                    for (let path of paths) {
                        let pathLio = path.lastIndexOf("?");
                        if (pathLio !== -1) {
                            path = path.slice(0, pathLio);
                        }
                        if (headElement.querySelector(`[data-res="${path}"]`)) {
                            continue;
                        }
                        await _loadScript(path);
                    }
                }
                resolve();
            } catch (e) {
                reject(e);
            }
        });
    }
    /**
     * 加载 script 标签并等待返回成功（无视是否已经加载过）
     */
    function _loadScript(path: string): Promise<void> {
        return new Promise(async (resolve, reject) => {
            let script = document.createElement("script");
            script.setAttribute("data-res", path);
            script.addEventListener("load", () => {
                resolve();
            });
            script.addEventListener("error", (e) => {
                reject(e);
            });
            script.src = path;
            headElement.appendChild(script);
        });
    }

    /**
     * --- 发起 post 请求 ---
     * @param url 要请求的 URL 地址
     * @param data 发送的数据
     */
    function post(url: string, data: any): Promise<any> {
        return new Promise(async (resolve, reject) => {
            try {
                let header = new Headers();
                let body = new FormData();
                for (let k in data) {
                    if (data[k] !== undefined) {
                        body.append(k, data[k]);
                    }
                }
                body.append("_xsrf", _xsrf);
                let res = await fetch(url + "?l=" + local, {
                    method: "POST",
                    headers: header,
                    credentials: "include",
                    body: body
                });
                let text;
                let ct = res.headers.get("Content-Type") || "";
                if (ct.indexOf("json") !== -1) {
                    text = await res.json();
                } else {
                    text = await res.text();
                }
                resolve(text);
            } catch (e) {
                reject(e);
            }
        });
    }

    /**
     * --- 休眠一段时间 ---
     * @param timeout 休眠时间
     */
    function sleep(timeout: number): Promise<void> {
        return new Promise(async (resolve, reject) => {
            setTimeout(() => {
                resolve();
            }, timeout);
        });
    }

    /**
     * --- 获取语言包值 ---
     * @param key
     * @param data 要替换的数据
     */
    function l(key: string, data: any[]|null = null): string {
        if (!__LOCALE_OBJ[key]) {
            return "LocaleError";
        }
        if (data) {
            let str = __LOCALE_OBJ[key];
            for (let i = 0; i < data.length; ++i) {
                str.replace("?", data[i]);
            }
            return str;
        } else {
            return __LOCALE_OBJ[key];
        }
    }

}

