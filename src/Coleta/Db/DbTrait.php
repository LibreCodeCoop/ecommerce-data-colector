<?php
namespace ColetaDados\Db;

trait DbTrait
{
    /**
     * @var \PDO
     */
    private $pdo;
    private function getPdo()
    {
        if (!$this->pdo) {
            $this->pdo = new \PDO(
                'pgsql:host='.getenv('DB_HOST').';'.
                'dbname='.getenv('DB_NAME').';'.
                'user='.getenv('DB_USER').';'.
                'password='.getenv('DB_PASSWD')
            );
        }
        return $this->pdo;
    }
    public function setDb(\PDO $db)
    {
        $this->pdo = $db;
    }
    public function execute($query, $data = [])
    {
        $sth = $this->getPdo()->prepare($query);
        if ($sth->execute($data)) {
            return $sth->fetchAll(\PDO::FETCH_ASSOC);
        }
    }
    public function insert($data, $table, $pk = null)
    {
        $this->getPdo()->beginTransaction();
        if ($pk && is_numeric(key($data))) {
            $in  = implode(',', array_fill(0, count($data), '?'));
            $sth = $this->getPdo()->prepare(
                'SELECT '.$pk.' FROM '.$table.' WHERE '.$pk.' IN('.$in.')'
            );
            $sth->execute(array_keys($data));
            $exist = $sth->fetchAll(\PDO::FETCH_COLUMN, 0);
            $toInsert = array_filter($data, function ($row) use ($exist, $pk) {
                return !in_array($row[$pk], $exist);
            });
        } elseif ($pk && isset($data[$pk])) {
            $sth = $this->getPdo()->prepare(
                'SELECT '.$pk.' FROM '.$table.' WHERE '.$pk.' = ?'
            );
            $sth->execute([$data[$pk]]);
            $exist = $sth->fetchAll(\PDO::FETCH_COLUMN, 0);
            if ($exist) {
                $this->getPdo()->rollBack();
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
                $sth = $this->getPdo()->prepare($insert);
            $sth->execute($values);
        }
        $this->getPdo()->commit();
    }
    public function update($table, $set, $where, $data)
    {
        $update =
            'UPDATE ' . $table . ' ' .
            'SET ' . implode(',', $set) . ' ' .
            'WHERE ' . implode(',', $where);
        $sth = $this->getPdo()->prepare($update);
        $sth->execute($data);
    }
}
