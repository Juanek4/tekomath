<?php
require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/registration_policy.php';
foreach(['docente','admin','estudiante',null,['docente']] as $input){
 if(publicRegistrationRole($input)!=='estudiante')throw new RuntimeException('Public role escalation');
}
$token=bin2hex(random_bytes(32));
foreach([null,[], '',str_repeat('a',64)] as $bad){if(validRegistrationToken($bad,$token))throw new RuntimeException('Invalid token accepted');}
if(!validRegistrationToken($token,$token))throw new RuntimeException('Valid token rejected');
if(validRegistrationToken('',''))throw new RuntimeException('Empty session accepted');
echo "PASS: public registration cannot grant staff roles; CSRF checks reject missing, malformed and incorrect tokens.\n";
