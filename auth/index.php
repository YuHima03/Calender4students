<?php
/**
 * 認証用
 */

include_once "../libs/main.php";
include_once "../libs/account.php";

$result = [
    "result"    =>  false
];

if(isset($_POST["timestamp"], $_POST["token"])){
    if($_POST["token"] === $_SESSION["auth_token"]){
        $account = new account();

        if($account->getLoginStatus() === true){
            $result["result"] = true;
        }
    }
    else{
        unset($_SESSION["auth_token"]);
    }
}

//JSON形式で返す
echo json_encode($result, JSON_UNESCAPED_UNICODE);

?>