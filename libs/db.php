<?php

require_once __DIR__."/main.php";

/**
 * データベースの情報を取得
 * @return array<string>
 */
function getDBData(){
    return parse_ini_file(dirname(__DIR__). "/libs/PDO_data.ini");
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
     */
    public function connect(){
        if(is_string($this->dsn) && is_array($this->loginInfo) && !$this->is_connected()){
            try{
                $this->PDO_obj = new PDO($this->dsn, $this->loginInfo["username"], $this->loginInfo["password"]);
                //エラー時にExceptionを投げるように設定
                $this->PDO_obj->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            catch(PDOException $e){
                //接続失敗
                //続行不能のエラー
                exitWithErrorPage(FATAL_ERRORS::DB_CONNECTION_REFUSED);
            }

            return true;
        }
        else{
            //設定が不十分 or 接続中
            return $this->is_connected();
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
     * same as `PDO->prepare`
     * @param string $sql sql statement
     * @return \PDOStatement|false
     */
    public function prepare(string $stmt){
        if($this->is_connected()){
            return $this->PDO_obj->prepare($stmt);
        }
        else{
            return false;
        }
    }

    public function query(string $query, int $fetchMode = PDO::FETCH_ASSOC){
        $result = $this->PDO_obj->query($query);

        return $result->fetchAll($fetchMode);
    }
}

?>