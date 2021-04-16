<?php

//////////////////////////////////////////////////
//いろいろ設定
//include_once "./libs/C4S_main.php";

include_once "./libs/main.php";
include_once "./libs/db.php";
//include_once "./libs/account.php";

$account = new DB();
$account->connect(false);
/*
$page = new page(false);

if($page->get_account_info()["login"]){
    //ログイン済みの場合は転送
    header("Location: /home/");
    exit();
}

$page->set_info([
    "TITLE" =>  "ようこそ"
]);

$page->set_gen_option([
    
]);

//送信元確認用のトークン生成
$form_token = rand_text();
$_SESSION['form_token'] = $form_token;*/

//////////////////////////////////////////////////
//出力
?>

<!DOCTYPE html>
<html lang="ja">
<!--head-->
<?php /*$page->gen_page("head", 
    $page->add_css("/style/top.css")
    . $page->add_js(["/js/main.js", "/js/top.js"])
    . $page->put_data([
        "form_token" => $form_token
    ])
); */?>
<!--body-->
<body>
    <!--header-->
    <?php //$page->gen_page("body/header"); ?>
    <!--main_contents-->
    <main>
        <div id="container">
            <div id="title">
                <div>
                    <div class="main_title">
                        <h1>学生の為の</h1>
                        <h1>カレンダー</h1>
                    </div>
                    <h3 class="sub_title">面倒な学習の管理をこれひとつで。</h3>
                </div>
            </div>
            <div id="mode_selecter">
                <div>
                    <h3>さぁ、始めましょう！</h3>
                </div>
                <div class="selecter">
                    <input type="button" name="no_signup" value="アカウント無しで利用" />
                    <input type="button" name="signup" value="アカウントを作成" data-goto="signup"/>
                    <span>上の二つの違いは何ですか？</span>
                    <span>アカウントをお持ちですか？</span>
                    <input type="button" name="login" value="ログインする" data-goto="login"/>
                </div>
            </div>
        </div>
    </main>
    <!--footer-->
    <?php //$page->gen_page("body/footer"); ?>
</body>
</html>