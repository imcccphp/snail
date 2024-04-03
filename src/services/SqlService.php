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
    private $logprefix = ['sql', 'sqlerr'];
    private $logconf;
    private $join = '';
    private $prefix;

    /**
     * 构造函数
     *
     * @throws Exception 如果连接失败，则抛出异常
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->logger = $this->container->resolve('LoggerService');
        $this->config = $this->container->resolve('ConfigService')->get('database');
        $this->logconf = $this->container->resolve('ConfigService')->get('logger.on');

        try {
            $driver = $this->config['db'];
            $dsnConfig = $this->config['dsn'][$driver];
            $username = $dsnConfig['user'];
            $password = $dsnConfig['password'];
            $port = $dsnConfig['port'];
            $options = $dsnConfig['options'];
            $this->prefix = $dsnConfig['prefix'];

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
            $this->logger->log('PDO DSN: [' . $dsn . ']', $this->logprefix[0]);
        } catch (PDOException $e) {
            $this->logger->log('Database Connection Error: ' . $e->getMessage(), $this->logprefix[1]);
            throw new Exception('Database Connection Error: ' . $e->getMessage());
        }
    }
    /**
     * 获取表前缀
     *
     * @return string 表前缀
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    protected function handleException(PDOException $e, $opt): void
    {
        // 这里可以添加异常处理逻辑，比如记录日志等
        $this->logger->log('SQL Error: ' . $opt . $e->getMessage(), $this->logprefix[1]);
        // throw $e; // 或者重新抛出异常
        throw new Exception($opt . $e->getMessage());
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
        $this->logger->log('SQL Query: [' . $sql . ' Params: ' . json_encode($params) . ']', $this->logprefix[0]);
        try {
            $stmt = $this->pdo->prepare($sql . $this->join);
            $stmt->execute($params);
            $this->join = ''; // 重置连接条件
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->handleException($e, 'Query Error');
        }
    }

    /**
     * 执行查询，并返回第一行结果
     *
     * @param string $table 表名
     * @param array $columns 要查询的列名数组
     * @param string $condition 查询条件
     * @param array $params 查询条件中的参数
     * @return array|null 第一行结果数组，如果没有结果则返回 null
     * @throws Exception 如果查询出错，则抛出异常
     */
    public function select($table, $columns = ['*'], $condition = '', $params = [])
    {
        $columnsStr = implode(', ', $columns);
        $sql = "SELECT $columnsStr FROM $table";

        if (!empty($condition)) {
            $sql .= " WHERE $condition";
        }
        $this->logger->log('SQL Select: [' . $sql . ' Params: ' . json_encode($params) . ']', $this->logprefix[0]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->handleException($e, 'SQL Select Error:' . $sql);
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
        $this->logger->log('Insert Sql: [' . $sql . ' Data: ' . json_encode($data) . ']', $this->logprefix[0]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));
            return $this;
        } catch (PDOException $e) {
            $this->handleException($e, 'Insert Error:' . $sql);
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
        $this->logger->log('Update Sql: [' . $sql . ' Params: ' . json_encode($params) . ']', $this->logprefix[0]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            $this->handleException($e, 'Update Error:' . $sql);
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
        $this->logger->log('Delete Sql: [' . $sql . ' Params: ' . json_encode($params) . ']', $this->logprefix[0]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            $this->handleException($e, 'Delete Error:' . $sql);
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
        if ($this->logconf['sql']) {
            $this->logger->log('SQL Execute: [' . $sql . ' Params: ' . json_encode($params) . ']', $this->logprefix[0]);
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            // 记录执行错误日志
            $this->handleException($e, 'Execution Error:' . $sql);
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
        $this->logger->log('SQL Fetch: [' . $sql . ' Params: ' . json_encode($params) . ']', $this->logprefix[0]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->handleException($e, 'Fetch Error:' . $sql);
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
        $this->logger->log('SQL FetchAll: [' . $sql . ' Params: ' . json_encode($params) . ']', $this->logprefix[0]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $this->result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this;
        } catch (PDOException $e) {
            $this->handleException($e, 'FetchAll Error:' . $sql);
        }
    }

    /**
     * 获取最后插入行的 ID
     *
     * @return string 最后插入行的 ID
     */
    public function lastInsertId()
    {
        $this->logger->log('Last Insert ID: ' . $this->pdo->lastInsertId(), $this->logprefix[0]);
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
     * 绑定参数到预处理语句
     *
     * @param PDOStatement $stmt 预处理语句对象
     * @param array $params 要绑定的参数数组，格式为 [':paramName' => $value]
     * @return void
     * @throws Exception 如果绑定参数失败，则抛出异常
     */
    public function bindParams(PDOStatement $stmt, array $params)
    {
        foreach ($params as $paramName => $value) {
            // 获取参数类型
            $paramType = PDO::PARAM_STR;
            if (is_int($value)) {
                $paramType = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $paramType = PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $paramType = PDO::PARAM_NULL;
            }

            // 尝试绑定参数
            try {
                $stmt->bindValue($paramName, $value, $paramType);
            } catch (PDOException $e) {
                $this->handleException($e, 'Parameter Binding Error:' . $sql);
            }
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
        $this->logger->log('Batch Insert: [' . $sql . ' Params: ' . json_encode($data) . ']', $this->logprefix[0]);
        try {
            $stmt = $this->pdo->prepare($sql);
            // 执行多次插入
            foreach ($data as $item) {
                $stmt->execute(array_values($item));
            }
            return $this;
        } catch (PDOException $e) {
            $this->handleException($e, 'Batch Insert  Error:' . $sql);
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
                $this->logger->log('Batch Update: [' . $sql . ' Params: ' . json_encode($params) . ']', $this->logprefix[0]);
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }
            return $this;
        } catch (PDOException $e) {
            $this->handleException($e, 'Batch Update  Error:' . $sql);
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
        $this->logger->log('Batch Delete: [' . $sql . ' Params: ' . json_encode($params) . ']', $this->logprefix[0]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            $this->handleException($e, 'Batch Delete  Error:' . $sql);
        }
    }

    /**
     * 建表
     *
     * @param string $table 表名
     * @param array $columns 列定义数组
     * @return bool 是否成功
     * @throws Exception 如果建表失败，则抛出异常
     */
    public function createTable($table, $columns)
    {
        $sql = "CREATE TABLE IF NOT EXISTS $table (";
        $columnDefinitions = [];
        foreach ($columns as $columnName => $columnDefinition) {
            $columnDefinitions[] = "$columnName $columnDefinition";
        }
        $sql .= implode(', ', $columnDefinitions);
        $sql .= ")";
        $this->logger->log('Create Table: [' . $sql . ']', $this->logprefix[0]);
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            $this->handleException($e, 'Create Table  Error:' . $sql);
        }
    }

    /**
     * 清空表
     *
     * @param string $table 表名
     * @return bool 是否成功
     * @throws Exception 如果清空表失败，则抛出异常
     */
    public function truncateTable($table)
    {
        $sql = "TRUNCATE TABLE $table";
        $this->logger->log('Truncate Table: [' . $sql . ']', $this->logprefix[0]);
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            $this->handleException($e, 'Truncate Table  Error:' . $sql);
        }
    }

    /**
     * 删除表
     *
     * @param string $table 表名
     * @return bool 是否成功
     * @throws Exception 如果删除表失败，则抛出异常
     */
    public function dropTable($table)
    {
        $sql = "DROP TABLE IF EXISTS $table";
        $this->logger->log('Drop Table: [' . $sql . ']', $this->logprefix[0]);
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            $this->handleException($e, 'Drop Table  Error:' . $sql);
        }
    }

    /**
     * 更新表格
     *
     * @param string $table 表名
     * @param array $changes 要修改的列定义数组
     * @return bool 是否成功
     * @throws Exception 如果更新表格失败，则抛出异常
     */
    public function alterTable($table, $changes)
    {
        $sql = "ALTER TABLE $table";
        $alterations = [];
        foreach ($changes as $changeType => $changeDefinition) {
            $alterations[] = "$changeType $changeDefinition";
        }
        $sql .= ' ' . implode(', ', $alterations);
        $this->logger->log('Alter Table: [' . $sql . ']', $this->logprefix[0]);
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            $this->handleException($e, 'Alter Table  Error:' . $sql);
        }
    }

    /**
     * 导出 SQL
     *
     * @param string $filePath 导出文件路径
     * @return bool 是否成功
     * @throws Exception 如果导出 SQL 失败，则抛出异常
     */
    public function exportSql($filePath)
    {
        $sql = 'SHOW TABLES';
        $tables = $this->query($sql);
        $output = '';

        foreach ($tables as $table) {
            $tableName = reset($table);
            $output .= "DROP TABLE IF EXISTS $tableName;\n\n";
            $createTableSQL = $this->pdo->query("SHOW CREATE TABLE $tableName")->fetch(PDO::FETCH_ASSOC)['Create Table'];
            $output .= $createTableSQL . ";\n\n";

            $selectQuery = "SELECT * FROM $tableName";
            $rows = $this->query($selectQuery);
            foreach ($rows as $row) {
                $columnNames = implode(', ', array_keys($row));
                $columnValues = implode(', ', array_map(function ($value) {
                    return is_numeric($value) ? $value : "'" . addslashes($value) . "'";
                }, $row));
                $output .= "INSERT INTO $tableName ($columnNames) VALUES ($columnValues);\n";
            }
            $output .= "\n";
        }
        try {
            file_put_contents($filePath, $output);
            return true;
        } catch (Exception $e) {
            $this->handleException($e, 'Export SQL  Error:' . $sql);
        }
    }

    /**
     * 导入 SQL 或 ZIP 文件
     *
     * @param string $filePath 导入文件路径，可以是 SQL 文件或包含 SQL 文件的 ZIP 文件
     * @return bool 是否成功
     * @throws Exception 如果导入 SQL 失败，则抛出异常
     */
    public function importSql($filePath)
    {
        // 检查文件类型
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($extension === 'zip') {
            // 如果是 ZIP 文件，则解压并查找 SQL 文件
            $zip = new ZipArchive;
            if ($zip->open($filePath) === true) {
                // 解压缩 ZIP 文件到临时目录
                $tempDir = sys_get_temp_dir() . '/' . uniqid('sql_import_', true);
                $zip->extractTo($tempDir);
                $zip->close();

                // 遍历临时目录中的文件
                $files = scandir($tempDir);
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                        // 如果是 SQL 文件，则导入
                        $sqlFile = $tempDir . '/' . $file;
                        $this->importSingleSqlFile($sqlFile);
                    }
                }
                // 删除临时目录
                $this->deleteDirectory($tempDir);
                return true;
            } else {
                $this->handleException($e, 'Failed to open the ZIP file');
            }
        } elseif ($extension === 'sql') {
            // 如果是 SQL 文件，则直接导入
            return $this->importSingleSqlFile($filePath);
        } else {
            $this->handleException($e, 'Unsupported file format:' . $filePath);
        }
    }

/**
 * 导入单个 SQL 文件
 *
 * @param string $filePath SQL 文件路径
 * @return bool 是否成功
 * @throws Exception 如果导入 SQL 失败，则抛出异常
 */
    private function importSingleSqlFile($filePath)
    {
        $sql = file_get_contents($filePath);

        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            $this->handleException($e, 'Import SQL Error:' . $sql);
        }
    }

/**
 * 递归删除目录
 *
 * @param string $directory 目录路径
 */
    private function deleteDirectory($directory)
    {
        if (!file_exists($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), array('.', '..'));
        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}
