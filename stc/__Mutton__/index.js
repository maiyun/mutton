"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
window.onerror = function (msg, uri, line, col, err) {
    if (err) {
        alert("Error:\n" + err.message + "\n" + err.stack + "\nLine: " + line + "\nColumn: " + col);
    }
    else {
        console.log(msg);
    }
};
var __Mutton__;
(function (__Mutton__) {
    var headElement;
    document.addEventListener("DOMContentLoaded", function () {
        return __awaiter(this, void 0, void 0, function () {
            var vueEl;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        headElement = document.getElementsByTagName("head")[0];
                        if (!(typeof fetch !== "function")) return [3, 2];
                        return [4, loadScript(["https://cdn.jsdelivr.net/npm/whatwg-fetch@3.0.0/fetch.min.js"])];
                    case 1:
                        _a.sent();
                        _a.label = 2;
                    case 2:
                        Vue.component("mu-button", {
                            template: "<div class=\"button-out\" tabindex=\"0\"><div class=\"button\"><div class=\"button-in\"><slot></div></div></div>"
                        });
                        Vue.component("mu-line", {
                            template: "<div class=\"line\"></div>"
                        });
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
                            template: "<div class=\"radio\" :class=\"{'selected': checked === value}\" tabindex=\"0\" @click=\"$emit('change', value)\"><div class=\"radio__left-out\"><div class=\"radio__left\"><div class=\"radio__left-in\"></div></div></div><div class=\"radio__right\"><div class=\"radio__right-in\"><slot></div></div></div>"
                        });
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
                                value: function () {
                                    this.selectedIndex = this.value;
                                }
                            },
                            methods: {
                                click: function (index) {
                                    this.selectedIndex = index;
                                    this.$emit("change", index);
                                }
                            },
                            template: "<div class=\"list\" tabindex=\"0\">" +
                                "<div class=\"list-in\" :style=\"{'height': height}\">" +
                                "<div v-for=\"(val, index) of list\" class=\"list__item\" :class=\"{'selected': selectedIndex === index}\" @click=\"click(index)\"><div class=\"list__item-in\">{{val.label || val}}</div></div>" +
                                "</div>" +
                                "</div>"
                        });
                        vueEl = document.getElementById("vue");
                        vueEl.innerHTML = vueEl.innerHTML.replace(/>\s+/g, ">").replace(/\s+</g, "<");
                        new Vue({
                            el: vueEl,
                            data: {
                                mask: false,
                                alert: "",
                                tab: tab,
                                confirmTxt: "",
                                confirmResolve: null,
                                zoom: 1,
                                password: "",
                                verIndex: 0,
                                verList: [],
                                infoList: [],
                                latestVer: "",
                                selectedVer: "",
                                mirror: "global",
                                onlineLibs: [],
                                localLibs: [],
                                onlineLibsIndex: 0,
                                localLibsIndex: 0,
                                configTxt: "<?php\nconst __MUTTON__PWD = 'Your password';\n\n"
                            },
                            mounted: function () {
                                return __awaiter(this, void 0, void 0, function () {
                                    return __generator(this, function (_a) {
                                        switch (_a.label) {
                                            case 0: return [4, this.$nextTick()];
                                            case 1:
                                                _a.sent();
                                                if (window.devicePixelRatio < 2) {
                                                    this.zoom = 1 / window.devicePixelRatio;
                                                }
                                                return [2];
                                        }
                                    });
                                });
                            },
                            methods: {
                                l: function (key, data) {
                                    if (data === void 0) { data = null; }
                                    return l(key, data);
                                },
                                refresh: function () {
                                    return __awaiter(this, void 0, void 0, function () {
                                        var j;
                                        return __generator(this, function (_a) {
                                            switch (_a.label) {
                                                case 0:
                                                    this.mask = true;
                                                    return [4, post(URL_BASE + "__Mutton__/apiRefresh", { password: this.password, mirror: this.mirror })];
                                                case 1:
                                                    j = _a.sent();
                                                    this.mask = false;
                                                    if (j.result <= 0) {
                                                        this.alert = j.msg;
                                                        return [2];
                                                    }
                                                    this.verList = j.list;
                                                    this.verList.unshift({ value: "master", label: "master" });
                                                    this.latestVer = j.latestVer;
                                                    return [2];
                                            }
                                        });
                                    });
                                },
                                check: function () {
                                    return __awaiter(this, void 0, void 0, function () {
                                        var _a, j, list, _i, _b, file, _c, _d, file, file, _e, _f, lib, _g, _h, lib;
                                        return __generator(this, function (_j) {
                                            switch (_j.label) {
                                                case 0:
                                                    if (!this.verList[this.verIndex]) {
                                                        this.alert = l("Please select the version first.");
                                                        return [2];
                                                    }
                                                    _a = (this.verList[this.verIndex].value === "master");
                                                    if (!_a) return [3, 2];
                                                    return [4, this.confirm(l("Please select a published version to check or upgrade, and \"master\" for the latest code does not necessarily work correctly. To continue using \"master\", click \"OK\" or click \"Cancel\"."))];
                                                case 1:
                                                    _a = (!(_j.sent()));
                                                    _j.label = 2;
                                                case 2:
                                                    if (_a) {
                                                        return [2];
                                                    }
                                                    this.mask = true;
                                                    return [4, post(URL_BASE + "__Mutton__/apiCheck", { password: this.password, ver: this.verList[this.verIndex].value, verName: this.verList[this.verIndex].label })];
                                                case 3:
                                                    j = _j.sent();
                                                    this.mask = false;
                                                    if (j.result <= 0) {
                                                        this.alert = j.msg;
                                                        return [2];
                                                    }
                                                    this.selectedVer = this.verList[this.verIndex].label;
                                                    list = [];
                                                    for (_i = 0, _b = j.noMatch; _i < _b.length; _i++) {
                                                        file = _b[_i];
                                                        list.push(file + " - " + l("File mismatch."));
                                                    }
                                                    for (_c = 0, _d = j.miss; _c < _d.length; _c++) {
                                                        file = _d[_c];
                                                        list.push(file + " - " + l("File does not exist."));
                                                    }
                                                    for (file in j.missConst) {
                                                        list.push(file + " - " + l("Missing constants: ?.", [j.missConst[file].join(",")]));
                                                    }
                                                    for (_e = 0, _f = j.lib; _e < _f.length; _e++) {
                                                        lib = _f[_e];
                                                        list.push(l("Library: ?, current version: ?, latest version: ?.", [lib, j.lib[lib].localVer, j.lib[lib].ver]));
                                                    }
                                                    for (_g = 0, _h = j.libFolder; _g < _h.length; _g++) {
                                                        lib = _h[_g];
                                                        list.push(l("Library: ?, existing but missing satellite folders.", [lib]));
                                                    }
                                                    this.infoList = list;
                                                    this.onlineLibs = j.onlineLibs;
                                                    if (list.length === 0) {
                                                        this.alert = l("No problem.");
                                                    }
                                                    this.list.push(l("The \"mblob\" file was last updated:") + " " + j.lastTime);
                                                    return [2];
                                            }
                                        });
                                    });
                                },
                                reinstallFolder: function () {
                                    return __awaiter(this, void 0, void 0, function () {
                                        var j;
                                        return __generator(this, function (_a) {
                                            switch (_a.label) {
                                                case 0: return [4, this.confirm(l("Are you sure you're reinstalling the folder? Please be patient when the installation time may be long."))];
                                                case 1:
                                                    if (!(_a.sent())) {
                                                        return [2];
                                                    }
                                                    this.mask = true;
                                                    return [4, post(URL_BASE + "__Mutton__/apiReinstallFolder", { password: this.password, lib: this.localLibs[this.localLibsIndex].value, mirror: this.mirror })];
                                                case 2:
                                                    j = _a.sent();
                                                    this.mask = false;
                                                    if (j.result <= 0) {
                                                        this.alert = j.msg;
                                                        return [2];
                                                    }
                                                    return [4, this.getLocalLibs()];
                                                case 3:
                                                    _a.sent();
                                                    this.alert = l("Successful.");
                                                    return [2];
                                            }
                                        });
                                    });
                                },
                                build: function () {
                                    return __awaiter(this, void 0, void 0, function () {
                                        var j;
                                        return __generator(this, function (_a) {
                                            switch (_a.label) {
                                                case 0:
                                                    this.mask = true;
                                                    return [4, post(URL_BASE + "__Mutton__/apiBuild", { password: this.password })];
                                                case 1:
                                                    j = _a.sent();
                                                    this.mask = false;
                                                    if (j.result <= 0) {
                                                        this.alert = j.msg;
                                                        return [2];
                                                    }
                                                    this.alert = l("Successful.");
                                                    return [2];
                                            }
                                        });
                                    });
                                },
                                getLocalLibs: function () {
                                    return __awaiter(this, void 0, void 0, function () {
                                        var j;
                                        return __generator(this, function (_a) {
                                            switch (_a.label) {
                                                case 0:
                                                    this.mask = true;
                                                    return [4, post(URL_BASE + "__Mutton__/apiGetLocalLibs", { password: this.password })];
                                                case 1:
                                                    j = _a.sent();
                                                    this.mask = false;
                                                    if (j.result <= 0) {
                                                        this.alert = j.msg;
                                                        return [2];
                                                    }
                                                    this.localLibs = j.list;
                                                    return [2];
                                            }
                                        });
                                    });
                                },
                                confirm: function (txt) {
                                    return __awaiter(this, void 0, void 0, function () {
                                        var _this = this;
                                        return __generator(this, function (_a) {
                                            return [2, new Promise(function (resolve, reject) { return __awaiter(_this, void 0, void 0, function () {
                                                    return __generator(this, function (_a) {
                                                        this.confirmTxt = txt;
                                                        this.confirmResolve = resolve;
                                                        return [2];
                                                    });
                                                }); })];
                                        });
                                    });
                                }
                            }
                        });
                        return [2];
                }
            });
        });
    });
    document.addEventListener("touchstart", function () { });
    function loadScript(paths) {
        var _this = this;
        return new Promise(function (resolve, reject) { return __awaiter(_this, void 0, void 0, function () {
            var _i, paths_1, path, pathLio, e_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        _a.trys.push([0, 5, , 6]);
                        if (!(paths.length > 0)) return [3, 4];
                        _i = 0, paths_1 = paths;
                        _a.label = 1;
                    case 1:
                        if (!(_i < paths_1.length)) return [3, 4];
                        path = paths_1[_i];
                        pathLio = path.lastIndexOf("?");
                        if (pathLio !== -1) {
                            path = path.slice(0, pathLio);
                        }
                        if (headElement.querySelector("[data-res=\"" + path + "\"]")) {
                            return [3, 3];
                        }
                        return [4, _loadScript(path)];
                    case 2:
                        _a.sent();
                        _a.label = 3;
                    case 3:
                        _i++;
                        return [3, 1];
                    case 4:
                        resolve();
                        return [3, 6];
                    case 5:
                        e_1 = _a.sent();
                        reject(e_1);
                        return [3, 6];
                    case 6: return [2];
                }
            });
        }); });
    }
    function _loadScript(path) {
        var _this = this;
        return new Promise(function (resolve, reject) { return __awaiter(_this, void 0, void 0, function () {
            var script;
            return __generator(this, function (_a) {
                script = document.createElement("script");
                script.setAttribute("data-res", path);
                script.addEventListener("load", function () {
                    resolve();
                });
                script.addEventListener("error", function (e) {
                    reject(e);
                });
                script.src = path;
                headElement.appendChild(script);
                return [2];
            });
        }); });
    }
    function post(url, data) {
        var _this = this;
        return new Promise(function (resolve, reject) { return __awaiter(_this, void 0, void 0, function () {
            var header, body, k, res, text, ct, e_2;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        _a.trys.push([0, 6, , 7]);
                        header = new Headers();
                        body = new FormData();
                        for (k in data) {
                            if (data[k] !== undefined) {
                                body.append(k, data[k]);
                            }
                        }
                        body.append("_xsrf", _xsrf);
                        return [4, fetch(url + "?l=" + local, {
                                method: "POST",
                                headers: header,
                                credentials: "include",
                                body: body
                            })];
                    case 1:
                        res = _a.sent();
                        text = void 0;
                        ct = res.headers.get("Content-Type") || "";
                        if (!(ct.indexOf("json") !== -1)) return [3, 3];
                        return [4, res.json()];
                    case 2:
                        text = _a.sent();
                        return [3, 5];
                    case 3: return [4, res.text()];
                    case 4:
                        text = _a.sent();
                        _a.label = 5;
                    case 5:
                        resolve(text);
                        return [3, 7];
                    case 6:
                        e_2 = _a.sent();
                        reject(e_2);
                        return [3, 7];
                    case 7: return [2];
                }
            });
        }); });
    }
    function sleep(timeout) {
        var _this = this;
        return new Promise(function (resolve, reject) { return __awaiter(_this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                setTimeout(function () {
                    resolve();
                }, timeout);
                return [2];
            });
        }); });
    }
    function l(key, data) {
        if (data === void 0) { data = null; }
        if (!__LOCALE_OBJ[key]) {
            return "LocaleError";
        }
        if (data) {
            var str = __LOCALE_OBJ[key];
            for (var i = 0; i < data.length; ++i) {
                str.replace("?", data[i]);
            }
            return str;
        }
        else {
            return __LOCALE_OBJ[key];
        }
    }
})(__Mutton__ || (__Mutton__ = {}));
