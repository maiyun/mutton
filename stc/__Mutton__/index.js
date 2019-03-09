"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : new P(function (resolve) { resolve(result.value); }).then(fulfilled, rejected); }
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
var _this = this;
window.onerror = function (msg, uri, line, col, err) {
    if (err) {
        alert("Error:\n" + err.message + "\n" + err.stack + "\nLine: " + line + "\nColumn: " + col);
    }
    else {
        console.log(msg);
    }
};
var headEle;
var outPath = [];
document.addEventListener("DOMContentLoaded", function () {
    headEle = document.getElementsByTagName("head")[0];
    var callback = function () { return __awaiter(_this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!(typeof fetch !== "function")) return [3, 2];
                    return [4, loadScript(["https://cdn.jsdelivr.net/npm/whatwg-fetch@3.0.0/fetch.min.js"])];
                case 1:
                    _a.sent();
                    _a.label = 2;
                case 2:
                    Vue.component("mu-button", {
                        template: "<div class=\"button\" tabindex=\"0\"><div class=\"button__in\"><div class=\"button__txt\"><slot></div></div></div>"
                    });
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
                            }
                        },
                        template: "<div class=\"list\" tabindex=\"0\"><div class=\"list__in\" :style=\"{'height': height}\"><div v-for=\"(val, index) of list\" class=\"list__item\" :class=\"{'selected': selectedIndex === index}\" @click=\"selectedIndex = index\">{{val}}</div></div></div>"
                    });
                    new Vue({
                        el: "#vue",
                        data: {
                            mask: false,
                            alert: "",
                            tab: tab,
                            password: "",
                            code: "",
                            list: [],
                            output: "",
                            configTxt: "<?php\nconst __MUTTON__PWD = 'Your password';\n\n"
                        },
                        methods: {
                            check: function (strict, full) {
                                return __awaiter(this, void 0, void 0, function () {
                                    var j, list;
                                    return __generator(this, function (_a) {
                                        switch (_a.label) {
                                            case 0:
                                                strict = strict || false;
                                                full = full || false;
                                                this.mask = true;
                                                return [4, post(HTTP_BASE + "__Mutton__/apiCheck", { password: this.password, code: this.code, strict: strict ? "1" : "0", full: full ? "1" : "0" })];
                                            case 1:
                                                j = _a.sent();
                                                this.mask = false;
                                                if (j.result <= 0) {
                                                    this.alert = j.msg;
                                                }
                                                list = j.list;
                                                if (strict) {
                                                    list = list.concat(j.slist);
                                                }
                                                if (full) {
                                                    if (j.flist.length > 0) {
                                                        list.push("--------------------------------------------------");
                                                        list = list.concat(j.flist);
                                                    }
                                                }
                                                this.list = list;
                                                if (list.length === 0) {
                                                    this.alert = "There are no content to update.";
                                                }
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
                                                return [4, post(HTTP_BASE + "__Mutton__/apiBuild", { password: this.password })];
                                            case 1:
                                                j = _a.sent();
                                                this.mask = false;
                                                if (j.result > 0) {
                                                    this.output = j.output;
                                                }
                                                else {
                                                    this.alert = j.msg;
                                                }
                                                return [2];
                                        }
                                    });
                                });
                            }
                        }
                    });
                    return [2];
            }
        });
    }); };
    if (typeof Promise !== "function") {
        var script = document.createElement("script");
        script.addEventListener("load", function () {
            callback();
        });
        script.addEventListener("error", function () {
            alert("Load error.");
        });
        script.src = "https://cdn.jsdelivr.net/npm/promise-polyfill@8.1.0/dist/polyfill.min.js";
        headEle.appendChild(script);
    }
    else {
        callback();
    }
});
document.addEventListener("touchstart", function () { });
function loadScript(paths) {
    var _this = this;
    return new Promise(function (resolve, reject) { return __awaiter(_this, void 0, void 0, function () {
        var i, e_1;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    _a.trys.push([0, 5, , 6]);
                    if (!(paths.length > 0)) return [3, 4];
                    i = 0;
                    _a.label = 1;
                case 1:
                    if (!(i < paths.length)) return [3, 4];
                    if (!(outPath.indexOf(paths[i]) === -1)) return [3, 3];
                    outPath.push(paths[i]);
                    return [4, loadOutScript(paths[i])];
                case 2:
                    _a.sent();
                    _a.label = 3;
                case 3:
                    ++i;
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
function loadOutScript(path) {
    var _this = this;
    return new Promise(function (resolve, reject) { return __awaiter(_this, void 0, void 0, function () {
        var script;
        return __generator(this, function (_a) {
            script = document.createElement("script");
            script.addEventListener("load", function () {
                resolve();
            });
            script.addEventListener("error", function () {
                reject("Load error.");
            });
            script.src = path;
            headEle.appendChild(script);
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
                    return [4, fetch(url, {
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
