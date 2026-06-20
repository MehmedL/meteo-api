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
}
