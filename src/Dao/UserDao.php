<?php

class UserDao extends BaseDao
{
    protected string $table = 'user';
    protected string $dtoClass = UserDto::class;

    public function findByUser(string $user): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `user` WHERE LOWER(`User`) = LOWER(:user) LIMIT 1');
        $stmt->execute(['user' => trim($user)]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return UserDto::fromRow($row);
    }

    public function updatePassword(int $id, string $hash): void
    {
        $stmt = $this->db->prepare('UPDATE `user` SET `Password` = :pwd WHERE `ID` = :id');
        $stmt->execute(['pwd' => $hash, 'id' => $id]);
    }

    public function create(string $user, string $passwordHash): int
    {
        return $this->insert(['User' => $user, 'Password' => $passwordHash]);
    }
}
