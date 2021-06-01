<?php
/**
 * やばいエラーが発生した時の処理
 * (他PHPファイルより読み込みあり)
 */

include_once dirname(__DIR__)."/libs/main.php";

$errorNum = (is_numeric($errorNum) && is_string(FATAL_ERRORS::$errCodeList[$errorNum])) ? $errorNum : FATAL_ERRORS::UNKNOWN;

$errorText = FATAL_ERRORS::$errCodeList[$errorNum];

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
                    <p class="err_code">エラーコード：<?=$errorNum."_".$errorText?></p>
                    <p>処理中に重大なエラーが発生しました。</p>
                    <p>回線の混雑、または障害の可能性があります。</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>