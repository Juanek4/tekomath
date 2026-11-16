<?php
// Copy OUTSIDE the public directory and point TEKO_CONFIG_FILE at the copy.
// These placeholders are not usable credentials. Never commit the private copy.
return [
    'ENV'=>'production',
    'PUBLIC_URL'=>'https://tu-dominio.example/teko/',
    'MAIN_DB_HOST'=>'127.0.0.1','MAIN_DB_PORT'=>3306,'MAIN_DB_NAME'=>'teko_math',
    'MAIN_DB_USER'=>'teko_main_app','MAIN_DB_PASSWORD'=>'',
    'TUTOR_DB_HOST'=>'127.0.0.1','TUTOR_DB_PORT'=>3306,'TUTOR_DB_NAME'=>'kyhyjey_db',
    'TUTOR_DB_USER'=>'teko_tutor_app','TUTOR_DB_PASSWORD'=>'',
    'GEMINI_API_KEY'=>'',
    // Only for a deliberate initial console setup from CLI; remove afterward.
    'ADMIN_BOOTSTRAP_HASH'=>'',
];
