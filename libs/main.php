<?php

/**
 * いろんなページへの相対パスの取得
 */
class URI{
    /**
     * 相対パス
     */
    static public function RELATIVE_PATH() : string{
        preg_match_all("/\//", $_SERVER["SCRIPT_NAME"], $result, PREG_SET_ORDER);
        return "./".str_repeat("../", sizeof($result) - 1);
    }

    static public function LOGIN_PAGE(bool $setRelPATH = true, $get = null) : string{
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
}

?>