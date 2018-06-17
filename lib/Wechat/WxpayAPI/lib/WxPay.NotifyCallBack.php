<?php

class WxPayNotifyCallBack extends WxPayNotify {

    /* @var $_callback callable */
    private $_callback = NULL;

    public function setCallback(callable $callback) {
        $this->_callback = $callback;
    }

    //查询订单
    public function Queryorder($transaction_id) {

        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \WxPayApi::orderQuery($input);

        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS") {
            return true;
        }
        return false;

    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg) {

        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }
        // 查询微信订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }
        /*
         * $data['out_trade_no']: 传入的本系统的订单ID
         * $data['transaction_id']: 微信的订单ID
         */
        if ($this->_callback !== NULL) {
            call_user_func($this->_callback, $data);
        }
        return true;

    }

}

