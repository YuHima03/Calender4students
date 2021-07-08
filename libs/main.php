<?php

/**
 * 続行不能レベルの深刻なエラー
 */
class FATAL_ERRORS{
    public const UNKNOWN = 0;
    /**ファイル読み込み失敗 */
    public const FILE_LOADING_FAILED = 10;
    /**ファイル操作失敗 */
    public const FILE_OPERATION_FAILED = 11;
    /**DB接続失敗 */
    public const DB_CONNECTION_REFUSED = 20;
    /**DB操作失敗 */
    public const DB_OPERATION_FAILED = 21;

    public static $errCodeList = [
        0   =>  "UNKNOWN",
        10  =>  "FILE_LOADING_FAILED",
        11  =>  "FILE_OPERATION_FAILED",
        20  =>  "DB_CONNECTION_REFUSED",
        21  =>  "DB_OPERATION_FAILED"
    ];
}

/**
 * エラー画面を表示して終了
 * @param int $errorNum `FATAL_ERRORS`の定数、もしくは`HTTPステータス`(404...等)
 */
function exitWithErrorPage(int $errorNum, bool $errorLog = true){
    if($errorLog){
        //ログ保存
    }

    exit(include URI::ABSOLUTE_PATH()."/error/fatalerrors.php");
}

/**
 * いろんなページへの相対パスの取得
 */
class URI{
    public const HOME_PAGE = "home/";
    public const LOGIN_PAGE = "login/";

    public static function get_Level() :int{
        $path = substr($_SERVER["SCRIPT_FILENAME"], strlen(preg_replace("/\\\\/", "/", URI::ABSOLUTE_PATH())));
        preg_match_all("/\//", $path, $result, PREG_SET_ORDER);

        return sizeof($result) - 1;
    }

    /**
     * 最上位フォルダへの相対パス
     */
    public static function RELATIVE_PATH() :string{
        return substr("./".str_repeat("../", URI::get_Level()), 0, -1);
    }

    /**
     * 最上位フォルダへの絶対パス
     */
    public static function ABSOLUTE_PATH() :string{
        return dirname(__DIR__);
    }

    /**
     * GETパラメータをリンクの末尾に設定する
     */
    public static function add_GET_Param(string $linkText, array $dataList) :string{
        if(gettype($dataList) === "array"){
            foreach($dataList as $key => $value){
                if(preg_match("/((\&|\?)\w+\=\w+)$/", $linkText)){
                    $linkText .= "&";
                }
                else{
                    $linkText .= "?";
                }

                $linkText .= "{$key}={$value}";
            }

            return $linkText;
        }
        else{
            throw new TypeError("`dataList` argument must be object or array!");
        }
    }

    /**
     * 重大なエラー発生時(続行不能の場合の移動先)
     */
    public static function FATAL_ERROR_PAGE(int $errorType = FATAL_ERRORS::UNKNOWN) :string{
        $to = substr($_SERVER["SCRIPT_FILENAME"], strlen(preg_replace("/\\\\/", "/", URI::ABSOLUTE_PATH())));
        $result = URI::add_GET_Param(URI::RELATIVE_PATH()."/error/", ["errcode"=>$errorType, "to"=>$to]);

        return $result;
    }

    public static function get_PATH(string $rootPATH){
        return self::RELATIVE_PATH() . "/" . $rootPATH;
    }

    /**
     * 指定のURLに移動(`exit`で終了する)
     */
    public static function moveto(string $url){
        header("Location:".$url);
        exit;
    }
}

/**
 * ランダムな文字列を返す(`$len`の長さの文字列を`$mode`でハッシュ化する)
 * @param int $len 長さ
 * @param string $mode ハッシュ化の種類(`null`でハッシュ化なし)
 * @param bool $hex ランダムな16進数を利用する
 */
function getRandStr(int $len = 128, string $mode = null, bool $hex = false) :string {
    $result = "";

    if($hex){
        $result = bin2hex(openssl_random_pseudo_bytes($len/2));
    }
    else{
        $str = "abcdefghijklmnopqrstuvwxyz0123456789";

        for($i = 0; $i < $len; $i++){
            $result .= $str[rand(0, strlen($str)-1)];
        }
    }

    return (is_null($mode)) ? $result : hash($mode, $result);
}

/**
 * UUIDを取得する
 * @param bool $bin バイナリを取得する
 * @return string
 */
function getUUID(bool $bin = true) :string{
    //128バイトの乱数生成 (バージョン等指定するために論理積を取る)
    //RRRRRRRR-RRRR-4RRR-rRRR-RRRRRRRRRRRR -> (hex)FFFFFFFF-FFFF-4FFF-rFFF-FFFFFFFFFFFF (r=8,9,A,B)
    $binText_OR  = hex2bin('00000000'.'0000'.'4000'.'8000'.'000000000000');
    $binText_AND = hex2bin('FFFFFFFF'.'FFFF'.'4FFF'.'BFFF'.'FFFFFFFFFFFF');
    $uuidStr     = (random_bytes(16) | $binText_OR) & $binText_AND;

    if($bin){
        return $uuidStr;
    }
    else{
        $uuidStr = bin2hex($uuidStr);
        return substr($uuidStr, 0, 8) . '-' . substr($uuidStr, 8, 4) . '-' . substr($uuidStr, 12, 4) . '-' . substr($uuidStr, 16, 4) . '-' . substr($uuidStr, 20);
    }
}

/**
 * フォルダを削除
 */
function rmdir_all(string $path) :bool{
    if(is_dir($path)){
        if($dirhandle = opendir($path)){
            while(false !== ($entry = readdir($dirhandle))){
                if($entry !== "." && $entry !== ".."){
                    $entryDirName = "{$path}/{$entry}";

                    if(is_dir($entryDirName)){
                        //再帰的に
                        rmdir_all($entryDirName);
                    }
                    else{
                        unlink($entryDirName);
                    }
                }
            }

            closedir($dirhandle);
            rmdir($path);

            return true;
        }
    }

    return false;
}

function isset_check($value, $default = null){
    return isset($value) ? $value : $default;
}

/**
 * page-token
 */
class token_auth{
    private string $token_name = "a_token";
    private ?string $token = null;

    private function update_token_info(){
        $this->token = isset($_SESSION[$this->token_name]) ? $_SESSION[$this->token_name] : null;
        return;
    }

    public function __construct(?string $token_name = null){
        if(is_string($token_name)){
            $this->token_name = $token_name;
        }

        $this->update_token_info();
    }

    public function set_token(){
        $token = getRandStr(32);

        $this->token = $token;
        $_SESSION[$this->token_name] = $token;
        return;
    }

    public function get_token() :?string{
        return $this->token;
    }

    /**
     * @param $unset 認証成功時に自動削除
     * @param $regen 認証失敗時に再生成
     */
    public function auth(string $token, bool $unset = true, bool $regen = true) :bool{
        $this->update_token_info();
        $result = ($token === $this->token);

        if($result && $unset) $this->unset_token();
        else if(!$result && $regen) $this->set_token();

        return $result;
    }

    public function unset_token(){
        $this->token = null;
        unset($_SESSION[$this->token_name]);

        return;
    }
}
