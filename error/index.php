<?php
/**
 * やばいエラーが発生した時の処理
 * (他ののPHPファイルやJSファイルに依存しない完全独立ページ)
 */

$errCode = (isset($_GET["errcode"])) ? (int)$_GET["errcode"] : 0;
$locateTo = (isset($_GET["to"])) ? $_GET["to"] : "../";

$errorCodeList = [
    "UNKNOWN",
    "FILE_LOADING_FAILED",
    "DB_CONNECTION_FAILED"
];

$errorText = ($errCode <= sizeof($errorCodeList)) ? $errorCodeList[$errCode] : $errorCodeList[0];

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>エラーが発生しました</title>
</head>
<body>
    <main>
        <div id="msg">
            <div>
                <h3>エラーが発生しました</h3>
                <div class="detail">
                    <p class="err_code">エラーコード：<?=$errorText?></p>
                    <p>処理中に重大なエラーが発生しました。</p>
                    <p>回線の混雑、または障害の可能性があります。</p>
                    <p>時間を空けて下の「続行」のボタンを押してもう一度お試しください。</p>
                    <button onclick="location.href = '..<?=$locateTo?>'">続行<span style="margin-left: 0.5em;font-size: 12px;">(目的のページへ移動)</span></button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>