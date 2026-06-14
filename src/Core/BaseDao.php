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

    /**
     * Вмъква един ред. $data е масив колона => стойност.
     * Връща id-то на новия ред (lastInsertId).
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $cols = implode(', ', array_map(fn (string $c) => "`{$c}`", $columns));
        $params = implode(', ', array_map(fn (string $c) => ":{$c}", $columns));

        $stmt = $this->db->prepare("INSERT INTO `{$this->table}` ({$cols}) VALUES ({$params})");
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Вмъква много редове в една транзакция. Връща броя вмъкнати редове.
     */
    public function insertMany(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $this->db->beginTransaction();
        try {
            $count = 0;
            foreach ($rows as $row) {
                $this->insert($row);
                $count++;
            }
            $this->db->commit();

            return $count;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
