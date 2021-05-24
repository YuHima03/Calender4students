<?php
/**
 * カレンダーの内部データ等を扱う管理ページ
 * (Administratorのみ閲覧可能)
 */

include_once "../libs/main.php";
include_once "../libs/account.php";
include_once "../libs/page.php";

$account = new account();
$page = new page($account);

if($account->isAdmin()):
    //管理ページを表示
    $page->setPageInfo([
        "title" =>  "AdministerPage"
    ]);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <?=$page->genPage(page::HEAD_C)?>
</head>
<body>
    <div id="wrap">
        <main></main>
    </div>
</body>
</html>

<?php 
else:
    //閲覧権限無し(->401ページを表示)
endif;
?>