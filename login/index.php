<?php

include "../libs/C4S_main.php";

$page = new page(false);

$LANG_DATA = $page->get_lang_data()["login"];

//JavaScriptに渡すデータ
$PHP_DATA = [
    "mode"  =>  "normal",
    "account_error" =>  $page->get_account_info()["error"],
    "login_error"   =>  [],
    "lang"  =>  $LANG_DATA
];

if($page->get_account_info()["login"]){
    //ログイン済みの場合は転送
    header("Location: ../home/");
    exit();
}

//ページ情報設定
$page->set_info([
    "TITLE" =>  $LANG_DATA["pageinfo"]["title"]
]);

//////////////////////////////////////////////////
//GETデータで判別
if(isset($_GET["mode"])){
    $PHP_DATA["mode"] = $_GET["mode"];
}

//////////////////////////////////////////////////
//POSTデータ受け取り
if(isset($_POST['account_id']) && isset($_POST['pass']) && isset($_POST['form_token'])){
    //送信元確認のやつ照合
    if($_SESSION['form_token'] == $_POST['form_token']){
        unset($_SESSION['form_token']);

        $DB = new database();
        if($DB->connect()){
            //パスワードはハッシュ化したもので照合
            $account_name = $_POST['account_id'];
            $pass = hash("sha512", $_POST['pass']);
            $auto_login = (int)(isset($_POST['auto_login']) && $_POST['auto_login'] == "on");

            //れっつら照合
            $sql = "SELECT `uuid` FROM `account` WHERE `name`=? AND `password`=?";
            $stmt = $DB->getPDO()->prepare($sql);
            $stmt->execute([$account_name, $pass]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if($result !== false){
                $uuid = $result["uuid"];

                $sql = "INSERT INTO `login_session` (`uuid`, `start_date`, `session_token`, `cookie_token`, `auto_login`) VALUES (?, ?, ?, ?, ?)";
                $stmt = $DB->getPDO()->prepare($sql);
                do{
                    $now = date("Y-m-d", time());
                    $token = [rand_text(), rand_text()];
                    //セッションをDBに登録
                }while(!$stmt->execute([$uuid, $now, $token[0], $token[1], $auto_login]));

                //セッションとクッキーにそれぞれトークンを保存
                $token_limit = ($auto_login) ? time()+60*60*24*30 : 0;
                $_SESSION['_token'] = $token[0];
                setcookie("_token", $token[1], $token_limit, "/", "", false, true);

                //転送
                header("Location: ../home/");
            }
            else{
                $PHP_DATA["login_error"][] = "WRONG_ID_OR_PASSWORD";
            }

            //接続解除
            $DB->disconnect();
        }
        else{
            $PHP_DATA["login_error"][] = "UNKNOWN_ERROR_OCCURED";
        }
    }
}

//送信元確認用のトークン生成
$form_token = rand_text();
$_SESSION['form_token'] = $form_token;

//headタグの内容
$HEAD_CSS = $page->add_css(["/style/main.css", "/style/login.css"]);
$HEAD_JS = $page->add_js(["/js/main.js", "/js/login.js"]);
$PHP_DATA = $page->put_data($PHP_DATA, true);

?>

<!DOCTYPE html>
<html lang="ja" id="_login">
<?php $page->gen_page("head", $HEAD_CSS . $PHP_DATA); ?>
<body>
    <main>
        <div id="container">
            <div class="title">
                <h2><?=$LANG_DATA["main"]["login_title"]?></h2>
            </div>
            <div id="errmsg"></div>
            <div>
                <form action="" method="POST" onsubmit="return false;">
                    <label>
                        <span><?=$LANG_DATA["main"]["account_name"]?></span>
                        <input type="text" name="account_id" value="<?=(isset($_POST['account_id']))?"{$_POST['account_id']}":"";?>" required/>
                    </label>
                    <label>
                        <span><?=$LANG_DATA["main"]["password"]?></span>
                        <input type="password" name="pass" autocomplete="password" required />
                    </label>
                    <label>
                        <input type="checkbox" name="auto_login"/>
                        <span><?=$LANG_DATA["main"]["auto_login"]?></span>
                    </label>
                    <input type="submit" value="<?=$LANG_DATA["main"]["login"]?>" />
                    <!--トークン-->
                    <input type="hidden" name="form_token" value="<?=$form_token?>" />
                </form>
            </div>
            <div class="others">
                <p><?=$LANG_DATA["main"]["dont_have_account"]?><a class="-weight-500" href="../signup/"><?=$LANG_DATA["main"]["signup_here"]?></a></p>
            </div>
        </div>
    </main>
    <?=$HEAD_JS?>
</body>
</html>