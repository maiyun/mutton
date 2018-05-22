<?php
/**
 * Created by PhpStorm.
 * User: yunbo
 * Date: 2015/10/26
 * Time: 14:23
 */

namespace C\lib {

    require LIB_PATH.'OSS/OssClient.php';
    require LIB_PATH.'OSS/Core/OssUtil.php';
    require LIB_PATH.'OSS/Core/MimeTypes.php';
    require LIB_PATH.'OSS/Core/OssException.php';
    require LIB_PATH.'OSS/Http/RequestCore.php';
    require LIB_PATH.'OSS/Http/ResponseCore.php';
    require LIB_PATH.'OSS/Result/Result.php';
    require LIB_PATH.'OSS/Result/PutSetDeleteResult.php';
    require LIB_PATH.'OSS/Result/ExistResult.php';

    use OSS\OssClient;

    class Oss {

        /**
         * @var OssClient
         */
        private static $link = NULL;

        public static function connect() {

            self::$link = new OssClient(OSS_ACCESS_KEY_ID, OSS_ACCESS_KEY_SECRET, OSS_ENDPOINT);

        }

        public static function putFile($path, $content, $gzip = false) {

            $opt = [];
            if($gzip) {
                $content = gzencode($content);
                $opt[OssClient::OSS_HEADERS] = [
                    'Content-Encoding' => 'gzip'
                ];
            }
            self::$link->putObject(OSS_BUCKET, $path, $content, $opt);
            return true;

        }

        public static function uploadFile($file, $path) {

            self::$link->uploadFile(OSS_BUCKET, $path, $file);
            return true;

        }

        public static function deleteFile($path) {

            self::$link->deleteObject(OSS_BUCKET, $path);
            return true;

        }

        public static function isExist($path) {

            return self::$link->doesObjectExist(OSS_BUCKET, $path);

        }

        // --- 用于客户端直传 ---
        private static function _gmtIso8601($time) {
            $dtStr = date('c', $time);
            $myDateTime = new \DateTime($dtStr);
            $expiration = $myDateTime->format(\DateTime::ISO8601);
            $pos = strpos($expiration, '+');
            $expiration = substr($expiration, 0, $pos);
            return $expiration . "Z";
        }

        // --- ['dir' => '', 'callback' => '', 'size' => 3145728] 3M ---
        public static function getSignature($opt = []) {
            $opt['dir'] = isset($opt['dir']) ? $opt['dir'] : '';
            $opt['size'] = isset($opt['size']) ? $opt['size'] : 3145728; // 3M
            $opt['data'] = isset($opt['data']) ? ['data' => $opt['data']] : false;

            $callback_param = [
                'callbackUrl' => $opt['callback'],
                'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}' . ($opt !== false ? '&' . http_build_query($opt['data']) : ''),
                'callbackBodyType' => "application/x-www-form-urlencoded"
            ];
            $callback_string = json_encode($callback_param);

            $base64_callback_body = base64_encode($callback_string);
            $now = time();
            $expire = 10; //设置该 policy 超时时间是 10s. 即这个policy过了这个有效时间，将不能访问
            $end = $now + $expire;
            $expiration = self::_gmtIso8601($end);

            // --- 最大文件大小.用户可以自己设置 ---
            $condition = [0 => 'content-length-range', 1 => 0, 2 => $opt['size']];
            $conditions[] = $condition;

            // --- 设置bucket (官方示例没有) ---
            // $bucket = [0 => 'eq', 1 => '$bucket', 2 => OSS_BUCKET];
            // $conditions[] = $bucket;

            //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
            $start = [0 => 'starts-with', 1 => '$key', 2 => $opt['dir']];
            $conditions[] = $start;

            $arr = ['expiration' => $expiration, 'conditions' => $conditions];
            //echo json_encode($arr);
            //return;
            $policy = json_encode($arr);
            $base64_policy = base64_encode($policy);
            $string_to_sign = $base64_policy;
            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, OSS_ACCESS_KEY_SECRET, true));

            $response = [
                'accessid' => OSS_ACCESS_KEY_ID,
                'host' => '//' . OSS_BUCKET . '.' . OSS_ENDPOINT_NI,
                'policy' => $base64_policy,
                'signature' => $signature,
                'expire' => $end,
                'callback' => $base64_callback_body,
                // 这个参数是设置用户上传指定的KEY
                'dir' => $opt['dir']
            ];
            return $response;
        }

        // --- 回调代码 ---
        public static function callback() {

            // 1.获取OSS的签名header和公钥url header
            $authorizationBase64 = '';
            $pubKeyUrlBase64 = '';
            /*
             * 注意：如果要使用HTTP_AUTHORIZATION头，你需要先在apache或者nginx中设置rewrite，以apache为例，修改
             * 配置文件/etc/httpd/conf/httpd.conf(以你的apache安装路径为准)，在DirectoryIndex index.php这行下面增加以下两行
                RewriteEngine On
                RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
             * */
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
            }
            if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL'])) {
                $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
            }

            if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '') {
                header('http/1.1 403 Forbidden');
                return false;
            }

            // 2.获取OSS的签名
            $authorization = base64_decode($authorizationBase64);

            // 3.获取公钥
            $pubKeyUrl = base64_decode($pubKeyUrlBase64);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            $pubKey = curl_exec($ch);
            if ($pubKey == "") {
                header('http/1.1 403 Forbidden');
                return false;
            }

            // 4.获取回调body
            $body = file_get_contents('php://input');

            // 5.拼接待签名字符串
            $authStr = '';
            $path = $_SERVER['REQUEST_URI'];
            $pos = strpos($path, '?');
            if ($pos === false) {
                $authStr = urldecode($path)."\n".$body;
            } else {
                $authStr = urldecode(substr($path, 0, $pos)).substr($path, $pos, strlen($path) - $pos)."\n".$body;
            }

            // 6.验证签名
            $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
            if ($ok == 1) {
                header("Content-Type: application/json");
                parse_str($body, $result);
                // filename: 2017/11/07/100145js9qrumh.jpg, size: 128284, mimeType: image/jpeg, height: 800, width: 800
                return $result;
            } else {
                header('http/1.1 403 Forbidden');
                return false;
            }

        }

    }

}

