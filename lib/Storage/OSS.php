<?php
/**
 * User: JianSuoQiYue
 * Date: 2015/10/26 14:23
 * Last: 2018-12-12 20:05:48
 */

declare(strict_types = 1);

namespace lib\Storage;

use OSS\OssClient;

require LIB_PATH.'Storage/OSS/autoload.php';

class OSS implements IStorage {

    /** @var OssClient */
    private $_link = NULL;

    // --- 设置项 ---
    private $_aki = NULL;
    private $_aks = NULL;
    private $_ep = NULL;
    private $_epi = NULL;
    private $_internal = false;
    private $_bucket = NULL;
    private $_endpoint = NULL;

    /**
     * OSS constructor.
     * @param array $opt
     * @throws \OSS\Core\OssException
     */
    public function __construct(array $opt = []) {
        $this->_aki = isset($opt['accessKeyId']) ? $opt['accessKeyId'] : STORAGE_OSS_ACCESS_KEY_ID;
        $this->_aks = isset($opt['accessKeySecret']) ? $opt['accessKeySecret'] : STORAGE_OSS_ACCESS_KEY_SECRET;
        $this->_ep = isset($opt['endpoint']) ? $opt['endpoint'] : STORAGE_OSS_ENDPOINT;
        $this->_epi = isset($opt['endpointInternal']) ? $opt['endpointInternal'] : STORAGE_OSS_ENDPOINT_IN;
        $this->_internal = isset($opt['internal']) ? $opt['internal'] : STORAGE_OSS_INTERNAL;
        $this->_bucket = isset($opt['bucket']) ? $opt['bucket'] : STORAGE_OSS_BUCKET;
        $this->_endpoint = $this->_internal ? $this->_epi : $this->_ep;
        $this->_link = new OssClient($this->_aki, $this->_aks, $this->_endpoint);
    }

    public function putFile(string $path, $content, bool $gzip = false): array {
        $opt = [];
        if ($gzip) {
            $content = gzencode($content);
            $opt[OssClient::OSS_HEADERS] = [
                'Content-Encoding' => 'gzip'
            ];
        }
        return $this->_link->putObject($this->_bucket, $path, $content, $opt);
    }

    public function uploadFile(string $fromPath, string $toPath): bool {
        try {
            $this->_link->uploadFile($this->_bucket, $toPath, $fromPath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteFile(string $path): void {
        $this->_link->deleteObject($this->_bucket, $path);
    }

    public function doesExist(string $path): bool {
        return $this->_link->doesObjectExist($this->_bucket, $path);
    }

    // --- 用于客户端直传 ---
    private function _gmtIso8601(int $time): string {
        $dtStr = date('c', $time);
        $myDateTime = new \DateTime($dtStr);
        $expiration = $myDateTime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . "Z";
    }

    /**
     $rtn = $oss->getSignature([
        'dir' => 'upload/' . date('Y/m/d') . '/' . date('His') . Text::random() . '.txt',   // 文件上传路径
        'callback' => HTTP_PATH . 'api/ossCallback',    // 回调 URL
        'size' => 10485760,                             // 限制文件大小 10 M
        'data' => [
            'filename' => $_POST['file_name']           // data 数组内容将会被全部原样回传
        ]
     ]);
     * @param array $opt ['dir' => '', 'callback' => '', 'size' => 3145728] 3M
     * @return array
     */
    public function getSignature(array $opt = []): array {
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
        $expiration = $this->_gmtIso8601($end);

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
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->_aks, true));

        $response = [
            'accessid' => $this->_aki,
            'host' => 'https://' . $this->_bucket . '.' . $this->_ep,
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
    public function callback() {

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
            $authStr = urldecode($path) . "\n" . $body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)) . substr($path, $pos, strlen($path) - $pos) . "\n" . $body;
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

