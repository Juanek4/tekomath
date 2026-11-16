<?php
require_once __DIR__.'/../../../../config/runtime.php';
// Legacy variable names are mapped to the common private configuration.
function env($key,$default=null){
 $map=['DB_HOST'=>'HOST','DB_NAME'=>'NAME','DB_USER'=>'USER','DB_PASS'=>'PASSWORD','DB_PORT'=>'PORT'];
 if(isset($map[$key]))return teko_database('tutor')[strtolower($map[$key])==='password'?'password':strtolower($map[$key])];
 if($key==='DB_DRIVER')return teko_production()?'mysql':teko_config('TUTOR_DB_DRIVER','mysql');
 return teko_config($key,$default);
}
