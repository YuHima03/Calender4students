<?php

include_once "../libs/main.php";
include_once "../libs/account.php";

$account = new account();

$arr = [
    [
        "calenderId"    =>  "114514",
        "from"          =>  "2021-05-02 09:56",
        "to"            =>  "2021-05-02 19:56",
        "name"          =>  "テストの10時間",
        "detail"        =>  "暗号化のテスト用のやつ",
        "color"         =>  "1dacf2"
    ]
];

$binFile = fopen("encrypted_bin", "c+b");
$ivFile = fopen("iv_bin", "c+b");

//iv取得
$iv = unpack("H*", fread($ivFile, filesize("iv_bin")));

//読み取り
$data = fread($binFile, filesize("encrypted_bin"));
$decryped_str = unpack("C*", $data);
$read_data = "";
foreach($decryped_str as $bin){
    $read_data .= chr($bin);
}

echo "<pre>", var_dump(json_decode(openssl_decrypt(base64_encode($read_data), "AES-256-CBC", $account->getEncryptKey(), 0, hex2bin($iv[1])), true)), "</pre>";

//内容削除
ftruncate($binFile, 0);
rewind($binFile);

//書き込み
$iv = openssl_random_pseudo_bytes(16);
$encrypted_str = base64_decode(openssl_encrypt(json_encode($arr), "AES-256-CBC", $account->getEncryptKey(), 0, $iv));
$write_data = [];
for($i = 0; $i < strlen($encrypted_str); $i++){
    $write_data[] = ord($encrypted_str[$i]);
}
fwrite($binFile, pack("C*", ...$write_data));

fclose($binFile);

//iv記録
ftruncate($ivFile, 0);
rewind($ivFile);
fwrite($ivFile, pack("H*", bin2hex($iv)));
fclose($ivFile);

echo "<br>", base64_encode($encrypted_str);

?>