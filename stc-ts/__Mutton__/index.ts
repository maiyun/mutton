window.onerror = (msg, uri, line, col, err) => {
    if (err) {
        alert("Error:\n" + err.message + "\n" + err.stack + "\nLine: " + line + "\nColumn: " + col);
    } else {
        console.log(msg);
    }
};

/** head 标签 */
let headEle: HTMLHeadElement;
/** 已加载的外部路径 */
let outPath: string[] = [];
document.addEventListener("DOMContentLoaded", () => {
    headEle = document.getElementsByTagName("head")[0];
    // --- 先加载异步对象，这个很重要 ---
    let callback = async () => {
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
            template: `<div class="list" tabindex="0"><div class="list__in" :style="{\'height\': height}"><div v-for="(val, index) of list" class="list__item" :class="{\'selected\': selectedIndex === index}" @click="selectedIndex=index;$emit('change', index)">{{val}}</div></div></div>`
        });
        new Vue({
            el: "#vue",
            data: {
                mask: false,
                alert: "",
                tab: tab,
                // --- Password ---
                password: "",
                // --- Check ---
                mindex: 0,
                mlist: [],
                list: [],
                // --- System ---
                latestVer: "0",
                // --- Config ---
                configTxt: "<?php\nconst __MUTTON__PWD = 'Your password';\n\n"
            },
            methods: {
                // --- Check ---
                refresh: async function (this: any) {
                    this.mask = true;
                    let j = await post(HTTP_BASE + "__Mutton__/apiCheckRefresh", {password: this.password});
                    this.mask = false;
                    if (j.result <= 0) {
                        this.alert = j.msg;
                    }
                    this.mlist = j.list;
                },
                check: async function (this: any) {
                    if (!this.mlist[this.mindex]) {
                        this.alert = "Please select version.";
                        return;
                    }
                    this.mask = true;
                    let j = await post(HTTP_BASE + "__Mutton__/apiCheck", {password: this.password, ver: this.mlist[this.mindex]});
                    this.mask = false;
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    let list = [];
                    for (let v of j.list) {
                        list.push(`Cannot match "${v}".`);
                    }
                    for (let v of j.qlist) {
                        list.push(`Does not exist "${v}".`);
                    }
                    for (let v of j.dlist) {
                        list.push(`Extra "${v}".`);
                    }
                    for (let v of j.qlistConst) {
                        list.push(`Does not exist const "${v[1]}" on "${v[0]}".`);
                    }
                    for (let v of j.dlistConst) {
                        list.push(`Extra const "${v[1]}" on "${v[0]}".`);
                    }
                    this.list = list;
                    if (list.length === 0) {
                        this.alert = "All content is normal.";
                    }
                },
                // --- System ---
                getLatestVer: async function (this: any) {
                    this.mask = true;
                    let j = await post(HTTP_BASE + "__Mutton__/apiGetLatestVer", {password: this.password});
                    this.mask = false;
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    this.latestVer = j.version;
                },
                build: async function (this: any) {
                    this.mask = true;
                    let j = await post(HTTP_BASE + "__Mutton__/apiBuild", {password: this.password});
                    this.mask = false;
                    if (j.result <= 0) {
                        this.alert = j.msg;
                        return;
                    }
                    let bstr = atob(j.blob), n = bstr.length, u8arr = new Uint8Array(n);
                    while (n--) {
                        u8arr[n] = bstr.charCodeAt(n);
                    }
                    let blob = new Blob([u8arr]);
                    let a = document.createElement("a");
                    a.download = j.ver + ".mblob";
                    a.href = URL.createObjectURL(blob);
                    let evt = document.createEvent("MouseEvents");
                    evt.initEvent("click", false, false);
                    a.dispatchEvent(evt);
                }
            }
        });
    };
    if (typeof Promise !== "function") {
        let script = document.createElement("script");
        script.addEventListener("load", () => {
            callback();
        });
        script.addEventListener("error", () => {
            alert("Load error.");
        });
        script.src = "https://cdn.jsdelivr.net/npm/promise-polyfill@8.1.0/dist/polyfill.min.js";
        headEle.appendChild(script);
    } else {
        callback();
    }
});
document.addEventListener("touchstart", function() {});

/**
 * --- 顺序加载 js 后再执行 callback ---
 * @param paths 要加载文件的路径数组
 * @param cb 加载完后执行的回调
 */
function loadScript(paths: string[]): Promise<void> {
    return new Promise(async (resolve, reject) => {
        try {
            if (paths.length > 0) {
                for (let i = 0; i < paths.length; ++i) {
                    if (outPath.indexOf(paths[i]) === -1) {
                        outPath.push(paths[i]);
                        await loadOutScript(paths[i]);
                    }
                }
            }
            resolve();
        } catch (e) {
            reject(e);
        }
    });
}

// --- 加载 script 标签（1条）并等待返回成功（无视是否已经加载过） ---
function loadOutScript(path: string): Promise<void> {
    return new Promise(async (resolve, reject) => {
        let script = document.createElement("script");
        script.addEventListener("load", () => {
            resolve();
        });
        script.addEventListener("error", () => {
            reject("Load error.");
        });
        script.src = path;
        headEle.appendChild(script);
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
            let res = await fetch(url, {
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