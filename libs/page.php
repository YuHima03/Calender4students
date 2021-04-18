<?php

include_once __DIR__."/main.php";
include_once __DIR__."/account.php";

class page{
    public const BR_TAG = "<br />";

    /**これも参照になるはず */
    private ?\account $account = null;

    /**
     * @param \account|null &$accountObj 参照だゾ
     */
    public function __construct(?\account &$accountObj = null){
        if(is_null($accountObj)){
            //再ログイン(成敗は問わない)
            $accountObj = new account();   
        }

        $this->account = $accountObj;

        return;
    }
}

?>