<?php
// Run with a temporary, database-scoped migration account, never the public runtime account.
if(PHP_SAPI!=='cli')exit(1);
putenv('TEKO_SCHEMA_SETUP=1');
require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/database.php';
require_once __DIR__.'/../02_tekobot/TekoBot/Hackaton/config/database.php';
require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/admin_invitations.php';
require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/password_recovery.php';
require_once __DIR__.'/../01_sistema_principal_y_registro_offline/config/mission_attempts.php';
$main=getDB();$tutor=Database::getConnection();
// Only structural statements: no demo users, USE or CREATE DATABASE from the historical dump.
$source=file_get_contents(__DIR__.'/../02_tekobot/TekoBot/Hackaton/database/schema.sql');
$source=preg_replace('/--[^\r\n]*/','',$source);
foreach(explode(';',$source) as $sql)if(preg_match('/^\s*CREATE TABLE\b/i',$sql))$tutor->exec($sql);
AdminInvitations::ensure($main);PasswordRecovery::ensure($main);missionAttemptTable($main);
teko_schema_exec($main,'CREATE TABLE IF NOT EXISTS arcade_account_progress (user_id INT PRIMARY KEY, snapshot_json LONGTEXT NOT NULL, revision INT NOT NULL DEFAULT 1, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES usuarios(id) ON DELETE CASCADE) ENGINE=InnoDB');
teko_schema_exec($main,'CREATE TABLE IF NOT EXISTS console_credentials (id INT PRIMARY KEY, password_hash VARCHAR(255) NOT NULL, version INT NOT NULL DEFAULT 1, initial_password TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB');
teko_schema_exec($tutor,'CREATE TABLE IF NOT EXISTS tutor_session_states (session_id INTEGER PRIMARY KEY, state_json TEXT NOT NULL, revision INTEGER NOT NULL DEFAULT 1)');
teko_schema_exec($tutor,'CREATE TABLE IF NOT EXISTS portal_student_links (portal_user_id INTEGER PRIMARY KEY, tutor_student_id INTEGER NOT NULL UNIQUE)');
foreach(['main'=>$main,'tutor'=>$tutor] as $module=>$db)foreach(teko_required_tables($module) as $table)$db->query('SELECT 1 FROM `'.$table.'` LIMIT 0');
echo "Estructura preparada sin crear cuentas demo. Importá las credenciales administrativas elegidas o configurá su alta privada antes de publicar.\n";
