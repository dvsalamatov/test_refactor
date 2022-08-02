<?php

/*
Предисловие:
  Разработчика попросили получить данные из стороннего сервиса.
  Данные необходимо было кешировать, ошибки логировать.
  Разработчик с задачей справился, ниже предоставлен его код.

Задание:
- Решение рабочее, но довольно грубое. Требуется доработать код так,
  чтобы можно было быстро добавить дополнительные звенья в цепочку вызова.
  Например чтобы вместо текущего "Cache -> Сторонний сервис", можно было сделать
  "Cache -> MySQL -> Сторонний сервис" без особых проблем.

- В целом, провести рефакторинг, основываясь, что актуальная версии php 8.0

(Готово решение завернуть в secret gist или какой-либо приватный репозиторий)
*/

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class DataProvider
{
    private $host;
    private $user;
    private $password;

    /**
     * @param $host
     * @param $user
     * @param $password
     */
    public function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function get(array $request)
    {
        // returns a response from external service
    }
}

class DecoratorManager extends DataProvider
{
    public $cache;
    public $logger;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($host, $user, $password, CacheItemPoolInterface $cache)
    {
        parent::__construct($host, $user, $password);
        $this->cache = $cache;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(array $input)
    {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = parent::get($input);

            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );

            return $result;
        } catch (Exception $e) {
            $this->logger->critical('Error');
        }

        return [];
    }

    public function getCacheKey(array $input)
    {
        return json_encode($input);
    }
}
