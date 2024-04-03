<?php

namespace Imccc\Snail\Mvc;

use Imccc\Snail\Core\Container;
use PDOException;

class Model
{
    protected $container;
    protected $sqlService;
    protected $logger;
    protected $table;
    protected $conditions = [];
    protected $fields = ['*'];
    protected $prefix;
    protected $softDeletes = true; // 假设默认开启软删除

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->sqlService = $container->resolve('SqlService');
        $this->logger = $container->resolve('LoggerService');
        $this->prefix = $this->sqlService->getPrefix();
    }

    public function withSoftDeletes(bool $enabled = true): self
    {
        $this->softDeletes = $enabled;
        return $this;
    }

    public function setModel(string $table): self
    {
        $this->table = $this->prefix . $table;
        return $this;
    }

    public function where(array $conditions): self
    {
        $this->conditions = $conditions;
        return $this;
    }

    public function select(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function find(): array
    {
        // 如果启用了软删除，自动添加条件
        if ($this->softDeletes) {
            $this->conditions['deleted_at'] = null;
        }

        try {
            $result = $this->sqlService->select($this->table, $this->fields, $this->conditions);
            $this->reset();
            return $result ?: [];
        } catch (PDOException $e) {
            // 处理异常或记录日志
            $this->handleException($e);
            return [];
        }
    }

    public function insert(array $data): bool
    {
        $this->beforeSave();
        try {
            $result = $this->sqlService->insert($this->table, $data);
            $this->afterSave();
            return $result;
        } catch (PDOException $e) {
            $this->handleException($e);
            return false;
        }
    }

    public function update(array $data): bool
    {
        $this->beforeSave();
        try {
            $result = $this->sqlService->update($this->table, $data, $this->conditions);
            $this->afterSave();
            return $result;
        } catch (PDOException $e) {
            $this->handleException($e);
            return false;
        }
    }

    public function delete(): bool
    {
        if ($this->softDeletes) {
            // 实现软删除
            return $this->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }

        try {
            $result = $this->sqlService->delete($this->table, $this->conditions);
            $this->reset();
            return $result;
        } catch (PDOException $e) {
            $this->handleException($e);
            return false;
        }
    }

    protected function beforeSave(): void
    {
        // 自定义逻辑，比如清理、验证等
    }

    protected function afterSave(): void
    {
        // 自定义逻辑，比如清理缓存、发送通知等
    }

    protected function handleException(PDOException $e): void
    {
        // 这里可以添加异常处理逻辑，比如记录日志等
        $this->logger->log('SQL Error: ' . $e->getMessage());
        // throw $e; // 或者重新抛出异常
        throw $e;
    }

    protected function reset(): void
    {
        $this->conditions = [];
        $this->fields = ['*'];
    }
}
