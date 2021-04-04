<?php

include_once "../libs/C4S_main.php";

$page = new page(false);

if($page->get_account_info()["login"]){
    header("Location: ../home/");
    exit();
}
else{
    if(isset($_POST["_NAME"]) && isset($_POST["_PASS"])){
        if(isset($_POST["_TOKEN"]) && $_POST["_TOKEN"] === $_SESSION["form_token"]){
            unset($_SESSION["form_token"]);

            $page->set_info([
                "TITLE" =>  "処理中..."
            ]);

            $name = $_POST["_NAME"];
            $pass = $_POST["_PASS"];

            $DB = new database();
            if($DB->connect()){
                $accountCreation = new create_account();
                $accountCreation->create($name, $pass, false, true);

                $DB->disconnect();
            }
            else{
                exit("DATABASE_CONNECTION_ERROR");
            }

            header("Location: ../home/");
            exit;
        }
        else{
            //トークン認証失敗
        }
    }
    
    $page->set_info([
        "TITLE" =>  "アカウントの作成"
    ]);

    $form_token = rand_text();
    $_SESSION["form_token"] = $form_token;
}

?>

<!DOCTYPE html>
<html lang="ja">
<?php $page->gen_page("head", $page->add_css(["style/main.css", "style/login.css", "style/signup.css"]) . $page->add_js(["js/main.js", "js/signup.js"])); ?>
<body id="_signup">
    <main>
        <div>
            <h1>アカウントの作成</h1>
        </div>
        <div id="create_form">
            <form id="create_account" name="create_account" action="" method="POST" >
                <p>ID<span class="small">※4~32文字で、英数字(A~Z,a~z,0~9)と'_'(アンダーバー)が利用可能です</span></p>
                <input type="text" name="_NAME" value="" required />
                <p>パスワード<span class="small">※6~32文字で、英文字(A~Z,a~z)と数字(0~9)を共に含んでいる必要があります</span></p>
                <input type="password" name="_PASS" required />
                <p>パスワード(確認)<span class="small">※クリップボードからの貼り付けは出来ません</span></p>
                <input type="password" name="_PASS_CHK" onpaste="return false;" required />
                <input type="hidden" name="_TOKEN" value="<?=$form_token?>" />
                <input type="submit" value="作成" />
            </form>
        </div>
    </main>
</body>
</html>