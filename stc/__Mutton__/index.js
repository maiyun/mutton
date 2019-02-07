"use strict";
document.addEventListener('DOMContentLoaded', function() {
    // --- 组件注册 ---
    // --- Button ---
    Vue.component("mu-button", {
        template: '<div class="button" tabindex="0"><div class="button__in"><div class="button__txt"><slot></div></div></div>'
    });
    // --- List ---
    Vue.component("mu-list", {
        data: function() {
            return {
                selectedIndex: 0
            };
        },
        props: {
            list: {
                default: []
            },
            height: {
                default: '200px'
            }
        },
        template: '<div class="list" tabindex="0"><div class="list__in" :style="{\'height\': height}"><div v-for="(val, index) of list" class="list__item" :class="{\'selected\': selectedIndex === index}" @click="selectedIndex = index">{{val}}</div></div></div>'
    });
	var vue = new Vue({
		el: '#vue',
		data: {
            mask: false,
            alert: '',
			tab: tab,
            // --- Password ---
            password: '',
            // --- Check ---
            code: '',
            list: [],
            // --- Build ---
            output: '',
            // --- Config ---
            configTxt: "<?php\nconst __MUTTON__PWD = 'Your password';\n\n"
        },
        methods: {
		    // --- Check ---
            check: function (strict) {
                var _this = this;
                strict = strict || false;
                _this.mask = true;
                post(HTTP_BASE+'__Mutton__/apiCheck', {password: _this.password, code: _this.code, strict: strict ? '1' : '0'}, function (j) {
                    _this.mask = false;
                    if (j.result > 0) {
                        var list = j.list;
                        if (strict) {
                            list = list.concat(j.slist);
                        }
                        _this.list = list;
                        if (j.list.length === 0) {
                            _this.alert = 'There are no content to update.';
                        }
                    } else {
                        _this.alert = j.msg;
                    }
                });
            },
		    // --- Build ---
            build: function () {
                var _this = this;
                _this.mask = true;
                post(HTTP_BASE+'__Mutton__/apiBuild', {password: _this.password}, function (j) {
                    _this.mask = false;
                    if (j.result > 0) {
                        _this.output = j.output;
                    } else {
                        _this.alert = j.msg;
                    }
                });
            }
        }
	});
	document.addEventListener('touchstart', function() {});
	// --- 检查 fetch ---
    if (typeof fetch !== "function") {
        document.head.appendChild(document.createElement('script')).src = '//cdn.jsdelivr.net/npm/fetch-polyfill@0.8.2/fetch.min.js';
    }
});

// --- FETCH ---

function get(url, success, error) {
    success = success || function(){};
    error = error || function(){};
    fetch(url, {
        method: "GET",
        credentials: "include"
    }).then(function(res){return res.json()}).then(function(j) {
        success(j);
    }).catch(function(err) {
        error(err);
    });
}

function post(url, data, success, error) {
    success = success || function(){};
    error = error || function(){};
    var header = new Headers();
    var body = new FormData();
    for (var k in data) {
        if (data[k] !== undefined) {
            body.append(k, data[k]);
        }
    }
    fetch(url, {
        method: "POST",
        headers: header,
        credentials: "include",
        body: body
    }).then(function(res){return res.json()}).then(function(j) {
        success(j);
    }).catch(function(err) {
        error(err);
    });
}

