<?php

namespace vennv\Test;

use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use Throwable;
use vennv\Async;
use vennv\Promise;
use vennv\simplifylibasynql\SimplifyLibasynql;
use vennv\simplifylibasynql\TypeData;

final class Test extends PluginBase
{

    private static DataConnector $database;
    private static SimplifyLibasynql $simplifyLibasynql;

    /**
     * @throws Throwable
     */
    protected function onEnable() : void
    {
        $this->saveDefaultConfig();

        self::$database = libasynql::create($this, $this->getConfig()->get("database"), [
            "mysql" => "mysql.sql",
            "sqlite" => "sqlite.sql"
        ]);

        self::$database->executeGeneric("init_tables");
        self::$database->executeGeneric("init_data_handler");
		self::$database->waitAll();

        self::$simplifyLibasynql = new SimplifyLibasynql();
        self::$simplifyLibasynql->register(self::$database);

        self::$simplifyLibasynql->addTable("users", [
            "id" => TypeData::PRIMARY_KEY,
            "name" => "",
            "age" => 0
        ]);
    }

    protected function onDisable() : void
    {
        if (isset(self::$database)) 
        {
            self::$database->close();
        }
    }

    public static function getDatabase() : DataConnector
    {
        return self::$database;
    }

    /**
     * @throws Throwable
     */
    public static function addAge(int $id, int $age) : void
    {
        new Async(function () use ($id, $age) : void
        {
            $data = Async::await(self::$simplifyLibasynql->fetchData("users", $id));
            $data["age"] += $age;

            self::$simplifyLibasynql->update("users", $data);
        });
    }

    /**
     * @throws Throwable
     */
    public static function minusAge(int $id, int $age) : void
    {
        new Async(function () use ($id, $age) : void
        {
            $data = Async::await(self::$simplifyLibasynql->fetchData("users", $id));
            $data["age"] -= $age;

            self::$simplifyLibasynql->update("users", $data);
        });
    }

    /**
     * @throws Throwable
     */
    public static function changeName(int $id, string $name) : void
    {
        new Async(function () use ($id, $name) : void
        {
            $data = Async::await(self::$simplifyLibasynql->fetchData("users", $id));
            $data["name"] = $name;

            self::$simplifyLibasynql->update("users", $data);
        });
    }

    /**
     * @throws Throwable
     */
    public static function getDataUser(int $id) : Promise
    {
        return self::$simplifyLibasynql->fetchData("users", $id);
    }

    /**
     * @throws Throwable
     */
    public static function removeDataUser(int $id) : void
    {
        self::$simplifyLibasynql->removeDataTable("users", $id);
    }

}