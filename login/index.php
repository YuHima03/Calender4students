<?php

include_once "../libs/main.php";
include_once "../libs/account.php";
include_once "../libs/page.php";

$loginResult = false;

$account = new account();

//ログイン処理
if(isset($_POST["username"], $_POST["pass"])){
    //トークン認証
    if(isset($_SESSION["_ftoken"], $_POST["ftoken"])){
        $account->login($_POST["username"], hash("sha512", $_POST["pass"]), (isset($_POST["auto_login"]) && $_POST["auto_login"] === "on"));
        //トークンはもう使わない
        unset($_SESSION["_ftoken"]);
    }
    else{
        //認証失敗(トークン新規生成)
        $account->logout(true, account::ERROR_BAD_LOGIN_REQUEST);
        $ftoken = getRandStr(32);
        $_SESSION["_ftoken"] = $ftoken;
    }
}

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
                    <input type="hidden" name="ftoken" value="<?=$ftoken?>" />
                </form>
            </div>
        </div>
    </main>
</body>
</html>