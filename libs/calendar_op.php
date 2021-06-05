<?php

include_once __DIR__."/main.php";

/**
 * カレンダーの操作いろいろ
 */
class calendarOp{
    /**予定等 */
    public const SCHEDULE = 0;
    /**予定のIDリスト */
    public const ID_LIST = 1;
    /**タグのリスト */
    public const TAG_LIST = 2;

    private static array $fileTypeList = [
        "", "id_list"
    ];

    private \account $account = null;

    private ?string $dirpath = null;

    /**
     * @return string|false
     */
    private static function getFullFileName(int $fileType, string $fileName){
        //ファイル名書式チェック
        switch($fileType){
            case(self::SCHEDULE):
                if(!preg_match("/^\d{4}_(0[1-9]|1[0-2])$/", $fileName)){
                    throw new Error("Invalid `fileName` form");
                    return false;
                }
                break;

            case(self::ID_LIST):
            case(self::TAG_LIST):
                if(strlen($fileName) !== 0){
                    throw new Error("Invalid `fileName` value, which must be empty string");
                    return false;
                }
                break;

            default:
                throw new Error("Unknown `fileType` value");
                return false;
        }

        //ファイル名の確定
        if(is_string(self::$fileTypeList[$fileType])){
            $fileTypeStr = self::$fileTypeList[$fileType];

            if(strlen($fileTypeStr) > 0){
                $fileName .= "_".$fileTypeStr;
            }
        }
        else{
            throw new Error("Invalid `fileType` value");
            return false;
        }

        return $fileName;
    }

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
    public function getJSON(int $fileType, string $fileName){
        if($this->account->getLoginStatus() && is_string($this->dirpath) && is_dir($this->dirpath)){
            if(($fullFileName = self::getFullFileName($fileType, $fileName)) === false){
                return false;
            }

            //ファイルのパス生成
            $binFilePath = "{$this->dirpath}/{$fullFileName}.dat";
            $ivFilePath = dirname($binFilePath)."/{$fullFileName}_iv.dat";

            if(!is_file($binFilePath) || !is_file($ivFilePath)){
                //初のファイル作成
                if(!$this->updateJSON($fileType, $fileName)){
                    return false;
                }
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

    public function updateJSON(int $fileType, string $fileName = "", array $data = []) :bool{
        $returnResult = false;
        $timestamp = time();

        if(($fileName = self::getFullFileName($fileType, $fileName)) === false){
            return false;
        }

        //ファイル破損を防ぐために別に作ってから後でコピーする方式を利用
        $binFilePath = "{$this->dirpath}/{$fileName}_{$timestamp}.dat";
        $ivFilePath = dirname($binFilePath)."/{$fileName}_iv_{$timestamp}.dat";
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

        //ファイルのコピペで終了
        return $returnResult && copy($binFilePath, dirname($binFilePath)."/{$fileName}.dat") && copy($ivFilePath, dirname($ivFilePath)."/{$fileName}_iv.dat");
    }
}

?>