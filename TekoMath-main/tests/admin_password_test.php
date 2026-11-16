<?php
require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/database.php';require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/admin_invitations.php';
$password=trim(stream_get_contents(STDIN));$db=getDB();AdminInvitations::credentials($db);$db->beginTransaction();
try{
 if(!AdminInvitations::authenticate('tekoadmin',$password))throw new Exception('Initial authentication failed');
 $next=bin2hex(random_bytes(16));$before=AdminInvitations::credentials($db);AdminInvitations::changePassword($db,$password,$next);
 if(AdminInvitations::authenticate('tekoadmin',$password)||!AdminInvitations::authenticate('tekoadmin',$next))throw new Exception('Password rotation failed');
 $after=AdminInvitations::credentials($db);if($after['version']<=$before['version'])throw new Exception('Revocation version unchanged');
 echo "PASS admin password rotation and revocation version; change rolled back, existing password preserved.\n";
}finally{$db->rollBack();}
