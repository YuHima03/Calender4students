<?php

include_once __DIR__."/main.php";

/**
 * カレンダーの操作
 */
class calenderOp{
    public static array $defaultArrayData = [];

    private \account $account = null;

    private ?string $dirpath = null;

    public function __construct(?\account &$accountObj){
        if(is_null($accountObj)){
            $accountObj = new account();
        }

        $this->account = $accountObj;

        $this->dirpath = dirname(__DIR__)."/data/".$this->account->getUUID();
    }

    /**
     * @return array|false
     */
    public function getJSON(){
        if($this->account->getLoginStatus() && is_string($this->dirpath) && is_dir($this->dirpath)){
            //ファイルのパス生成
            $binFilePath = "{$this->dirpath}/schedule.dat";
            $ivFilePath = dirname($binFilePath)."/iv.dat";

            if(!is_file($binFilePath) || !is_file($ivFilePath)){
                //初のファイル作成
                $this->updateJSON();
            }

            //ファイルを開く
            $binFile = fopen($binFilePath, "r+b");
            $ivFile = fopen($ivFilePath, "r+b");

            //共有ロック
            flock($binFile, LOCK_SH);

            //初期化ベクトルの読み込み
            $viData = unpack("H*", fread($ivFile, filesize($ivFilePath)));
            $viHex = $viData[1];

            //jsonデータの読み込み
            $binData = unpack("C*", fread($binFile, filesize($binFilePath)));
            $binStr = "";
            foreach($binData as $bin){
                $binStr .= chr($bin);
            }

            $decrypedStr = openssl_decrypt(base64_encode($binStr), "AES-256-CBC", $this->account->getEncryptKey(), 0, hex2bin($viHex));

            //ロック解除&ファイル閉じ
            flock($binFile, LOCK_UN);
            fclose($binFile);

            return json_decode($decrypedStr, true);
        }
        else{
            return false;
        }
    }

    public function updateJSON(array $data = calenderOp::$defaultArrayData) :bool{
        $returnResult = false;
        $timestamp = time();

        //ファイル破損を防ぐために別に作ってから後でコピーする方式を利用
        $binFilePath = "{$this->dirpath}/schedule_{$timestamp}.dat";
        $ivFilePath = dirname($binFilePath)."/iv_{$timestamp}.dat";
        $ivFile = fopen($ivFilePath, "r+b");
        $binFile = fopen($binFilePath, "r+b");

        if(is_resource($ivFile) && is_resource($binFilePath)){
            //占有ロック
            flock($binFile, LOCK_EX);
            flock($ivFile, LOCK_EX);

            //初期ベクトルの生成&保存
            $ivBin = openssl_random_pseudo_bytes(16);
            fwrite($ivFile, pack("H*", bin2hex($ivBin)));

            //jsonデータの暗号化&書き込み
            $encrypted_str = base64_decode(openssl_encrypt(json_encode($data, JSON_UNESCAPED_UNICODE), "AES-256-CBC", $this->account->getEncryptKey(), 0, $ivBin));
            $writeData = [];
            for($i = 0; $i < strlen($encrypted_str); $i++){
                $writeData[] = ord($encrypted_str[$i]);
            }
            fwrite($binFile, pack("C*", ...$writeData));

            $returnResult = true;
        }

        //ロック解除&ファイル閉じ
        flock($binFile, LOCK_UN);
        flock($ivFile, LOCK_UN);
        fclose($binFile);
        fclose($ivFile);

        //ファイルのコピペ
        return $returnResult && copy($binFilePath, dirname($binFilePath)."/schedule.dat") && copy($ivFilePath, dirname($ivFilePath)."/iv.dat");
    }
}

?>