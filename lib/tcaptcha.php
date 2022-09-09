<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {"ver":"0.1","folder":false} - END
 * Date: 2022-09-08 15:26:32
 * Last: 2022-09-08 15:26:37
 */
declare(strict_types = 1);

namespace lib;

class Tcaptcha {

    const CO = 'recaptcha.net';
    const GL = 'www.google.com';

    /**
     * --- 页面初始化 script，一般放在 head 中 ---
     * @param int $mode 0-自动,1-第三方,2-本地
     * @param string $domain 不含中国大陆地区的业务请设置为 GL（也可不设置，更加全球通用）
     */
    public static function getScript(int $mode = 0, string $domain = self::CO): string {
        $echo = ['<style>' .
        '.tcaptcha-input{border:2px solid #c1c1c1;border-radius:2px;background-color:#fff;color:#555;flex:1;margin-right:10px;width:0;text-align:center;font-size:18px;font-weight:bold;-webkit-appearance:none;}' .
        '.tcaptcha-input:hover{border-color:#b2b2b2;}' .
        '.tcaptcha-input:focus{outline:none;border-color:#555;}' .
        '.tcaptcha-input:hover,.tcaptcha-input:focus{box-shadow:inset 0 1px 1px rgba(0,0,0,.1);}' .
        '</style>' .
        '<script>' .
        'var Tcaptcha={' .
            'error:0,' .
            'isReady:0,' .
            'readyFun:null,' .
            'cid:0,' .
            'ready:function(fun){'.
                'if(this.isReady>0){' .
                    'fun();' .
                '}else{' .
                    'this.readyFun=fun;' .
                '}' .
            '},' .
            'render:function(el,opt){' .
                'if(this.isReady===0){' .
                    'return false' .
                '}else if(this.isReady===1){' .
                    'if(this.error===0){' .
                        'return grecaptcha.render(el,opt)' .
                    '}else{' .
                        'el.innerHTML="reCaptcha failed.";' .
                        'return false' .
                    '}' .
                '}else{' .
                    'var id=++this.cid;' .
                    'el.innerHTML = \'<div style="border:1px solid #d3d3d3;border-radius:3px;display:inline-flex;background:#f9f9f9;box-shadow:0 0 4px 1px rgba(0,0,0,.08);width:304px;height:78px;box-sizing:border-box;padding:15px;align-items:center;justify-content:center;">\' + (opt.url?\'<input class="tcaptcha-input" id="tcaptchai-\'+id+\'"><img id="tcaptcha-\'+id+\'" data-url="\'+opt.url+\'" width="120" height="40" style="cursor:pointer;" onclick="Tcaptcha.reset(\'+id+\')">\':"Captcha failed.") + "</div>";' .
                    'Tcaptcha.reset(id);' .
                    'return id' .
                '}' .
            '},' .
            'reset:function(id){' .
                'if(id===false){' .
                    'return false' .
                '}' .
                'if(this.isReady===1){' .
                    'grecaptcha.reset(id)' .
                '}else{' .
                    'var c=document.getElementById("tcaptcha-"+id);' .
                    'if(!c){return false}' .
                    'c.src=c.dataset.url+"?"+Math.random();' .
                    'document.getElementById("tcaptchai-"+id).value=""' .
                '}' .
            '},' .
            'get:function(id){' .
                'if(id===false){' .
                    'return ""' .
                '}' .
                'if(this.isReady===1){' .
                    'return grecaptcha.getResponse(id)' .
                '}else{' .
                    'var c=document.getElementById("tcaptchai-"+id);' .
                    'if(!c){return ""}' .
                    'return c.value' .
                '}' .
            '}' .
        '};' .
        'function ontcaptchaload(){' .
            'Tcaptcha.isReady=1;' .
            'if(Tcaptcha.readyFun){Tcaptcha.readyFun()}' .
        '}'];
        if ($mode < 2) {
            $echo[] = 'function ontcaptchaerror(){';
            if ($mode === 0) {
                // --- 三方加载失败，则自动切换 ---
                $echo[] = 'Tcaptcha.isReady=2;' .
                'if(Tcaptcha.readyFun){Tcaptcha.readyFun()}';
            }
            else {
                // --- 三方加载失败，直接彻底失败 ---
                $echo[] = 'Tcaptcha.isReady=1;' .
                'Tcaptcha.error=1;' .
                'if(Tcaptcha.readyFun){Tcaptcha.readyFun()}';
            }
            $echo[] = '}' .
            '</script>' .
            '<script async src="https://' . $domain . '/recaptcha/api.js?onload=ontcaptchaload&render=explicit" onerror="ontcaptchaerror&&ontcaptchaerror()"></script>';
        }
        else {
            // --- 2 ---
            $echo[] = 'Tcaptcha.isReady=2;' .
            'if(Tcaptcha.readyFun){Tcaptcha.readyFun()}' . 
            '</script>';
        }
        return join('', $echo);
    }

    /**
     * --- 获取验证码对象 ---
     * @param int $mode
     * @param string $domain
     * @param int $len
     * @return Captcha|null
     */
    public static function get(int $mode = 0, string $domain = self::CO, int $len = 4): Captcha|null {
        if ($mode === 1) {
            // --- 强制第三方，不能获取本地验证码 ---
            return null;
        }
        if ($mode === 0) {
            // --- 自动切换，但是却请求本地验证码，检测是不是真的在线的不能访问了，若是，才显示本地验证码 ---
            $r = Net::get('https://' . $domain . '/recaptcha/api.js', [
                'timeout' => 2
            ]);
            if ($r->content && strpos($r->content, 'function') !== false) {
                // --- 能访问，不显示本地验证码 ---
                return null;
            }
        }
        return Captcha::get(240, 80, $len);
    }

    /**
     * --- 校验验证码是否正确 ---
     * @param string $val 用户提交的验证码，无视大小写
     * @param string $secret recaptcha 为通讯密钥，本地模式为服务器存储的验证码，无视大小写
     * @param string $domain
     */
    public static function verify(string $val, string $secret, string $domain = self::CO): bool {
        if (strlen($val) <= 6) {
            if (strtolower($val) !== strtolower($secret)) {
                return false;
            }
            return true;
        }
        // --- recaptcha ---
        $r = Net::post('https://' . $domain . '/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $val
        ]);
        if (!$r->content) {
            return false;
        }
        $j = json_decode($r->content);
        if (!$j->success) {
            return false;
        }
        return true;
    }

}

