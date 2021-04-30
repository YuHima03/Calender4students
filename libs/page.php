<?php

include_once __DIR__."/main.php";
include_once __DIR__."/account.php";

/**
 * ページ生成関連
 */
class page{
    public const BR_TAG = "<br />";

    /**これも参照になるはず */
    private ?\account $account = null;

    private array $pageInfo = [
        "title" =>  "Untitled"
    ];

    /**
     * @param \account|null &$accountObj 参照だゾ
     */
    public function __construct(?\account &$accountObj = null){
        if(is_null($accountObj)){
            $accountObj = new account();
        }

        $this->account = $accountObj;

        return;
    }

    /**`account`クラスオブジェクトを取得 (ポインタなので操作可能) */
    public function getAccountObj() :\account{
        return $this->account;
    }
    
    public function setPageInfo(array $info = []){
        
    }
}

?>