<?php

abstract class BaseDao
{
    protected PDO $db;

    /** @var string Име на таблицата в MySQL, напр. 'phenomena' */
    protected string $table;

    /** @var class-string Име на DTO класа, напр. PhenomenonDto::class */
    protected string $dtoClass;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM `{$this->table}`");
        $rows = $stmt->fetchAll();

        return array_map(
            fn (object $row) => ($this->dtoClass)::fromRow($row),
            $rows
        );
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return ($this->dtoClass)::fromRow($row);
    }
}
