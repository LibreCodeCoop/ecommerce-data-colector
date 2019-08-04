<?php
namespace ColetaDados\Db;

trait DbTrait
{
    /**
     * @var \PDO
     */
    private $pdo;
    public function setDb(\PDO $db)
    {
        $this->pdo = $db;
    }
    public function insert($data, $table, $pk)
    {
        $this->pdo->beginTransaction();
        $in  = implode(',', array_fill(0, count($data), '?'));
        $sth = $this->pdo->prepare(
            'SELECT '.$pk.' FROM '.$table.' WHERE codigo IN('.$in.')'
        );
        $sth->execute(array_keys($data));
        $exist = $sth->fetchAll(\PDO::FETCH_COLUMN, 0);
        $toInsert = array_filter($data, function ($row) use ($exist, $pk) {
            return !in_array($row[$pk], $exist);
        });
        if (count($toInsert)) {
            $columns = array_keys(current($data));
            $insert =
            'INSERT INTO '.$table.' ('.implode(',', $columns).')'.
            ' VALUES ' .
            implode(
                ',',
                array_fill(
                    0,
                    count($toInsert),
                    '('.implode(',', array_fill(0, count($columns), '?')).')'
                )
            );
            $sth = $this->pdo->prepare($insert);
            $values = array_reduce($toInsert, function ($carry, $item) {
                return array_merge($carry, array_values($item));
            }, []);
                $sth->execute($values);
        }
        $this->pdo->commit();
    }
    public function update($table, $set, $where, $data)
    {
        $update =
            'UPDATE ' . $table . ' ' .
            'SET ' . implode(',', $set) . ' ' .
            'WHERE ' . implode(',', $where);
        $sth = $this->pdo->prepare($update);
        $sth->execute($data);
    }
}
