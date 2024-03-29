<?php

namespace Imccc\Snail\Services\Drivers;

use MongoDB\Client;

class MongoCacheDriver
{
    protected $collection;

    public function __construct($config)
    {
        $client = new Client($config['dsn']);
        $this->collection = $client->selectDatabase($config['database'])->selectCollection($config['collection']);
    }

    public function get($key)
    {
        $document = $this->collection->findOne(['_id' => $key]);
        return $document ? $document['value'] : null;
    }

    public function set($key, $value, $expiration = 0)
    {
        $this->collection->updateOne(['_id' => $key], ['$set' => ['value' => $value]], ['upsert' => true]);
        return true;
    }

    public function clear()
    {
        $this->collection->deleteMany([]);
        return true;
    }
}
