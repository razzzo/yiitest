<?php

namespace common\components;

use yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Komponent konfiguracji aplikacji na podstawie rozszerzenia CmsSettings
 * do wersji Yii 1.x.
 *
 * http://www.yiiframework.com/extension/settings/
 */
class Config extends Component
{

    protected $saveItemsToDatabase = [];
    protected $deleteItemsFromDatabase = [];
    protected $deleteCategoriesFromDatabase = [];
    protected $cacheNeedsFlush = [];
    protected $cacheRegistry = [];
    protected $initCacheRegistry = [];
    protected $deleteFromCacheRegistry = [];

    protected $items = [];
    protected $loaded = [];

    protected $_cacheComponentId = 'cache';
    protected $_cacheId = 'global_website_settings';
    protected $_cacheTime = 0;

    protected $_dbComponentId = 'db';
    protected $_tableName = '{{settings}}';
    protected $_createTable = false;
    protected $_dbEngine = 'InnoDB';

    public function init()
    {
        parent::init();
        //Yii::app()->attachEventHandler('onEndRequest', array($this, 'whenRequestEnds'));
        // inne wersje podczepiania funkcji callback do eventu
        // najlepiej sprawdzić w C:\wamp\www\yii2adv\vendor\yiisoft\yii2\yii\base\Component :: on()
        //Yii::$app->on('afterRequest', function ($event) { var_dump('sdfsdf');} );
        Yii::$app->on(\yii\web\Application::EVENT_AFTER_REQUEST, array($this, 'whenRequestEnds'));

        if ($this->getCreateTable()) {
            $this->createTable();
        }
    }

    /**
     * CmsSettings::set()
     *
     * @param  string      $category   name of the category
     * @param  mixed       $key
     *                                 can be either a single item (string) or an array of item=>value pairs
     * @param  mixed       $value      value to set for the key, leave this empty if $key is an array
     * @param  bool        $toDatabase whether to save the items to the database
     * @return CmsSettings
     */
    public function set($category = 'system', $key = '', $value = '', $toDatabase = true)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($category, $k, $v, $toDatabase);
            }
        } else {
            if ($toDatabase) {
                if (isset($this->saveItemsToDatabase[$category]) && is_array($this->saveItemsToDatabase[$category])) {
                    $this->saveItemsToDatabase[$category] = ArrayHelper::merge($this->saveItemsToDatabase[$category], array($key => $value));
                } else {
                    $this->saveItemsToDatabase[$category] = array($key => $value);
                }
            }
            if (isset($this->items[$category]) && is_array($this->items[$category])) {
                $this->items[$category] = ArrayHelper::merge($this->items[$category], array($key => $value));
            } else {
                $this->items[$category] = array($key => $value);
            }
        }

        return $this;
    }

    /**
     * CmsSettings::get()
     *
     * @param  string $category name of the category
     * @param  mixed  $key
     *                          can be either :
     *                          empty, returning all items of the selected category
     *                          a string, meaning a single key will be returned
     *                          an array, returning an array of key=>value pairs
     * @param  string $default  the default value to be returned
     * @return mixed
     */
    public function get($category = 'system', $key = '', $default = null)
    {
        if (!isset($this->loaded[$category])) {
            $this->load($category);
        }

        if (empty($key) && empty($default) && !empty($category)) {
            return isset($this->items[$category]) ? $this->items[$category] : null;
        }

        if (!empty($key) && is_array($key)) {
            $toReturn = [];
            foreach ($key as $k => $v) {
                if (is_numeric($k)) {
                    $toReturn[$v] = $this->get($category, $v);
                } else {
                    $toReturn[$k] = $this->get($category, $k, $v);
                }
            }

            return $toReturn;
        }

        if (isset($this->items[$category][$key])) {
            return $this->items[$category][$key];
        }

        return $default;
    }

    /**
     * delete an item or all items from a category
     *
     * @param  string      $category the name of the category
     * @param  mixed       $key
     *                               can be either:
     *                               empty, meaning it will delete all items of the selected category
     *                               a single key
     *                               an array of keys
     * @return CmsSettings
     */
    public function delete($category, $key = '')
    {
        if (empty($category)) {
            return $this;
        }

        if (!empty($category) && empty($key)) {
            $this->deleteCategoriesFromDatabase[] = $category;
            $this->deleteCache($category);
            if (isset($this->items[$category])) {
                unset($this->items[$category]);
            }

            return;
        }
        if (is_array($key)) {
            foreach ($key as $k) {
                $this->delete($category, $k);
            }
        } else {
            if (isset($this->items[$category][$key])) {
                unset($this->items[$category][$key]);
                if (empty($this->deleteItemsFromDatabase[$category])) {
                    $this->deleteItemsFromDatabase[$category] = [];
                }
                $this->deleteItemsFromDatabase[$category][] = $key;
            }
        }

        return $this;
    }

    /**
     * load the cache registry
     * @return $cacheRegistry array containing all the cached categories
     */
    protected function loadCacheRegistry()
    {
        if (!empty($this->cacheRegistry)) {
            return $this->cacheRegistry;
        }

        $cacheRegistry = $this->getCacheComponent()->get('__cache_registry_'.$this->getCacheId());

        if (empty($cacheRegistry) || !is_array($cacheRegistry)) {
            $cacheRegistry = [];
        }

        $this->cacheRegistry = $cacheRegistry;
        $this->initCacheRegistry = $cacheRegistry;

        return $this->cacheRegistry;
    }

    /**
     * add to cache registry
     *
     * @param $category - the category to be added to cache.
     * @return CmsSettings
     */
    protected function addToCacheRegistry($category)
    {
        $cacheRegistry = $this->loadCacheRegistry();
        if (!in_array($category, $cacheRegistry)) {
            $this->cacheRegistry[] = $category;
        }

        return $this;
    }

    /**
     * delete one/more/all categories from cache
     *
     * @param  mixed       $category the name of the category
     *                               if $category is empty will delete all cached categories.
     *                               if $category is an array, will delete all provided categories
     *                               if $category is a string, will delete only that particular category
     * @return CmsSettings
     */
    public function deleteCache($category = '')
    {
        $cacheRegistry = $this->loadCacheRegistry();

        if (empty($category)) {
            $this->deleteFromCacheRegistry = ArrayHelper::merge($this->deleteFromCacheRegistry, $cacheRegistry);
            $cacheRegistry = [];
        } elseif (is_string($category) && in_array($category, $cacheRegistry)) {
            unset($cacheRegistry[array_search($category, $cacheRegistry)]);
            $this->deleteFromCacheRegistry[] = $category;
        } elseif (is_array($category)) {
            foreach ($category as $catName) {
                if (in_array($catName, $cacheRegistry)) {
                    unset($cacheRegistry[array_search($catName, $cacheRegistry)]);
                    $this->deleteFromCacheRegistry[] = $catName;
                }
            }
        }
        $this->cacheRegistry = $cacheRegistry;

        return $this;
    }

    /**
     * load from database the items of the specified category
     *
     * @param  string $category
     * @return array  the items of the category
     */
    public function load($category)
    {
        $items = $this->getCacheComponent()->get($category.'_'.$this->getCacheId());
        $this->loaded[$category] = true;
        $this->addToCacheRegistry($category);
        if (!$items) {
            $connection = $this->getDbComponent();
            $command = $connection->createCommand('SELECT `key`, `value` FROM '.$this->getTableName().' WHERE category=:cat');
            $command->bindParam(':cat', $category);
            $result = $command->queryAll();
            if (empty($result)) {
                return;
            }

            $items = [];
            foreach ($result as $row) {
                $items[$row['key']] = @unserialize($row['value']);
            }
            $this->getCacheComponent()->set($category.'_'.$this->getCacheId(), $items, $this->getCacheTime());
        }

        if (isset($this->items[$category])) {
            $items = ArrayHelper::merge($items, $this->items[$category]);
        }

        $this->set($category, $items, null, false);

        return $items;
    }

    public function toArray()
    {
        return $this->items;
    }

    /**
     * @param int $int the time to cache the keys, defaults to 0
     */
    public function setCacheTime($int)
    {
        $this->_cacheTime = (int) $int>0 ? $int : 0;
    }

    /**
     * @return int the time to cache the keys, defaults to 0
     */
    public function getCacheTime()
    {
        return $this->_cacheTime;
    }

    /**
     * @param string $str the cache key to prepend to all categories, defaults to 'global_website_settings'
     */
    public function setCacheId($str = '')
    {
        $this->_cacheId = !empty($str) ? $str : $this->_cacheId;
    }

    /**
     * @return string the cache key to prepend to all categories, defaults to 'global_website_settings'
     */
    public function getCacheId()
    {
        return $this->_cacheId;
    }

    /**
     * @param string $name the name of the cache component to use, defaults to 'cache'
     */
    public function setCacheComponentId($name)
    {
        $this->_cacheComponentId = $name;
    }

    /**
     * @return string the name of the cache component to use, defaults to 'cache'
     */
    public function getCacheComponentId()
    {
        return $this->_cacheComponentId;
    }

    /**
     * @param $name string the name of the settings database table, defaults to '{{settings}}'
     */
    public function setTableName($name)
    {
        if ($this->getCreateTable() && (strpos($name, '{{') != 0 || strpos($name, '}}') != (strlen($name)-2))) {
            throw new CException('The table name must be like "{{'.$name.'}}" not just "'.$name.'"');
        }
        $this->_tableName = $name;
    }

    /**
     * @return string the name of the settings database table, defaults to '{{settings}}'
     */
    public function getTableName()
    {
        return $this->_tableName;
    }

    /**
     * @param string $name the name of the db component to use, defaults to 'db'
     */
    public function setDbComponentId($name)
    {
        $this->_dbComponentId = $name;
    }

    /**
     * @return string the name of the db component to use, defaults to 'db'
     */
    public function getDbComponentId()
    {
        return $this->_dbComponentId;
    }

    /**
     * wheter to create the settings table if the table does not exist
     * set this to false in production mode as it will slow down the application
     * defaults to false
     * @param boolean $bool
     */
    public function setCreateTable($bool)
    {
        $this->_createTable = (bool) $bool;
    }

    /**
     * wheter to create the settings table if the table does not exist
     * set this to false in production mode as it will slow down the application
     * defaults to false
     * @return boolean
     */
    public function getCreateTable()
    {
        return $this->_createTable;
    }

    /**
     * @param string $name the engine to use when creating a new table, defaults to 'InnoDb'
     */
    public function setDbEngine($name)
    {
        $this->_dbEngine = $name;
    }

    /**
     * @return string the dbEngine to use when creating a new table, defaults to 'InnoDb'
     */
    public function getDbEngine()
    {
        return $this->_dbEngine;
    }

    /**
     * @return CCache the cache component
     */
    protected function getCacheComponent()
    {
        return Yii::$app->{$this->getCacheComponentId()};
    }

    /**
     * @return CDbConnection the db connection component
     */
    protected function getDbComponent()
    {
        return Yii::$app->{$this->getDbComponentId()};
    }

    protected function addDbItem($category = 'system', $key, $value)
    {
        $connection = $this->getDbComponent();
        $command = $connection->createCommand('SELECT id FROM '.$this->getTableName().' WHERE `category`=:cat AND `key`=:key LIMIT 1');
        $command->bindParam(':cat', $category);
        $command->bindParam(':key', $key);
        $result = $command->queryScalar();
        $value = @serialize($value);

        if (!empty($result)) {
            $command = $connection->createCommand('UPDATE '.$this->getTableName().' SET `value`=:value WHERE `category`=:cat AND `key`=:key');
        } else {
            $command = $connection->createCommand('INSERT INTO '.$this->getTableName().' (`category`,`key`,`value`) VALUES(:cat,:key,:value)');
        }

        $command->bindParam(':cat', $category);
        $command->bindParam(':key', $key);
        $command->bindParam(':value', $value);
        $command->execute();
    }

    /**
     * Funkcja jest uruchamiana przez aplikację w evencie onEndRequest
     * W wyniku czego nie następuje zapis zmienionych wartości gdy w kontrolerze
     * zostaje użyta np. metoda refresh();
     * Poniższa zmiana z protected na public umożliwia ręczne wywołanie metody
     * we właściwym momencie, tutaj w Config.save()
     */
    //protected function whenRequestEnds()
    public function whenRequestEnds($event)
    {
        $this->cacheNeedsFlush = [];

        if (count($this->deleteCategoriesFromDatabase)>0) {
            foreach ($this->deleteCategoriesFromDatabase as $catName) {
                $connection = $this->getDbComponent();
                $command = $connection->createCommand('DELETE FROM '.$this->getTableName().' WHERE `category`=:cat');
                $command->bindParam(':cat', $catName);
                $command->execute();
                $this->cacheNeedsFlush[] = $catName;

                if (isset($this->deleteItemsFromDatabase[$catName])) {
                    unset($this->deleteItemsFromDatabase[$catName]);
                }
                if (isset($this->saveItemsToDatabase[$catName])) {
                    unset($this->saveItemsToDatabase[$catName]);
                }
            }
        }

        if (count($this->deleteItemsFromDatabase)>0) {
            foreach ($this->deleteItemsFromDatabase as $catName => $keys) {
                $params = [];
                $i = 0;
                foreach ($keys as $v) {
                    if (isset($this->saveItemsToDatabase[$catName][$v])) {
                        unset($this->saveItemsToDatabase[$catName][$v]);
                    }
                    $params[':p'.$i] = $v;
                    ++$i;
                }
                $names = implode(',', array_keys($params));

                $connection = $this->getDbComponent();
                $query = 'DELETE FROM '.$this->getTableName().' WHERE `category`=:cat AND `key` IN('.$names.')';
                $command = $connection->createCommand($query);
                $command->bindParam(':cat', $catName);

                foreach ($params as $key => $value) {
                    $command->bindParam($key, $value);
                }

                $command->execute();
                $this->cacheNeedsFlush[] = $catName;
            }
        }
        if (count($this->saveItemsToDatabase)>0) {
            foreach ($this->saveItemsToDatabase as $catName => $keyValues) {
                foreach ($keyValues as $k => $v) {
                    $this->addDbItem($catName, $k, $v);
                }
                $this->cacheNeedsFlush[] = $catName;
            }
        }

        if (count($this->cacheRegistry) == 0 && count($this->initCacheRegistry)>0) {
            $this->getCacheComponent()->delete('__cache_registry_'.$this->getCacheId());
        } elseif (count(array_diff($this->initCacheRegistry, $this->cacheRegistry))>0 || count(array_diff($this->cacheRegistry, $this->initCacheRegistry))>0) {
            $this->getCacheComponent()->set('__cache_registry_'.$this->getCacheId(), $this->cacheRegistry, $this->getCacheTime());
        }

        if (count($this->deleteFromCacheRegistry)>0) {
            $this->cacheNeedsFlush = array_unique(ArrayHelper::merge($this->cacheNeedsFlush, $this->deleteFromCacheRegistry));
        }

        if (count($this->cacheNeedsFlush)>0) {
            foreach ($this->cacheNeedsFlush as $catName) {
                $this->getCacheComponent()->delete($catName.'_'.$this->getCacheId());
            }
        }
    }

    /**
     * create the settings table
     */
    protected function createTable()
    {
        $connection = $this->getDbComponent();
        $tableName = $connection->tablePrefix.str_replace(array('{{', '}}'), '', $this->getTableName());
        $sql = 'CREATE TABLE IF NOT EXISTS `'.$tableName.'` (
		  `id` int(11) NOT NULL auto_increment,
		  `category` varchar(64) NOT NULL default \'system\',
		  `key` varchar(255) NOT NULL,
		  `value` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `category_key` (`category`,`key`)
		) '.($this->getDbEngine() ? 'ENGINE='.$this->getDbEngine() : '').'  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; ';
        $command = $connection->createCommand($sql);
        $command->execute();
    }
}
