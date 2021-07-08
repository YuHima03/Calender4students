<?php

include_once "../libs/page.php";

$page = new page();
$account = $page->getAccountObj();
$lang = $page->getLangObj();

if(!$account->getLoginStatus()){
    //ログイン画面へ
    URI::moveto(URI::get_PATH(URI::LOGIN_PAGE));
}

$page->setPageInfo([
    "title" =>  "ホーム",
    "js"    =>  [
        "../js/main.js",
        "../js/elemop.js",
        ["../js/auth.js", page::JS_INBODY],
        ["../js/calendar.js", page::JS_INBODY]
    ],
    "css"   =>  [
        "../style/main.css",
        "../style/home.css"
    ]
]);

?>

<!DOCTYPE html>
<html <?=$lang->getLangAttr()?>>
<head <?=page::OGP_PREFIX?>>
    <?=$page->genPage(page::HEAD_C)?>
</head>
<body>
    <!--main_contents-->
    <div id="wrap">
        <header id="header_wrap">
            <div id="title_wrap">
                <h1>C4E</h1>
            </div>
            <!--アカウントのメニュー-->
            <div id="account_menu" class="menu_wrap">
                <div class="menu_button_wrap">
                    <!--アイコンをボタンにする-->
                    <button id="account_menu_button" type="button"><img src="../data/TEMPLATE/icon.png" alt="MENU" /></button>
                </div>
                <div class="menu_container">
                    <div><span>UserName：&nbsp;</span><span><?=$account->getUserName()?></span></div>
                    <div><span>UUID：&nbsp;</span><span><?=$account->getUUID()?></span></div>
                    <div><button type="button" id="account_menu_logout_button"><?=$lang->getWord("gui.logout")?></button></div>
                </div>
            </div>
        </header>
        <main id="main_wrap">
            <!--calendar_main-->
            <div id="calendar_wrap">
                <div id="calendar">
                    <div id="calendar_table_wrap"></div>
                </div>
            </div>
        </main>
    </div>
    <!--JS-->
    <?=$page->genPage(page::JS_INBODY)?>
</body>
</html>