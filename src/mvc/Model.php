<?php

namespace Imccc\Snail\Mvc;

use Imccc\Snail\Core\Container;

class Model
{
    protected $sqlService;
    protected $table;
    protected $conditions = [];
    protected $fields = ['*'];

    public function __construct(Container $container)
    {
        $this->container = $container;
        // 从容器中解析 SqlService 对象
        $this->sqlService = $container->resolve('SqlService');
    }

    /**
     * 设置要操作的表名
     *
     * @param string $table 表名
     * @return $this
     */
    public function setModel($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 设置查询条件
     *
     * @param array $conditions 查询条件
     * @return $this
     */
    public function where($conditions)
    {
        $this->conditions = $conditions;
        return $this;
    }

    /**
     * 设置返回的字段列表
     *
     * @param array $fields 返回的字段列表
     * @return $this
     */
    public function select($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * 根据条件查询数据
     *
     * @return array 查询结果数组
     */
    public function find()
    {
        return $this->sqlService->select($this->table, $this->fields, $this->conditions);
    }

    /**
     * 插入数据
     *
     * @param array $data 要插入的数据
     * @return bool 插入是否成功
     */
    public function insert($data)
    {
        return $this->sqlService->insert($this->table, $data);
    }

    /**
     * 更新数据
     *
     * @param array $data 要更新的数据
     * @return bool 更新是否成功
     */
    public function update($data)
    {
        return $this->sqlService->update($this->table, $data, $this->conditions);
    }

    /**
     * 删除数据
     *
     * @return bool 删除是否成功
     */
    public function delete()
    {
        return $this->sqlService->delete($this->table, $this->conditions);
    }

    // 支持链式调用
    public function __call($name, $arguments)
    {
        if (in_array($name, ['find', 'insert', 'update', 'delete'])) {
            $result = $this->$name();
            $this->reset();
            return $result;
        }
    }

    // 重置属性
    protected function reset()
    {
        $this->table = null;
        $this->conditions = [];
        $this->fields = ['*'];
    }
}
