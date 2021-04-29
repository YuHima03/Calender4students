<?php

include_once "../libs/main.php";
include_once "../libs/account.php";
include_once "../libs/page.php";

$loginResult = false;

$account = new account();

if(isset($_POST["username"], $_POST["pass"])){
    $account->login($_POST["username"], hash("sha512", $_POST["pass"]), (isset($_POST["auto_login"]) && $_POST["auto_login"] === "on"));
}

$page = new Page($account);

if($account->getLoginStatus()){
    echo $account->getUserName()."としてログイン済みです".page::BR_TAG;
}
else{
    echo "まだログインしていません".page::BR_TAG;
}

if($account->is_admin()){
    echo "あなたは管理者です".page::BR_TAG;
}
else if($account->is_developer()){
    echo "あなたはデベロッパです".page::BR_TAG;
}

var_dump(getRandStr(32));

?>

<!DOCTYPE html>
<html lang="ja" id="_login">
<body>
    <main>
        <div id="container">
            <div class="title">
                <h2>ログイン(beta)</h2>
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
                    <input type="hidden" name="form_token" value="" />
                </form>
            </div>
        </div>
    </main>
</body>
</html>