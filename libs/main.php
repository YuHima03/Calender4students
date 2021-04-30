<?php

/**
 * 続行不能レベルの深刻なエラー
 */
class FATAL_ERRORS{
    public const UNKNOWN = 0;
    public const FILE_LOADING_FAILED = 1;
    public const DB_CONNECTION_FAILED = 2;
}

/**
 * いろんなページへの相対パスの取得
 */
class URI{
    /**
     * ROOTフォルダへの相対パス
     */
    public static function RELATIVE_PATH() : string{
        preg_match_all("/\//", $_SERVER["SCRIPT_NAME"], $result, PREG_SET_ORDER);
        return substr("./".str_repeat("../", sizeof($result) - 1), 0, -1);
    }

    /**
     * GETパラメータをリンクの末尾に設定する
     */
    public static function add_GET_Param(string $linkText, array $dataList) : string{
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

    public static function LOGIN_PAGE(bool $setRelPATH = true, $get = null) : string{
        $result = (($setRelPATH) ? URI::RELATIVE_PATH() : "") . "login/";
        
        if(is_array($get)){
            $result .= "?";
            $fstFlag = false;
            foreach($get as $key => $value){
                if(!$fstFlag){
                    $fstFlag = true;
                }
                else{
                    $result .= "&";
                }
                $result .= "{$key}={$value}";
            }
        }

        return $result;
    }

    /**
     * 自身(=呼び出したファイル) へのrootファイルからの相対アドレス
     */
    public static function SELF_PAGE() :string{
        return $_SERVER["PHP_SELF"];
    }

    /**
     * 重大なエラー発生時(続行不能の場合の移動先)
     */
    public static function FATAL_ERROR_PAGE(int $errorType = FATAL_ERRORS::UNKNOWN) :string{
        $result = URI::add_GET_Param(URI::RELATIVE_PATH()."/error/", ["errcode"=>$errorType, "to"=>URI::SELF_PAGE()]);

        return $result;
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
 * @return string
 */
function getUUID(){
    //128バイトの乱数生成 (バージョン等指定するために論理積を取る)
    //RRRRRRRR-RRRR-4RRR-rRRR-RRRRRRRRRRRR -> (hex)FFFFFFFF-FFFF-4FFF-rFFF-FFFFFFFFFFFF (r=8,9,A,B)
    $binText_OR  = hex2bin('00000000'.'0000'.'4000'.'8000'.'000000000000');
    $binText_AND = hex2bin('FFFFFFFF'.'FFFF'.'4FFF'.'BFFF'.'FFFFFFFFFFFF');
    $uuidStr     = bin2hex((random_bytes(16) | $binText_OR) & $binText_AND);

    return substr($uuidStr, 0, 8) . '-' . substr($uuidStr, 8, 4) . '-' . substr($uuidStr, 12, 4) . '-' . substr($uuidStr, 16, 4) . '-' . substr($uuidStr, 20);
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

?>