<?php
/**
 * 認証用
 */

include_once "../libs/main.php";
include_once "../libs/account.php";

$result = [
    "result"    =>  false,
    "timestamp" =>  time()
];

if(isset($_POST["timestamp"])){
    $timestamp = (int)$_POST["timestamp"];

    //送信時刻が現在時刻の1分以内の場合のみ通す
    if($timestamp <= time() + 5 && time() - 60 <= $timestamp){
        $account = new account();

        //ログイン認証
        if($account->getLoginStatus() === true){
            $result["result"] = true;
        }
    }
}

//JSON形式で返す
echo json_encode($result, JSON_UNESCAPED_UNICODE);

?>