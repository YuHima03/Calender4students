<?php
/**
 * 認証用
 */

include_once "../libs/main.php";
include_once "../libs/account.php";

$result = [
    "result"    =>  false
];

if(isset($_POST["timestamp"])){
    $account = new account();

    if($account->getLoginStatus() === true){
        $result["result"] = true;
    }
}

//JSON形式で返す
echo json_encode($result, JSON_UNESCAPED_UNICODE);

?>