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

    }

}

