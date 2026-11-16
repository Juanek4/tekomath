<?php
require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/database.php';
$db=getDB();
$disabled=(int)$db->query('SELECT COUNT(*) FROM account_security WHERE disabled=1')->fetchColumn();
echo 'Disabled original demo accounts: '.$disabled.PHP_EOL;
