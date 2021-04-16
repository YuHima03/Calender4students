<?php

require_once __DIR__."/main.php";
require_once __DIR__."/db.php";

class account{
    public function __construct(){
        
    }

    /**
     * ログインする
     * @param string|null $userName nullの場合はcookieとセッションより再ログイン
     * @param string|null $pass SHA256で暗号化したものをぶち込む
     * @return bool ログインの成功/失敗
     */
    private function login($userName = null, $pass = null) : bool {
        $DB = new DB();

        if(is_null($userName)){
            //cookieとセッションの情報を照合して再ログイン
            if(isset($_COOKIE["_token"]) && isset($_SESSION["_token"])){
                
            }
            else{
                //ログイン失敗
                return false;
            }
        }
        else{

        }

        return true;
    }
}

?>