<?php

namespace CLMVC\Core\Data;

use CLMVC\Core\Debug;
use CLMVC\Helpers\ObjectUtility;
use Exception;
use PDO;

/**
 * Class MySqlDatabase.
 */
class MySqlDatabase
{
    public $db;
    private $stmt;
    private $relations = array();
    private $indexes = array();
    private $indextype = array();
    private $cache = array();

    /**
     * @return MySqlDatabase
     */
    public static function instance()
    {
        global $db;

        return $db;
    }

    /**
     * @param bool $autoConnect
     */
    public function MySqlDatabase($autoConnect = true)
    {
        if (!$autoConnect) {
            return;
        }
        if (defined('HOST')) {
            $this->connect(HOST, DATABASE, USERNAME, PASSWORD);
        } else {
            $this->connect(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);
        }
    }

    /**
     * @param $host
     * @param $database
     * @param $username
     * @param $password
     */
    public function connect($host, $database, $username, $password)
    {
        try {
            $this->db = new PDO('mysql:host='.$host.';dbname='.$database, $username, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        } catch (\PDOException $e) {
            print 'Error!: '.$e->getMessage().'<br/>';
            die();
        }
    }

    /**
     * @param $object
     */
    public function dropTable($object)
    {
        global $db_prefix;
        $table = 'DROP TABLE `'.$db_prefix.strtolower(get_class($object)).'`';
        $this->db->exec($table);
        $arrays = ObjectUtility::getArrayProperties($object);
        foreach ($arrays as $array) {
            $settings = ObjectUtility::getCommentDecoration($object, $array.'List');
            $relation = array_key_exists_v('dbrelation', $settings);
            if ($relation) {
                $name = array_key_exists_v('dbrelationname', $settings);
                $class = new $relation();
                if (!array_key_exists($name, $this->relations)) {
                    $this->relations[$name] = array(strtolower(get_class($class).'_id'),strtolower(get_class($object).'_id'));
                }
            }
        }
    }

    /**
     * @param $object
     */
    public function createTable($object)
    {
        global $db_prefix;
        $tablename = $db_prefix.strtolower(get_class($object));
        Debug::Value('create table', $tablename);
        $table = 'CREATE TABLE `'.$tablename.'` (';
        $columns = ObjectUtility::getPropertiesAndValues($object);
        $csettings = ObjectUtility::getClassCommentDecoration($object);
        $tableengine = array_key_exists_v('table', $csettings);
        Debug::Message('gettings columns');
        foreach ($columns as $property => $value) {
            $settings = ObjectUtility::getCommentDecoration($object, 'get'.$property);
            $column = strtolower($property);
            $table .= ' `'.$column.'` ';
            if ($column == 'id') {
                $table .= 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,';
            } elseif (!isset($value)) {
                $dbfield = strtolower(array_key_exists_v('dbfield', $settings));

                switch ($dbfield) {
                    case 'char':
                        $length = array_key_exists_v('dblength', $settings);
                        $length = $length ? $length : 45;
                        $column_sql = 'VARCHAR('.$length.') NOT NULL default \'\',';
                        break;
                    case 'boolean':
                        $column_sql = 'tinyint(1) NOT NULL default 0,';
                        break;
                    case 'text':
                        $column_sql = 'TEXT NOT NULL,';
                        break;
                    case stristr($dbfield, 'int'):
                        $column_sql = "$dbfield NOT NULL default 0,";
                    break;
                    case stristr($dbfield, 'decimal'):
                        $column_sql = str_replace('|', ',', $dbfield).' NOT NULL default 0,';
                        break;
                    default:
                        $column_sql = 'VARCHAR(45) NOT NULL default \'\',';
                        break;
                }
                $table .= $column_sql;
            } elseif (is_int($value)) {
                $table .= 'INTEGER NOT NULL default '.$value.',';
            } elseif (is_string($value)) {
                $dbfield = array_key_exists_v('dbfield', $settings);
                switch ($dbfield) {
                    case 'text':
                        $column_sql = 'TEXT NOT NULL,';
                        break;
                    case 'datetime':
                      $column_sql = 'DATETIME NOT NULL,';
                        break;
                    default:
                        $length = array_key_exists_v('dblength', $settings);
                        $length = $length ? $length : 45;
                        $column_sql = 'VARCHAR('.$length.') NOT NULL default \''.$value.'\',';
                        break;
                }
                $table .= $column_sql;
            }
            $dbindexkind = array_key_exists_v('dbindexkind', $settings);
            $dbindexorder = array_key_exists_v('dbindexorder', $settings);
            $dbindexname = array_key_exists_v('dbindexname', $settings);
            if ($dbindexkind == 'primary') {
                Debug::Message('Primary index');
                if ($dbindexorder > 0) {
                    $this->indexes[$tablename]['primary'][intval($dbindexorder)] = ' `'.$column.'` ';
                } else {
                    $this->indexes[$tablename][$dbindexname] = '`'.$column.'` ';
                }
                $this->indextype[$tablename]['primary'] = 'PRIMARY KEY';
            }

            if ($dbindexname) {
                Debug::Message('Index '.$dbindexname);
                if ($dbindexorder > 0) {
                    $this->indexes[$tablename][$dbindexname][intval($dbindexorder)] = ' `'.$column.'` ';
                } else {
                    $this->indexes[$tablename][$dbindexname] = ' `'.$column.'` ';
                }
                $this->indextype[$tablename][$dbindexname] = $dbindexkind;
            }
        }
        $table = rtrim($table, ',');
        if ($tableengine) {
            $table .= ") ENGINE $tableengine";
        } else {
            $table .= ') ENGINE MyISAM';
        }

        try {
            $this->db->exec($table);
        } catch (Exception $exception) {
            Debug::Value('Error with sql statement', $table);
            Debug::Message('PDO::errorInfo()');
            print_r($this->db->errorInfo());
        }

        $arrays = ObjectUtility::getArrayProperties($object);
        foreach ($arrays as $array) {
            $settings = ObjectUtility::getCommentDecoration($object, $array.'List');
            $relation = array_key_exists_v('dbrelation', $settings);
            if ($relation) {
                $name = array_key_exists_v('dbrelationname', $settings);
                $class = new $relation();
                if (!array_key_exists($name, $this->relations)) {
                    $this->relations[$name] = array(strtolower(get_class($class).'_id'),strtolower(get_class($object).'_id'));
                }
            }
        }
    }

    /**
     * @param $row
     *
     * @return int
     */
    public function insert($row)
    {
        if (is_array($row)) {
            global $db_prefix;
            $columns = array();
            $params = array();
            $prepared = 'INSERT INTO `'.$db_prefix.strtolower($row['table']).'`';
            $colval = $row['values'];
            foreach ($colval as $column => $value) {
                if (!empty($value)) {
                    $column = strtolower($column);
                    $columns[] = '`'.$column.'`';
                    $params[':'.$column] = $value;
                }
            }
            $prepared .= ' ('.implode(',', $columns).') ';
            $prepared .= ' VALUES('.implode(',', array_keys($params)).')';
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            $stmt = $this->db->prepare($prepared);
            Debug::Value('SQL', $prepared);
            Debug::Value('SQL Params', $params);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, $this->getParamDataType($value));
            }
            if (!$stmt) {
                Debug::Value('Error occured when preparing sql statement', $prepared);
                Debug::Value('SQL Params', $params);
                Debug::Value('PDO::errorInfo()', $this->db->errorInfo());
            }

            if (!$stmt->execute()) {
                Debug::Value('Error with sql statement', $prepared);
                Debug::Value('SQL Params', $params);
                Debug::Message('PDO::errorInfo()');
                print_r($this->db->errorInfo());
            }
        } else {
            die('MySqlDatabase->insert only accepts arrays. See documentation for structure');
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function getParamDataType($value)
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }
        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }
        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }

    /**
     * @param $row
     * @param $restriction
     */
    public function update($row, $restriction)
    {
        if (is_array($row)) {
            $params = array();
            $columns = array();
            global $db_prefix;
            $prepared = 'UPDATE `'.$db_prefix.strtolower($row['table']).'` ';
            $colval = $row['values'];
            foreach ($colval as $column => $value) {
                //				if(!empty($value)){
                    $column = strtolower($column);
                $columns[] = "`$column`=:$column";
                $params[':'.$column] = $value;
            }
            $params = array_merge($params, $restriction->getParameter());
            $prepared .= ' SET '.implode(',', $columns).' ';
            $prepared .= ' WHERE '.$restriction->toSQL();
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            $stmt = $this->db->prepare($prepared);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, $this->getParamDataType($value));
            }
            Debug::Value('Sql statement', $prepared);
            Debug::Value('SQL Params', $params);
            if (!$stmt) {
                Debug::Value('Error occured when preparing sql statement', $prepared);
                Debug::Value('SQL Params', $params);
                Debug::Value('PDO::errorInfo()', $this->db->errorInfo());
            }

            if (!$stmt->execute()) {
                Debug::Value('Error with sql statement', $prepared);
                Debug::Value('SQL Params', $params);
                Debug::Message('PDO::errorInfo()');
                print_r($this->db->errorInfo());
            }
        } else {
            die('MySqlDatabase->update only accepts arrays. See documentation for structure');
        }
//		return (int)$this->db->lastInsertId();
    }

    /**
     * @param $q
     *
     * @return mixed
     */
    public function query($q)
    {
        $paramCount = 0;
        $from = implode(',', $q->from);
        $columns = implode(',', $q->select);
        $order = '';
        $where = '';
        $limit = '';
        $groupby = '';
        $params = array();
        if ($q->hasWhere()) {
            $where = ' WHERE ';
            foreach ($q->where as $clause) {
                if (empty($clause) || $clause == null) {
                    continue;
                }
                if (is_string($clause)) {
                    $where .= $clause;
                    continue;
                }
                if ($clause->method == ' IN ') {
                    $params = array_merge($params, $clause->getParameters());
                } elseif ($clause->method == 'MATCH') {
                    $params = array_merge($params, $clause->getParameter());
                } elseif ($clause->hasValue()) {
                    $param = $clause->getParameter();
                    $param_keys = array_keys($param);
                    $paramKey = array_pop($param_keys);
                    if (array_key_exists($paramKey, $params)) {
                        $paramCount++;
                        $clause->removeParameter($paramKey);
                        $paramKey = trim($paramKey, ':');
                        $clause->setParameter($paramKey.$paramCount, $clause->getValue());
                        $param = $clause->getParameter();
                    }
                    $params = array_merge($params, $param);
                }
                $where .= $clause->toSQL();
            }
        }
        if (sizeof($q->groupby) > 0) {
            $groupby = ' GROUP BY '.implode(',', $q->groupby);
        }

        if (sizeof($q->order) > 0) {
            $order = ' ORDER BY '.implode(',', $q->order);
        }

        if ($q->hasLimit()) {
            $limit = ' LIMIT '.($q->offset ? $q->offset.','.$q->limit : $q->limit).' ';
        }
        $prepared = 'SELECT '.$columns.' FROM '.$from.$where.$groupby.$order.$limit;

        if (defined('SQLDEBUG') && SQLDEBUG) {
            Debug::Value('SQL', $prepared);
            Debug::Value('SQL Params', $params);
        }

        $stmt = $this->db->prepare($prepared);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $this->getParamDataType($value));
        }
        if (!$stmt) {
            Debug::Value('Error occured when preparing sql statement', $prepared);
            Debug::Value('SQL Params', $params);
            Debug::Value('PDO::errorInfo()', $this->db->errorInfo());
        }
        if (!$stmt->execute()) {
            Debug::Value('Error with sql statement', $prepared);
            Debug::Value('SQL Params', $params);
            Debug::Value('PDO::errorInfo()', $this->db->errorInfo());
        }
        $result = $stmt->fetchAll($fetch_style = PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param $d
     */
    public function delete($d)
    {
        $from = implode(',', $d->from);
        $where = '';
        $params = array();
        if ($d->hasWhere()) {
            foreach ($d->where as $clause) {
                $where .= $clause->toSQL();
                if ($clause->hasValue()) {
                    $param = $clause->getParameter();
                    $params = array_merge($params, $param);
                }
            }
        }
        if ($where) {
            $where = ' WHERE '.$where;
        }
        $prepared = 'DELETE FROM '.$from.$where;
        Debug::Value('SQL', $prepared);
        Debug::Value('SQL Params', $params);
        $stmt = $this->db->prepare($prepared);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $this->getParamDataType($value));
        }
        if (!$stmt) {
            Debug::Value('Error occured when preparing sql statement', $prepared);
            Debug::Value('SQL Params', $params);
            Debug::Value('PDO::errorInfo()', $this->db->errorInfo());
        }

        if (!$stmt->execute()) {
            Debug::Value('Error occured when executing sql statement', $prepared);
            Debug::Value('SQL Params', $params);
            Debug::Value('PDO::errorInfo()', $this->db->errorInfo());
        }
    }

    /**
     * @param $sql
     */
    public function executeSQL($sql)
    {
        Debug::Message("SQL command is run: $sql");
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->db->exec($sql);
    }

    /**
     *
     */
    public function close()
    {
        $this->db = null;
    }

    /**
     *
     */
    public function createStoredRelations()
    {
        global $db_prefix;
        $relations = $this->relations;
        foreach ($relations as $table => $columns) {
            $table = ' CREATE TABLE `'.$db_prefix.$table.'` ( `'.$columns[0].'` INTEGER NOT NULL, `'.$columns[1].'` INTEGER NOT NULL,';
            $table .= "PRIMARY KEY (`$columns[0]`,`$columns[1]`)) ENGINE = InnoDB;";
            try {
                $this->db->exec($table);
            } catch (Exception $exception) {
                echo $exception;
                Debug::Message($table);
            }
        }
    }

    /**
     *
     */
    public function dropStoredRelations()
    {
        global $db_prefix;
        $relations = $this->relations;
        foreach ($relations as $table => $columns) {
            $table = 'DROP TABLE `'.$db_prefix.$table.'`';
            $this->db->exec($table);
        }
    }

    /**
     *
     */
    public function createStoredIndexes()
    {
        foreach ($this->indexes as $table => $data) {
            foreach ($data as $indexname => $columns) {
                $indextype = $this->indextype[$table][$indexname];
                if (is_array($columns)) {
                    ksort($columns);
                    $columns = implode(',', $columns);
                }
                $sql = 'ALTER TABLE `'.$table.'` ';
                if ($indexname == 'primary') {
                    $sql .= " ADD PRIMARY KEY ($columns);";
                } else {
                    $sql .= " ADD $indextype `$indexname` ($columns);";
                }
                $this->db->exec("ALTER IGNORE TABLE $table DROP INDEX $indexname");
                $this->db->exec($sql);
                Debug::Value('Index Sql', $sql);
            }
        }
    }
}
