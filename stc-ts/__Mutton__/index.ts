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
    document.addEventListener("DOMContentLoaded", async function () {
        headElement = document.getElementsByTagName("head")[0];
        if (typeof fetch !== "function") {
            await loadScript(["https://cdn.jsdelivr.net/npm/whatwg-fetch@3.0.0/fetch.min.js"]);
        }
        // --- 组件注册 ---
        // --- Button ---
        Vue.component("mu-button", {
            template: `<div class="button-out" tabindex="0"><div class="button"><div class="button-in"><slot></div></div></div>`
        });
        // --- Line ---
        Vue.component("mu-line", {
            template: `<div class="line"></div>`
        });
        // --- Button ---
        Vue.component("mu-radio", {
            model: {
                prop: "checked",
                event: "change"
            },
            props: {
                value: {
                    default: ""
                },
                checked: {
                    default: false
                }
            },
            template: `<div class="radio" :class="{'selected': checked === value}" tabindex="0" @click="$emit('change', value)"><div class="radio__left-out"><div class="radio__left"><div class="radio__left-in"></div></div></div><div class="radio__right"><div class="radio__right-in"><slot></div></div></div>`
        });
        // --- List ---
        Vue.component("mu-list", {
            model: {
                prop: "value",
                event: "change"
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
            methods: {
                mousedown: async function (this: any, index: number) {
                    this.$emit("change", index);
                    await this.$nextTick();
                    if (this.value !== index) {
                        this.value = index;
                    }
                }
            },
            template: `<div class="list" tabindex="0">` +
                `<div class="list-in" :style="{\'height\': height}">` +
                    `<div v-for="(val, index) of list" class="list__item" :class="{\'selected\': value === index}" @mousedown="mousedown(index)"><div class="list__item-in">{{val.label || val}}</div></div>` +
                `</div>` +
            `</div>`
        });
        // --- 清除空白 ---
        let vueEl = <HTMLDivElement>document.getElementById("vue");
        vueEl.innerHTML = vueEl.innerHTML.replace(/>\s+/g, ">").replace(/\s+</g, "<");
        // --- 创建 Vue 对象 ---
        new Vue({
            el: vueEl,
            data: {
                mask: false,
                alert: "",
                tab: tab,
                confirmTxt: "",
                confirmResolve: null,
                zoom: 1,
                // --- Password ---
                password: "",
                // --- Check ---
                verIndex: 0,
                verList: [],
                infoList: [],
                // --- System ---
                latestVer: "",
                selectedVer: "",
                mirror: "global",
                onlineLibs: [],
                localLibs: [],
                onlineLibsIndex: 0,
                localLibsIndex: 0,
                // --- Config ---
                configTxt: "<?php\nconst __MUTTON__PWD = 'Your password';\n\n"
            },
            mounted: async function (this: any) {
                await this.$nextTick();
                if (window.devicePixelRatio < 2) {
                    this.zoom = 1 / window.devicePixelRatio;
                }
            },
            methods: {
                l: function (key: string, data: any[]|null = null) {
                    return l(key, data);
                },
                // --- Check ---
                refresh: async function (this: any) {
                    this.mask = true;
                    let j = await post(URL_BASE + "__Mutton__/apiRefresh", {password: this.password, mirror: this.mirror});
                    this.mask = false;
                    if (j === false) {
                        this.alert = l("The network connection failed.");
                        return;
                    }
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    this.verList = j.list;
                    this.verList.unshift({value: "master", label: "master"});
                    this.latestVer = j.latestVer;
                },
                check: async function (this: any) {
                    if (!this.verList[this.verIndex]) {
                        this.alert = l("Please select the version first.");
                        return;
                    }
                    if ((this.verList[this.verIndex].value === "master") && (!await this.confirm(l(`Please select a published version to check or upgrade, and "master" for the latest code does not necessarily work correctly. To continue using "master", click "OK" or click "Cancel".`)))) {
                        return;
                    }
                    this.mask = true;
                    let j = await post(URL_BASE + "__Mutton__/apiCheck", {password: this.password, ver: this.verList[this.verIndex].value, verName: this.verList[this.verIndex].label, mirror: this.mirror});
                    this.mask = false;
                    if (j === false) {
                        this.alert = l("The network connection failed.");
                        return;
                    }
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    this.selectedVer = this.verList[this.verIndex].label;
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
                    if (list.length === 0) {
                        this.alert = l("No problem.");
                    }
                    list.unshift(l(`The "mblob" file was last updated:`) + " " + j.lastTime);
                    this.infoList = list;
                    this.onlineLibs = j.onlineLibs;
                },
                // --- System ---
                // --- 安装库 ---
                install: async function (this: any) {
                    if (!await this.confirm(l(`Are you sure you want to install "?"? This will take some time.`, [this.localLibs[this.localLibsIndex].value]))) {
                        return;
                    }
                    this.mask = true;
                    let j = await post(URL_BASE + "__Mutton__/apiInstallFolder", {password: this.password, lib: this.localLibs[this.localLibsIndex].value, mirror: this.mirror});
                    this.mask = false;
                    if (j === false) {
                        this.alert = l("The network connection failed.");
                        return;
                    }
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    await this.getLocalLibs();
                    this.alert = l("Successful.");
                },
                uninstall: async function (this: any) {
                    if (!await this.confirm(l(`Are you sure you want to uninstall "?"?`, [this.localLibs[this.localLibsIndex].value]))) {
                        return;
                    }
                },
                // --- 创建 mblob 文件 ---
                build: async function (this: any) {
                    this.mask = true;
                    let j = await post(URL_BASE + "__Mutton__/apiBuild", {password: this.password});
                    this.mask = false;
                    if (j === false) {
                        this.alert = l("The network connection failed.");
                        return;
                    }
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    this.alert = l("Successful.");
                },
                // --- 获取本地库列表 ---
                getLocalLibs: async function (this: any) {
                    this.mask = true;
                    let j = await post(URL_BASE + "__Mutton__/apiGetLocalLibs", {password: this.password});
                    this.mask = false;
                    if (j === false) {
                        this.alert = l("The network connection failed.");
                        return;
                    }
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    this.localLibs = j.list;
                },
                // --- 询问对话框 ---
                confirm: async function (this: any, txt: string) {
                    return new Promise(async (resolve, reject) => {
                        this.confirmTxt = txt;
                        this.confirmResolve = resolve;
                    });
                }
            }
        });
    });
    document.addEventListener("touchstart", function () {});
    document.addEventListener("contextmenu", function (e) {
        e.preventDefault();
    });

    /**
     * --- 顺序加载 js ---
     * @param paths 要加载文件的路径数组
     */
    function loadScript(paths: string[]): Promise<boolean> {
        return new Promise(async (resolve, reject) => {
            if (paths.length === 0) {
                resolve(true);
            }
            for (let path of paths) {
                let pathLio = path.lastIndexOf("?");
                if (pathLio !== -1) {
                    path = path.slice(0, pathLio);
                }
                if (headElement.querySelector(`[data-res="${path}"]`)) {
                    continue;
                }
                if (await _loadScript(path) === false) {
                    resolve(false);
                }
            }
            resolve(true);
        });
    }
    /**
     * 加载 script 标签并等待返回成功（无视是否已经加载过）
     */
    function _loadScript(path: string): Promise<boolean> {
        return new Promise(async (resolve, reject) => {
            let script = document.createElement("script");
            script.setAttribute("data-res", path);
            script.addEventListener("load", () => {
                resolve(true);
            });
            script.addEventListener("error", (e) => {
                reject(false);
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
                resolve(false);
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
                str = str.replace("?", data[i]);
            }
            return str;
        } else {
            return __LOCALE_OBJ[key];
        }
    }

}

