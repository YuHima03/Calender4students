<?php

include_once "../libs/main.php";
include_once "../libs/account.php";
include_once "../libs/page.php";

$loginResult = false;

$account = new account();

$token_auth = new token_auth();

if(isset($_POST["username"], $_POST["pass"], $_POST["ftoken"]) && $token_auth->auth($_POST["ftoken"], true, false)){
    //トークン認証成功(自動削除)
    $account->login($_POST["username"], hash("sha512", $_POST["pass"]), (isset($_POST["auto_login"]) && $_POST["auto_login"] === "on"));
}
else{
    //認証失敗(トークン新規生成)
    $account->logout(true, account::ERROR_BAD_LOGIN_REQUEST);
}

$token_auth->set_token();

$page = new Page($account);

//以下デバッグ用

if($account->getLoginStatus()){
    echo $account->getUserName()."としてログイン済みです".page::BR_TAG;
}
else{
    echo "まだログインしていません".page::BR_TAG;
}

if($account->isAdmin()){
    echo "あなたは管理者です".page::BR_TAG;
}
else if($account->isDeveloper()){
    echo "あなたはデベロッパです".page::BR_TAG;
}

$page->setPageInfo([
    "title" =>  "ログイン"
]);
echo "<pre style='background: whitesmoke;'>", var_dump($page->getPageInfo()), "</pre>";

?>

<!DOCTYPE html>
<html lang="ja" id="_login" <?=page::OGP_PREFIX?>>
<head>
    <?=$page->genPage(page::HEAD_C)?>
</head>
<body>
    <main>
        <div id="container">
            <div class="title">
                <h2>ログイン(β版)</h2>
            </div>
            <div>
                <form action="" method="POST" onsubmit="return true;">
                    <label>
                        <span>アカウント名</span>
                        <input type="text" name="username" value="<?=(isset($_POST['account_id']))?"{$_POST['account_id']}":"";?>" required/>
                    </label>
                    <label>
                        <span>パスワード</span>
                        <input type="password" name="pass" autocomplete="password" required />
                    </label>
                    <label>
                        <input type="checkbox" name="auto_login"/>
                        <span>自動ログイン</span>
                    </label>
                    <input type="submit" value="ログイン" />
                    <!--トークン-->
                    <input type="hidden" name="ftoken" value="<?=$token_auth->get_token()?>" />
                </form>
            </div>
        </div>
    </main>
</body>
</html>