<?php

include_once "../libs/main.php";
include_once "../libs/account.php";
include_once "../libs/page.php";

$account = new account();
$auth = new token_auth();

if($account->getLoginStatus()){
    URI::moveto(URI::get_PATH(URI::HOME_PAGE));
}
else{
    $result = [
        "result" => false
    ];

    if(isset($_POST["name"]) && isset($_POST["pass"])){
        if(isset($_POST["form_token"]) && $auth->auth($_POST["form_token"], true, false)){
            $name = $_POST["name"];
            $pass = $_POST["pass"];

            if($account->create($name, $pass)){
                //アカウント作成&ログイン成功
                $result["result"] = true;
            }
            else{
                $result["error"] = $account->getLastError();
            }
        }
        else{
            $result["error"] = "Missing property => `form_token`";
        }
    }
    else{
        $result["error"] = "Missing property => `name` or `pass`";
    }

    echo json_encode($result, JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
}

?>