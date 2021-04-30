<?php

include_once __DIR__."/main.php";

class calenderOp{
    private \account $account = null;

    private ?string $dirpath = null;

    public function __construct(?\account &$accountObj){
        if(is_null($accountObj)){
            $accountObj = new account();
        }

        $this->account = $accountObj;

        $this->dirpath = dirname(__DIR__)."/data/".$this->account->getUUID();
    }

    /**
     * @return array|false
     */
    public function getJSON(){
        if($this->account->getLoginStatus() && is_string($this->dirpath) && is_dir($this->dirpath)){
            
        }
        else{
            return false;
        }
    }
}

?>