<?php

include_once "../libs/main.php";
include_once "../libs/account.php";
include_once "../libs/page.php";

$page = new page();
$account = $page->getAccountObj();

$auth = new token_auth();

if($account->getLoginStatus()){
    header("Location: ../home/");
    exit();
}
else{
    if(isset($_POST["name"]) && isset($_POST["pass"])){
        if(isset($_POST["form_token"]) && $auth->auth($_POST["form_token"], true, false)){
            $page->setPageInfo([
                "title" =>  "処理中..."
            ]);

            $name = $_POST["name"];
            $pass = $_POST["pass"];

            if($account->create($name, $pass)){
                //アカウント作成&ログイン成功
                URI::moveto(URI::get_PATH(URI::HOME_PAGE));
            }
            else{
                //アカウント作成失敗
                URI::moveto(URI::get_PATH(URI::SIGNUP_PAGE));
            }

            exit;
        }
        else{
            //トークン認証失敗
            URI::moveto(URI::get_PATH(URI::SIGNUP_PAGE));
        }
    }
}
    
$page->setPageInfo([
    "title" =>  "アカウントの作成",
    "js"    =>  [
        "../js/main.js",
        ["../js/signup.js", page::JS_INBODY]
    ],
    "css"   =>  [
        "../style/main.css"
    ]
]);

$auth->set_token();
$form_token = $auth->get_token();

?>

<!DOCTYPE html>
<html lang="ja" id="signup" <?=page::OGP_PREFIX?>>
<head>
    <?=$page->genPage(page::HEAD_C)?>
</head>
<body>
    <main>
        <div>
            <h1>アカウントの作成</h1>
        </div>
        <div id="create_form">
            <form id="create_account" name="create_account" action="" method="POST" >
                <p>ID<span class="small">※4~32文字で、英数字(A~Z,a~z,0~9)と'_'(アンダーバー)が利用可能です</span></p>
                <input type="text" name="name" value="" required />
                <p>パスワード<span class="small">※6~32文字で、英文字(A~Z,a~z)と数字(0~9)を共に含んでいる必要があります</span></p>
                <input type="password" name="pass" required />
                <p>パスワード(確認)<span class="small">※クリップボードからの貼り付けは出来ません</span></p>
                <input type="password" name="pass_chk" onpaste="return false;" required />
                <input type="hidden" name="form_token" value="<?=$form_token?>" />
                <input type="submit" value="作成" />
            </form>
        </div>
    </main>

    <?=$page->genPage(page::JS_INBODY)?>
</body>
</html>