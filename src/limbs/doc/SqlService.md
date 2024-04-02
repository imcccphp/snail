# SqlService


```
这是一个用于数据库操作的PHP类，提供了连接数据库、执行查询、插入、更新、删除等功能，并支持事务、分页查询、批量操作、表格操作以及导入导出SQL等功能。

1. 数据库连接：通过构造函数 `__construct` 实现，使用 PDO 扩展与数据库建立连接，并设置了一些连接属性。

2. 数据库查询：提供了 `query`、`select`、`insert`、`update`、`delete` 方法来执行不同类型的 SQL 查询和操作。

3. 日志记录：通过注入 `LoggerService` 对象实现日志记录，记录了数据库连接信息、SQL 查询、SQL 执行错误等信息。

4. SQL 操作方法：提供了一系列方法用于执行 SQL 查询和操作，包括查询、插入、更新、删除等。

5. 数据库事务：提供了 `beginTransaction`、`commit`、`rollback` 方法来控制事务。

6. 数据库表操作：提供了建表、清空表、删除表、更新表等方法。

7. 数据库导入导出：提供了导出 SQL 和导入 SQL 的功能，支持导入 SQL 文件和 ZIP 文件。

8. 其他功能：还包括了一些其他功能，如内连接、左连接、右连接、绑定参数、分页查询、批量插入、批量更新、批量删除等。

该类封装了常用的数据库操作，通过使用 PDO 扩展和依赖注入的方式，提供了灵活、安全的数据库操作方式。

该类的主要方法包括：

- `__construct`: 构造函数，用于连接数据库。
- `query`: 执行查询并返回所有结果。
- `insert`: 插入单条数据。
- `update`: 更新单条数据。
- `delete`: 删除单条数据。
- `execute`: 执行SQL语句。
- `fetch`: 执行查询并返回第一行结果。
- `fetchAll`: 执行查询并返回所有结果。
- `lastInsertId`: 获取最后插入行的ID。
- `innerJoin`: 生成内连接SQL。
- `leftJoin`: 生成左连接SQL。
- `rightJoin`: 生成右连接SQL。
- `on`: 指定连接条件。
- `complexCondition`: 生成复杂条件SQL。
- `beginTransaction`: 开始事务。
- `commit`: 提交事务。
- `rollback`: 回滚事务。
- `bindParams`: 参数绑定。
- `paginate`: 分页查询。
- `batchInsert`: 批量插入数据。
- `batchUpdate`: 批量更新数据。
- `batchDelete`: 批量删除数据。
- `createTable`: 建表。
- `truncateTable`: 清空表。
- `dropTable`: 删除表。
- `alterTable`: 更新表格。
- `exportSql`: 导出SQL。
- `importSql`: 导入SQL或ZIP文件。

该类还包括一些辅助方法和私有方法，用于处理SQL文件的导入导出和目录的删除操作。
```

### __construct
```php
初始化了数据驱动，支持MySQL、Oracle、SQL Server等数据库。
dsn 数据库连接字符串
logger 日志记录器
config 配置信息
```
### query
```
 执行查询并返回所有结果。
```