<?php

require_once("netbank.inc.php");
require_once("config.inc.php");

$nb = new Netbank(); 

$nb->login($clientno, $password);

$accounts = $nb->getAccounts();

/* This contains a list of accounts by name and their current balance */
print_r($accounts);

foreach ($accounts as $id => $a) {
/* This retrieves transactions from an account */
	$acc = $nb->getAccountData($id, 5); 
	print_r($acc);
}


?>
