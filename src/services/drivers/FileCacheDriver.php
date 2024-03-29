<?php

namespace Imccc\Snail\Services\Drivers;

class FileCacheDriver
{
    protected $cachePath;

    public function __construct($config)
    {
        $this->cachePath = $config['path'];
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }

    public function get($key)
    {
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath) && is_readable($filePath)) {
            $content = file_get_contents($filePath);
            $data = unserialize($content);
            if ($data['expiration'] == 0 || $data['expiration'] > time()) {
                return $data['value'];
            }
            unlink($filePath); // 清除过期缓存文件
        }
        return null;
    }

    public function set($key, $value, $expiration = 0)
    {
        $filePath = $this->getCacheFilePath($key);
        $data = [
            'value' => $value,
            'expiration' => $expiration > 0 ? time() + $expiration : 0,
        ];
        $content = serialize($data);
        return file_put_contents($filePath, $content) !== false;
    }

    public function clear()
    {
        $files = glob($this->cachePath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    protected function getCacheFilePath($key)
    {
        $hash = md5($key);
        return $this->cachePath . '/' . $hash . '.cache';
    }
}
