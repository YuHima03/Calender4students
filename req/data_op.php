<?php

include_once "../libs/main.php";
include_once "../libs/db.php";
include_once "../libs/account.php";
include_once "../libs/calendar_op.php";

$result = [
    "result" => false
];

/** @var array */
$jsonData = json_decode(file_get_contents("php://input"), true);

try{
    if(sizeof($jsonData) > 0){
        if(isset($jsonData["type"])){
            $account = new account();

            if($account->getLoginStatus()){
                $cal = new calendar_op($account);

                /** @var string */
                $optype = $jsonData["type"];

                switch($optype){
                    case"get":
                        //情報取得
                        if(isset($jsonData["year"])){
                            $year = $jsonData["year"];
                            $month = $jsonData["month"] ?? null;
                            $date = $jsonData["date"] ?? null;

                            $result["data"] = $cal->get_schedules($year, $month, $date);
                        }
                        else throw new Error("`year` property not found");

                        break;
                    default:
                        throw new Error("Unknown type -> `{$jsonData['type']}`");
                }
            }
            else throw new Error("Auth failed");
        }
        else throw new Error("`type` property not found");
    }
    else throw new Error("Nothing posted");
}
catch(Exception|Error $e){
    $result["errMsg"] = $e->getMessage();
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

?>