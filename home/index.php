<?php

include_once "../libs/page.php";

$page = new page();
$lang = $page->getLangObj();

$page->setPageInfo([
    "title" =>  "ホーム",
    "js"    =>  [
        "../js/main.js",
        ["../js/calender.js", page::JS_INBODY]
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
    <h1>HOME</h1>
    <?=$page->genPage(page::JS_INBODY)?>
</body>
</html>