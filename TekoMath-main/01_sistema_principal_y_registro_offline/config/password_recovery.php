<?php
class PasswordRecovery {
    public static function ensure(PDO $db): void {
        teko_schema_exec($db,'CREATE TABLE IF NOT EXISTS password_reset_links (
            id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE, password_fingerprint CHAR(64) NOT NULL,
            expires_at BIGINT NOT NULL, used_at BIGINT NULL, created_at BIGINT NOT NULL,
            INDEX(user_id), FOREIGN KEY(user_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB');
    }

    public static function create(PDO $db, string $email): string {
        if(strlen($email)>150 || !filter_var($email,FILTER_VALIDATE_EMAIL))
            throw new InvalidArgumentException('Ingresá un correo válido.');
        self::ensure($db);
        $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT id,password FROM usuarios WHERE email=? FOR UPDATE');$q->execute([$email]);$user=$q->fetch();
            if(!$user || AccountSecurity::state($db,(int)$user['id'])['disabled'])
                throw new InvalidArgumentException('No hay una cuenta habilitada con ese correo.');
            $token=bin2hex(random_bytes(32));
            $db->prepare('UPDATE password_reset_links SET used_at=? WHERE user_id=? AND used_at IS NULL')->execute([time(),$user['id']]);
            $db->prepare('INSERT INTO password_reset_links(user_id,token_hash,password_fingerprint,expires_at,created_at) VALUES(?,?,?,?,?)')
                ->execute([$user['id'],hash('sha256',$token),hash('sha256',$user['password']),time()+1800,time()]);
            $db->commit();return $token;
        } catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }

    public static function valid(PDO $db, string $token): bool {
        if(!preg_match('/^[a-f0-9]{64}$/D',$token))return false;
        $q=$db->prepare('SELECT r.*,u.password FROM password_reset_links r JOIN usuarios u ON u.id=r.user_id WHERE r.token_hash=?');
        $q->execute([hash('sha256',$token)]);$row=$q->fetch();
        return $row && !$row['used_at'] && (int)$row['expires_at']>time()
            && hash_equals($row['password_fingerprint'],hash('sha256',$row['password']))
            && !AccountSecurity::state($db,(int)$row['user_id'])['disabled'];
    }

    public static function redeem(PDO $db, string $token, string $password): void {
        if(!preg_match('/^[a-f0-9]{64}$/D',$token))throw new InvalidArgumentException('El enlace no es válido o venció.');
        if(mb_strlen($password)<8 || strlen($password)>72 || str_contains($password,"\0"))
            throw new InvalidArgumentException('Usá al menos 8 caracteres; si la contraseña es muy larga, acortala.');
        $db->beginTransaction();
        try {
            // Same user lock order as create(): no older link can win a concurrent replacement.
            $q=$db->prepare('SELECT user_id FROM password_reset_links WHERE token_hash=?');$q->execute([hash('sha256',$token)]);$id=$q->fetchColumn();
            if(!$id)throw new InvalidArgumentException('El enlace no es válido o venció.');
            $q=$db->prepare('SELECT password FROM usuarios WHERE id=? FOR UPDATE');$q->execute([$id]);$old=$q->fetchColumn();
            $q=$db->prepare('SELECT * FROM password_reset_links WHERE token_hash=? FOR UPDATE');$q->execute([hash('sha256',$token)]);$link=$q->fetch();
            $q=$db->prepare('SELECT disabled FROM account_security WHERE user_id=? FOR UPDATE');$q->execute([$id]);$disabled=$q->fetchColumn();
            if(!$old || !$link || $link['used_at'] || (int)$link['expires_at']<=time() || $disabled
                || !hash_equals($link['password_fingerprint'],hash('sha256',$old)))
                throw new InvalidArgumentException('El enlace no es válido o venció.');
            if(password_verify($password,$old))throw new InvalidArgumentException('Elegí una contraseña diferente de la anterior.');
            $db->prepare('UPDATE usuarios SET password=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$id]);
            $db->prepare('INSERT INTO account_security(user_id,session_version) VALUES(?,1) ON DUPLICATE KEY UPDATE session_version=session_version+1')->execute([$id]);
            $db->prepare('UPDATE password_reset_links SET used_at=? WHERE user_id=? AND used_at IS NULL')->execute([time(),$id]);
            $db->commit();
        } catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
}
