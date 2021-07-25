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

    private ?\account $account = null;

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
                    throw new Error("Invalid `fileName` value. It must be empty string");
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
    private function getJSON(int $fileType, string $fileName){
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

    private function updateJSON(int $fileType, string $fileName = "", array $data = []) :bool{
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

//上のやつは使わない(開発参考用に残すけどあとで消す)

/**
 * カレンダー情報の操作
 */
class calendar_op{
    private ?\account $account = null;
    private ?string $path = null;

    /**
     * @param null|account $accountObj `null`の場合は新規作成される
     */
    public function __construct(?\account $accountObj = null) {
        $this->account = $accountObj ?? new account();

        //フォルダへのパス
        $this->path = URI::get_PATH("data/{$this->account->getUUID(true)}/");

        //フォルダが存在しない場合は新規作成
        if(!is_dir($this->path)) $this->init();
    }

    /**
     * 暗号化する
     * @return string 生のバイナリデータを返す
     */
    static private function file_encryption(string $plain, string $key, string $ivector) :string {
        return base64_decode(openssl_encrypt($plain, "AES-256-CBC", $key, 0, $ivector));
    }

    /** 
     * 復号する
     * @param string $cipher 生のバイナリデータ
     */
    static private function file_decryption(string $cipher, string $key, string $ivector) :string {
        return openssl_decrypt(base64_encode($cipher), "AES-256-CBC", $key, 0, $ivector);
    }

    /** 
     * 初期ベクトルを取得
     * @return string バイナリデータ
     */
    private function get_ivector() :string {
        $iv_file_path = $this->path . "iv.dat";

        if(file_exists($iv_file_path)){
            $file = new SplFileObject($iv_file_path, "rb");
            $file->flock(LOCK_SH);

            //$iv_hex = unpack("H*", $file->fread($file->getSize()));
            $iv_bin = $file->fread($file->getSize());

            $file->flock(LOCK_UN);
            unset($file);
        }
        else{
            //新規作成
            $iv_bin = openssl_random_pseudo_bytes(16);
            $this->save_ivector($iv_bin);
        }

        return $iv_bin;
    }

    private function save_ivector(string $ivector) :bool {
        $iv_file_path = $this->path . "iv";

        $file = new SplFileObject($iv_file_path . "_new.dat", "wb");
        $file->flock(LOCK_EX);

        $file->ftruncate(0);
        $file->fwrite($ivector);

        $file->flock(LOCK_UN);
        unset($file);

        return rename($iv_file_path . "_new.dat", $iv_file_path . ".dat");
    }

    /**
     * 必須ファイルの新規作成
     */
    private function init() :bool {
        $dir_path = $this->path;

        return mkdir($dir_path, 0764);
    }

    /**
     * @param string $file_name 拡張子は無し
     */
    private function get_decrypted_data(string $file_name, int $lock_mode = LOCK_SH) :string {
        //必要な情報を取得
        $iv = $this->get_ivector();
        $key = $this->account->getEncryptKey();

        $file = new SplFileObject($file_name . ".dat");
        $file->flock($lock_mode);

        //バイナリデータ(暗号化されてる)
        $cipher = $file->fread($file->getSize());

        $file->flock(LOCK_UN);
        unset($file);

        return $this->file_decryption($cipher, $key, $iv);
    }

    /**
     * @param string $file_name 拡張子は無し
     */
    private function save_encrypted_data(string $file_name, string $data, int $lock_mode = LOCK_EX) :bool {
        //必要な情報を取得
        $iv = $this->get_ivector();
        $key = $this->account->getEncryptKey();

        //ファイル破損防止の為に最初は名前に_newをつける
        $file = new SplFileObject($file_name . "_new.dat", "wb");
        $file->flock($lock_mode);

        //一応丸める
        $file->ftruncate(0);

        $cipher = $this->file_encryption($data, $key, $iv);
        $file->fwrite($cipher);

        $file->flock(LOCK_UN);
        unset($file);

        $result = true;

        //置き換え前のやつを_oldに改名
        $result &= rename($file_name . ".dat", $file_name . "_old.dat");

        //_newを無印に改名
        $result &= $result && rename($file_name . "_new.dat", $file_name . ".dat");

        return $result;
    }

    /**
     * ファイルは年月で分けてるので日のデータはこの関数の戻り値から取得できる
     * @return array
     */
    private function get_schedules_filedata(?int $year = null, ?int $month = null) {
        $result = [];

        if(is_null($year)){
            //データフォルダの中の全ファイル
            if(is_resource($dir = opendir($this->path))){
                while(false !== ($entry = readdir($dir))){
                    if(preg_match("/^sch_(\d{4})_(\d{2})\.dat$/", $entry, $matches) && sizeof($matches) === 3){
                        $result[] = $this->get_schedules_filedata((int)$matches[1], (int)$matches[2]);
                    }
                }

                closedir($dir);
            }
            else throw new Error("Failed to open directory");
        }
        else if(is_null($month)){
            //1~12月まで回す
            for($i = 1; $i <= 12; $i++){
                $result[] = $this->get_schedules_filedata($year, $i);
            }
        }
        else if(between($month, 0, 12, true)){
            $yearStr = (string)$year;
            $monthStr = num2str($month, 2);

            $schlist_path = $this->path . "sch_{$yearStr}_{$monthStr}";

            if(file_exists($schlist_path)){
                $result = json_decode($this->get_decrypted_data($schlist_path));
            }
        }

        return $result;
    }

    /**
     * @param (null|int)[][] $stmt
     */
    public function get_schedules(array $stmt) :array {
        $result = [];

        foreach($stmt as $info){
            //yearプロパティは必須(全取得は`get_all_schedules`で)
            if(isset($info["year"])){
                $year = (int)$info["year"];
                $month = isset($info["month"]) ? (int)$info["month"] : null;
                $date = isset($info["date"]) ? (int)$info["date"] : null;

                $file_data = $this->get_schedules_filedata($year, $month);

                if(!is_null($date) && between($date, 1, 12, true)){
                    //日付指定あり
                }
                else{
                    //なし -> 全部
                }

                $result
            }
            else throw new Error("`year` property not found");
        }

        return $result;
    }

    public function get_all_schedules(){

    }
}

?>