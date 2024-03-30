<?php
/**
 * 数据库连接类
 *
 * @package Imccc\Snail
 * @since 0.0.1
 * @author Imccc
 * @copyright Copyright (c) 2024 Imccc.
 */

namespace Imccc\Snail\Services;

use Exception;
use Imccc\Snail\Core\Container;
use PDO;
use PDOException;
use PDOStatement;

class SqlService
{
    private $pdo;
    private $container;
    protected $config;
    protected $logger;
    private $logfile = '_SQL_';
    private $logconf;
    private $join = '';

    /**
     * 构造函数
     *
     * @throws Exception 如果连接失败，则抛出异常
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $this->container->resolve('ConfigService')->get('database');
        $this->logger = $this->container->resolve('LoggerService');
        $this->logconf = $this->container->resolve('ConfigService')->get('logger');

        $driver = $this->config['db'];
        $dsnConfig = $this->config['dsn'][$driver];
        $username = $dsnConfig['user'];
        $password = $dsnConfig['password'];
        $port = $dsnConfig['port'];
        $options = $dsnConfig['options'];

        try {
            switch ($driver) {
                case 'mysql':
                    $dsn = "mysql:host={$dsnConfig['host']};dbname={$dsnConfig['dbname']};charset={$dsnConfig['charset']};port={$port}";
                    break;
                case 'sqlsrv':
                    $dsn = "sqlsrv:Server={$dsnConfig['host']},{$port};Database={$dsnConfig['dbname']};charset={$dsnConfig['charset']}";
                    break;
                case 'oci':
                    $dsn = "oci:dbname={$dsnConfig['dbname']}";
                    break;
                default:
                    throw new Exception("Unsupported database driver: $driver");
            }

            $this->pdo = new PDO($dsn, $username, $password, $options);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            if ($this->logconf['on']['sql']) {
                $this->logger->log('PDO DSN: [' . $dsn . ']', $this->logfile);
            }
        } catch (PDOException $e) {
            if ($this->logconf['on']['sqlerr']) {
                $this->logger->log('Database Connection Error: ' . $e->getMessage(), $this->logfile);
            }
            throw new Exception('Database Connection Error: ' . $e->getMessage());
        }
    }

    /**
     * 执行查询，并返回所有结果
     *
     * @param string $sql SQL 查询语句
     * @param array $params 查询参数
     * @return array 查询结果数组
     * @throws Exception 如果查询出错，则抛出异常
     */
    public function query($sql, $params = [])
    {
        if ($logconf['on']['sql']) {
            $this->logger->log('SQL Query: [' . $sql . ' Params: ' . json_encode($params) . ']', $logfile);
        }
        try {
            $stmt = $this->pdo->prepare($sql . $this->join);
            $stmt->execute($params);
            $this->join = ''; // 重置连接条件
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // 记录查询错误日志
            if ($logconf['on']['sqlerr']) {
                $this->logger->log('SQL Query Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            }
            throw new Exception('SQL Query Error: ' . $e->getMessage());
        }
    }

    /**
     * 插入单条数据
     *
     * @param string $table 表名
     * @param array $data 要插入的数据数组，键是列名，值是要插入的值
     * @return bool 插入是否成功
     * @throws Exception 如果插入出错，则抛出异常
     */
    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        if ($logconf['on']['sql']) {
            $this->logger->log('Insert Sql: [' . $sql . ' Data: ' . json_encode($data) . ']', $logfile);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));
            return $this;
        } catch (PDOException $e) {
            // 记录错误日志
            if ($logconf['on']['sqlerr']) {
                $this->logger->log('Insert Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            }
            throw new Exception('Insert Error: ' . $e->getMessage());
        }
    }

    /**
     * 更新单条数据
     *
     * @param string $table 表名
     * @param array $data 要更新的数据数组，键是列名，值是要更新的值
     * @param string $condition 更新条件
     * @param array $params 更新条件中的参数
     * @return bool 更新是否成功
     * @throws Exception 如果更新出错，则抛出异常
     */
    public function update($table, $data, $condition, $params = [])
    {
        $setClauses = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "$key = ?";
            $params[] = $value;
        }

        $sql = "UPDATE $table SET " . implode(', ', $setClauses) . " WHERE $condition";

        if ($logconf['on']['sql']) {
            $this->logger->log('Update Sql: [' . $sql . ' Params: ' . json_encode($params) . ']', $logfile);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            // 记录错误日志
            if ($logconf['on']['sqlerr']) {
                $this->logger->log('Update Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            }
            throw new Exception('Update Error: ' . $e->getMessage());
        }
    }

    /**
     * 删除单条数据
     *
     * @param string $table 表名
     * @param string $condition 删除条件
     * @param array $params 删除条件中的参数
     * @return bool 删除是否成功
     * @throws Exception 如果删除出错，则抛出异常
     */
    public function delete($table, $condition, $params = [])
    {
        $sql = "DELETE FROM $table WHERE $condition";

        if ($logconf['on']['sql']) {
            $this->logger->log('Delete Sql: [' . $sql . ' Params: ' . json_encode($params) . ']', $logfile);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            // 记录错误日志
            if ($logconf['on']['sqlerr']) {
                $this->logger->log('Delete Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            }
            throw new Exception('Delete Error: ' . $e->getMessage());
        }
    }

    /**
     * 执行 SQL 语句·
     *
     * @param string $sql SQL 语句
     * @param array $params 参数
     * @return bool 执行结果（成功返回 true，失败返回 false）
     * @throws Exception 如果执行出错，则抛出异常
     */
    public function execute($sql, $params = [])
    {
        if ($logconf['on']['sql']) {
            $this->logger->log('SQL Execute: [' . $sql . ' Params: ' . json_encode($params) . ']', $logfile);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            // 记录执行错误日志
            $this->logger->log('SQL Execution Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            throw new Exception('SQL Execution Error: ' . $e->getMessage());
        }
    }

    /**
     * 执行查询，并返回第一行结果
     *
     * @param string $sql SQL 查询语句
     * @param array $params 查询参数
     * @return array|null 第一行结果数组，如果没有结果则返回 null
     * @throws Exception 如果查询出错，则抛出异常
     */
    public function fetch($sql, $params = [])
    {
        if ($logconf['on']['sql']) {
            $this->logger->log('SQL Fetch: [' . $sql . ' Params: ' . json_encode($params) . ']', $logfile);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // 记录查询错误日志
            $this->logger->log('SQL Fetch Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            throw new Exception('SQL Fetch Error: ' . $e->getMessage());
        }
    }

    /**
     * 执行查询，并返回所有结果
     *
     * @param string $sql SQL 查询语句
     * @param array $params 查询参数
     * @return SqlService 当前对象的实例
     * @throws Exception 如果查询出错，则抛出异常
     */
    public function fetchAll($sql, $params = [])
    {
        if ($logconf['on']['sql']) {
            $this->logger->log('SQL FetchAll: [' . $sql . ' Params: ' . json_encode($params) . ']', $logfile);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $this->result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this;
        } catch (PDOException $e) {
            // 记录查询错误日志
            $this->logger->log('SQL Fetch Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            throw new Exception('SQL Fetch Error: ' . $e->getMessage());
        }
    }

    /**
     * 获取最后插入行的 ID
     *
     * @return string 最后插入行的 ID
     */
    public function lastInsertId()
    {
        if ($logconf['on']['sql']) {
            $this->logger->log('Last Insert ID: ' . $this->pdo->lastInsertId(), $logfile);
        }
        return $this->pdo->lastInsertId();
    }

    /**
     * 生成内连接 SQL
     *
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @return string 内连接 SQL
     */
    public function innerJoin($table, $condition)
    {
        $this->join .= " INNER JOIN $table ON $condition";
        return $this;
    }

    /**
     * 生成左连接 SQL
     *
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @return string 左连接 SQL
     */
    public function leftJoin($table, $condition)
    {
        $this->join .= " LEFT JOIN  $table ON $condition";
        return $this;
    }

    /**
     * 生成右连接 SQL
     *
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @return string 右连接 SQL
     */
    public function rightJoin($table, $condition)
    {
        $this->join .= " RIGHT JOIN $table ON $condition";
        return $this;
    }

    /**
     * 指定连接条件
     *
     * @param string $condition 连接条件
     * @return string 连接条件
     */
    public function on($condition)
    {
        $this->join .= " ON $condition";
        return $this;
    }

    /**
     * 生成复杂条件 SQL
     *
     * @param array $conditions 条件数组
     * @return string
     */
    public function complexCondition($conditions)
    {
        $sql = '';
        foreach ($conditions as $key => $value) {
            // 这里可以根据实际情况自定义条件拼接方式
            $sql .= "$key = '$value' AND ";
        }
        // 去除最后一个 AND，并返回条件 SQL
        return rtrim($sql, ' AND ');
    }

    /**
     * 开始事务
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
        return $this;
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->pdo->commit();
        return $this;
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $this->pdo->rollBack();
        return $this;
    }

    /**
     * 参数绑定
     *
     * @param PDOStatement $stmt PDOStatement 对象
     * @param array $params 参数数组
     */
    public function bindParams($stmt, $params)
    {
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
    }

    /**
     * 分页查询
     *
     * @param string $sql SQL 查询语句
     * @param int $limit 查询结果限制数量
     * @param int $offset 查询结果偏移量
     * @return array 查询结果数组
     * @throws Exception 如果查询出错，则抛出异常
     */
    public function paginate($sql, $limit, $offset)
    {
        $sql .= " LIMIT $limit OFFSET $offset";
        return $this->query($sql);
    }

    /**
     * 批量插入数据
     *
     * @param string $table 表名
     * @param array $data 要插入的数据数组，每个元素是一个关联数组，键是列名，值是要插入的值
     * @return bool 插入是否成功
     * @throws Exception 如果插入出错，则抛出异常
     */
    public function batchInsert($table, $data)
    {
        // 如果数据为空，直接返回 true
        if (empty($data)) {
            return $this;
        }

        // 获取列名
        $columns = implode(', ', array_keys($data[0]));
        // 生成占位符
        $placeholders = '(' . implode(', ', array_fill(0, count($data[0]), '?')) . ')';
        // 生成多个占位符组成的字符串
        $values = implode(', ', array_fill(0, count($data), $placeholders));

        $sql = "INSERT INTO $table ($columns) VALUES $values";

        if ($logconf['on']['sql']) {
            $this->logger->log('Batch Insert: [' . $sql . ' Params: ' . json_encode($data) . ']', $logfile);
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            // 执行多次插入
            foreach ($data as $item) {
                $stmt->execute(array_values($item));
            }

            return $this;
        } catch (PDOException $e) {
            // 记录错误日志
            if ($logconf['on']['sqlerr']) {
                $this->logger->log('Batch Insert Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            }
            throw new Exception('Batch Insert Error: ' . $e->getMessage());
        }
    }

    /**
     * 批量更新数据
     *
     * @param string $table 表名
     * @param array $data 要更新的数据数组，每个元素是一个关联数组，键是列名，值是要更新的值
     * @param string $condition 更新条件
     * @param array $params 更新条件中的参数
     * @return bool 更新是否成功
     * @throws Exception 如果更新出错，则抛出异常
     */
    public function batchUpdate($table, $data, $condition, $params = [])
    {
        // 如果数据为空，直接返回 true
        if (empty($data)) {
            return $this;
        }

        try {
            foreach ($data as $item) {
                $setClauses = [];
                foreach ($item as $key => $value) {
                    $setClauses[] = "$key = ?";
                    $params[] = $value;
                }

                $sql = "UPDATE $table SET " . implode(', ', $setClauses) . " WHERE $condition";

                if ($logconf['on']['sql']) {
                    $this->logger->log('Batch Update: [' . $sql . ' Params: ' . json_encode($params) . ']', $logfile);
                }
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            return $this;
        } catch (PDOException $e) {
            // 记录错误日志
            if ($logconf['on']['sqlerr']) {
                $this->logger->log('Batch Update Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            }
            throw new Exception('Batch Update Error: ' . $e->getMessage());
        }
    }

    /**
     * 批量删除数据
     *
     * @param string $table 表名
     * @param string $condition 删除条件
     * @param array $params 删除条件中的参数
     * @return bool 删除是否成功
     * @throws Exception 如果删除出错，则抛出异常
     */
    public function batchDelete($table, $condition, $params = [])
    {
        $sql = "DELETE FROM $table WHERE $condition";
        if ($logconf['on']['sql']) {
            $this->logger->log('Batch Delete: [' . $sql . ' Params: ' . json_encode($params) . ']', $logfile);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            // 记录错误日志
            if ($logconf['on']['sqlerr']) {
                $this->logger->log('Batch Delete Error: ' . $e->getMessage() . ' [SQL: ' . $sql . ']', $logfile);
            }
            throw new Exception('Batch Delete Error: ' . $e->getMessage());
        }
    }

}