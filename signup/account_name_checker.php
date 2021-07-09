<?php

include_once "../libs/main.php";
include_once "../libs/db.php";

$DB = new db();
$result = [];

if(isset($_POST['name'])){
    $name = $_POST['name'];
    if($DB->connect()){
        $sql = "SELECT `name` FROM `account` WHERE `name`=?";
        $stmt = $DB->prepare($sql);
        $stmt->execute([$name]);
        
        $result[] = ($stmt->rowCount() == 0) ? true : false;

        $DB->disconnect();
    }
}
else{
    $result[] = false;
}

echo json_encode($result);

?>