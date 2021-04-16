<?php

require_once __DIR__."/main.php";

/**
 * データベースの情報を取得
 * @return array<string>
 */
function getDBData(){
    return parse_ini_file(URI::RELATIVE_PATH() . "/libs/PDO_data.ini");
}

class DB{
    /** @var \PDO */
    private $PDO_obj = null;

    private $dsn = null;
    private $loginInfo = null;

    /**
     * @param string $dbName データベース名
     */
    public function __construct($dbName = "C4S"){
        $this->loginInfo = getDBData();

        $this->dsn = "mysql:dbname={$dbName};host=localhost";
    }

    /**
     * 接続する
     * @param bool $auto_error_redirect エラー発生時の自動リダイレクトの有無
     */
    public function connect($auto_error_redirect = true){
        if(is_string($this->dsn) && is_array($this->loginInfo) && !$this->is_connected()){
            try{
                $this->PDO_obj = new PDO($this->dsn, $this->loginInfo["username"], $this->loginInfo["password"]);
            }
            catch(PDOException $e){
                //接続失敗
                //続行不能のエラー
                URI::moveto(URI::FATAL_ERROR_PAGE(FATAL_ERRORS::DB_CONNECTION_FAILED));
            }

            return true;
        }
        else{
            //設定が不十分 or 接続中
            return false;
        }
    }

    /**
     * 接続解除
     */
    public function disconnect(){
        if($this->is_connected()){
            $this->PDO_obj = null;
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * 現在接続されてるか否か
     */
    public function is_connected(){
        return !is_null($this->PDO_obj);
    }

    /**
     * @param string $stmt statement
     */
    public function prepare($stmt){
        return $this->PDO_obj->prepare($stmt);
    }
}

?>