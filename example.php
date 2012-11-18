<?php

require_once("netbank.inc.php");
require_once("config.inc.php");

$nb = new Netbank(); 

$nb->login($clientno, $password);

$accounts = $nb->getAccounts();

print_r($accounts);

?>
