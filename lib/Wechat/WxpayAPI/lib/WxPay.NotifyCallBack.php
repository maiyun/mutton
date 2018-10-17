<?php

class WxPayNotifyCallBack extends WxPayNotify {

    /* @var $_callback callable */
    private $_callback = NULL;

    public function setCallback(callable $callback) {
        $this->_callback = $callback;
    }

    //查询订单
    public function Queryorder($transaction_id, $config) {

        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);

        $result = WxPayApi::orderQuery($config, $input);
        //Log::DEBUG("query:" . json_encode($result));
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;

    }

    //重写回调处理函数
    public function NotifyProcess($objData, $config, &$msg) {
        $data = $objData->GetValues();
        //TODO 1、进行参数校验
        if(!array_key_exists("return_code", $data)
            ||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
            //TODO失败,不是支付成功的通知
            //如果有需要可以做失败时候的一些清理处理，并且做一些监控
            $msg = "异常异常";
            return false;
        }
        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }

        //TODO 2、进行签名验证
        try {
            $checkResult = $objData->CheckSign($config);
            if($checkResult == false){
                //签名错误
                \sys\log("签名错误...");
                return false;
            }
        } catch(Exception $e) {
            \sys\log(json_encode($e));
        }

        //TODO 3、处理业务逻辑
        //Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array();

        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"], $config)){
            $msg = "订单查询失败";
            return false;
        }

        if ($this->_callback !== NULL) {
            call_user_func($this->_callback, $data);
        }
        return true;

    }

}

