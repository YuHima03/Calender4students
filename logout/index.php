<?php

include_once "../libs/page.php";
include_once "../libs/account.php";

$page = new page();

$page->getAccountObj()->logout(true);

header("Location: ../");

?>