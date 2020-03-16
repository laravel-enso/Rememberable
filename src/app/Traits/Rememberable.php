<?php

namespace LaravelEnso\Rememberable\App\Traits;

use LaravelEnso\Rememberable\App\Layers\Persistent as PersistentLayer;
use LaravelEnso\Rememberable\App\Layers\Volatile as VolatileLayer;

trait Rememberable
{
    // protected $cacheLifetime = 600 || 'forever'; // optional

    public static function bootRememberable()
    {
        self::created(fn ($model) => $model->cachePut());

        self::updated(fn ($model) => $model->cachePut());

        self::deleted(fn ($model) => $model->cacheForget());
    }

    public static function cacheGet($id)
    {
        if ($id === null) {
            return;
        }

        $key = (new static())->getTable().':'.$id;
        $model = self::getFromCache($key);

        if ($model) {
            return $model;
        }

        $model = static::find($id);

        return $model ? tap($model)->cachePut() : null;
    }

    public function cachePut()
    {
        VolatileLayer::getInstance()->cachePut($this);

        PersistentLayer::getInstance()->cachePut($this);
    }

    public function getCacheKey()
    {
        return $this->getTable().':'.$this->getKey();
    }

    public function getCacheLifetime()
    {
        return $this->cacheLifetime
            ?? config('enso.config.cacheLifetime');
    }

    private function cacheForget()
    {
        VolatileLayer::getInstance()->cacheForget($this);

        PersistentLayer::getInstance()->cacheForget($this);
    }

    private static function getFromCache($key)
    {
        $model = VolatileLayer::getInstance()->cacheGet($key);

        if ($model !== null) {
            return $model;
        }

        $model = PersistentLayer::getInstance()->cacheGet($key);

        if ($model !== null) {
            VolatileLayer::getInstance()->cachePut($model);
        }

        return $model;
    }
}
