<?php

class UserCredentialsDao extends BaseDao
{
    protected string $table = 'usercredentials';
    protected string $dtoClass = UserCredentialsDto::class;

    public function findByUserId(int $userid): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `usercredentials` WHERE `Userid` = :uid LIMIT 1');
        $stmt->execute(['uid' => $userid]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return UserCredentialsDto::fromRow($row);
    }

    public function createForUser(int $userid, ?string $from, ?string $to, ?int $nmax): void
    {
        // Nused се пази само при режим "брой влизания" (има зададен Nmax).
        // При времеви прозорец (Nmax = NULL) броячът също остава NULL.
        $nused = $nmax === null ? null : 0;

        $stmt = $this->db->prepare(
            'INSERT INTO `usercredentials` (`Userid`, `From`, `To`, `Nused`, `Nmax`)
             VALUES (:uid, :from, :to, :nused, :nmax)'
        );
        $stmt->execute([
            'uid'   => $userid,
            'from'  => $from,
            'to'    => $to,
            'nused' => $nused,
            'nmax'  => $nmax,
        ]);
    }

    public function incrementUsed(int $userid): void
    {
        $stmt = $this->db->prepare('UPDATE `usercredentials` SET `Nused` = `Nused` + 1 WHERE `Userid` = :uid');
        $stmt->execute(['uid' => $userid]);
    }

    /** Акаунти без credentials (напр. администратори) се третират като без ограничение. */
    public function isAccessUsable(?array $creds): bool
    {
        if ($creds === null) {
            return true;
        }

        $today = date('Y-m-d');

        if ($creds['from'] !== null && $today < $creds['from']) {
            return false;
        }

        if ($creds['to'] !== null && $today > $creds['to']) {
            return false;
        }

        if ($creds['nmax'] !== null && $creds['nused'] >= $creds['nmax']) {
            return false;
        }

        return true;
    }

    /** Дали изтекъл прозорец или изчерпани влизания позволяват нова регистрация със същия имейл. */
    public function canReRegister(array $creds): bool
    {
        $today = date('Y-m-d');

        if ($creds['from'] !== null && $today < $creds['from']) {
            return false;
        }

        if ($creds['to'] !== null && $today > $creds['to']) {
            return true;
        }

        if ($creds['nmax'] !== null && $creds['nused'] >= $creds['nmax']) {
            return true;
        }

        return false;
    }

    public function replaceForUser(int $userid, ?string $from, ?string $to, ?int $nmax): void
    {
        $stmt = $this->db->prepare('DELETE FROM `usercredentials` WHERE `Userid` = :uid');
        $stmt->execute(['uid' => $userid]);
        $this->createForUser($userid, $from, $to, $nmax);
    }
}
