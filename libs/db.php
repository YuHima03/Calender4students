<?php

include_once "main.php";

/**
 * データベースの情報を取得
 * @return array<string>
 */
function getDBData(){
    return parse_ini_file(URI::RELATIVE_PATH() . "libs/PDO_data.ini");
}

class DB{
    /** @var \PDO */
    private $PDO_obj = null;

    /**
     * @param string $dbName データベース名
     */
    public function __construct($dbName = "C4S"){
        $dbData = getDBData();

        $dsn = "mysql:dbname={$dbName},host=localhost";

        $this->PDO_obj = new PDO($dsn, $dbData["username"], $dbData["password"]);
    }

    /**
     * @param string $stmt statement
     */
    public function prepare($stmt){
        return $this->PDO_obj->prepare($stmt);
    }
}

?>