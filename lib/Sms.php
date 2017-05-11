<?php

/* For AliyunMNS 1.3.4 */

namespace C\lib {

    require_once(LIB_PATH . 'AliyunMNS/mns-autoloader.php');

    use AliyunMNS\Client;
    use AliyunMNS\Topic;
    use AliyunMNS\Constants;
    use AliyunMNS\Model\MailAttributes;
    use AliyunMNS\Model\SmsAttributes;
    use AliyunMNS\Model\BatchSmsAttributes;
    use AliyunMNS\Model\MessageAttributes;
    use AliyunMNS\Exception\MnsException;
    use AliyunMNS\Requests\PublishMessageRequest;

    class Sms {

        private static $endPoint = '';
        private static $accessId = '';
        private static $accessKey = '';

        private static $client = NULL;

        public static function connect() {

            self::$endPoint = MNS_ENDPOINT;
            self::$accessId = MNS_ACCESS_ID;
            self::$accessKey = MNS_ACCESS_KEY;

            self::$client = new Client(self::$endPoint, self::$accessId, self::$accessKey);

        }

        public static function send($topicName, $signName, $tempCode, $phone, $data) {

            /**
             * Step 2. 获取主题引用
             */
            $topic = self::$client->getTopicRef($topicName);
            /**
             * Step 3. 生成SMS消息属性
             */
            // 3.1 设置发送短信的签名（SMSSignName）和模板（SMSTemplateCode）
            $batchSmsAttributes = new BatchSmsAttributes($signName, $tempCode);
            // 3.2 （如果在短信模板中定义了参数）指定短信模板中对应参数的值
            $batchSmsAttributes->addReceiver($phone, $data);
            $messageAttributes = new MessageAttributes(array($batchSmsAttributes));
            /**
             * Step 4. 设置SMS消息体（必须）
             *
             * 注：目前暂时不支持消息内容为空，需要指定消息内容，不为空即可。
             */
            $messageBody = "smsmessage";
            /**
             * Step 5. 发布SMS消息
             */
            $request = new PublishMessageRequest($messageBody, $messageAttributes);
            try {
                $res = $topic->publishMessage($request);
                //echo $res->isSucceed();
                //echo "\n";
                //echo $res->getMessageId();
                //echo "\n";
                return true;
            }
            catch (MnsException $e) {
                // echo $e;
                return false;
            }

        }

    }

}