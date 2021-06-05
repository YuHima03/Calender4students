<?php

include_once "../libs/page.php";

$page = new page();
$account = $page->getAccountObj();
$lang = $page->getLangObj();

$page->setPageInfo([
    "title" =>  "ホーム",
    "js"    =>  [
        "../js/main.js",
        ["../js/auth.js", page::JS_INBODY],
        ["../js/calendar.js", page::JS_INBODY]
    ],
    "css"   =>  [
        "../css/main.css"
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
            <h1>HOME</h1>
            <div id="accountinfo_wrap">
                <div id="accountinfo">
                    <div class="section"><span>ようこそ&nbsp;</span><span><?=$account->getUserName()?></span><span>&nbsp;さん</span><div>
                    <div class="section"><span>UUID&nbsp;</span><span><?=$account->getUUID()?></span></div>
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