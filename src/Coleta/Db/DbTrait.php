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
        if (is_numeric(key($data))) {
            $in  = implode(',', array_fill(0, count($data), '?'));
            $sth = $this->pdo->prepare(
                'SELECT '.$pk.' FROM '.$table.' WHERE '.$pk.' IN('.$in.')'
            );
            $sth->execute(array_keys($data));
            $exist = $sth->fetchAll(\PDO::FETCH_COLUMN, 0);
            $toInsert = array_filter($data, function ($row) use ($exist, $pk) {
                return !in_array($row[$pk], $exist);
            });
        } elseif (isset($data[$pk])) {
            $sth = $this->pdo->prepare(
                'SELECT '.$pk.' FROM '.$table.' WHERE '.$pk.' = ?'
            );
            $sth->execute([$data[$pk]]);
            $exist = $sth->fetchAll(\PDO::FETCH_COLUMN, 0);
            if ($exist) {
                $this->pdo->rollBack();
                return;
            }
            $toInsert = $data;
        } else {
            $toInsert = $data;
        }
        if (count($toInsert)) {
            $current = current($data);
            if (is_array($current)) {
                $columns = array_keys($current);
                $values = array_reduce($toInsert, function ($carry, $item) {
                    return array_merge($carry, array_values($item));
                }, []);
                $sqlValues = implode(
                    ',',
                    array_fill(
                        0,
                        count($toInsert),
                        '('.implode(',', array_fill(0, count($columns), '?')).')'
                    )
                );
            } else {
                $columns = array_keys($data);
                $values = array_values($data);
                $sqlValues  = '(' . implode(',', array_fill(0, count($data), '?')) . ')';
            }
            $insert =
                'INSERT INTO '.$table.' ('.implode(',', $columns).')'.
                ' VALUES ' .
                $sqlValues;
            $sth = $this->pdo->prepare($insert);
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
