# SimplifyLibasynql
- A Library for PocketMine-PMMP, A Library that helps you create DataBase easily through the 3rd library [Libasynql](https://poggit.pmmp.io/ci/poggit/libasynql/libasynql)

# How to install ?
- You need install: [Libasynql](https://poggit.pmmp.io/ci/poggit/libasynql/libasynql) & [LibVapmPMMP](https://poggit.pmmp.io/ci/VennDev/VapmPMMP/VapmPMMP) & ``plugin`` [VapmRunable](https://poggit.pmmp.io/ci/VennDev/VapmRunable/VapmRunable)
- Then you just need to run, install and customize your configuration in the plugin's data folder.

# Good to know !
- First, you need to know a few paths to create plugin methods.
```php
use vennv\Promise;
use vennv\Async;
use vennv\simplifylibasynql\SimplifyLibasynql;
use vennv\simplifylibasynql\TypeData;
```
# How to setup for your plugin ?
- This is plugin example: [Example](https://github.com/VennDev/SimplifyLibasynql/tree/main/Examples/Test)
- Just take everything in this [Folder](https://github.com/VennDev/SimplifyLibasynql/tree/main/SQL) and bring in your plugin configuration.
- Note: don't change any details in files like ``mysql.sql`` or ``sqlite.sql`` if you don't understand them or how this library works!
- Class registration creation template for libraries:
```php
private static DataConnector $database;
private static SimplifyLibasynql $simplifyLibasynql;

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
}

protected function onDisable() : void
{
    if (isset(self::$database)) 
    {
        self::$database->close();
    }
}
```

# How to create table ?
```php
self::$simplifyLibasynql->addTable("users", [
    "id" => TypeData::PRIMARY_KEY,
    "name" => "",
    "age" => 0
]);
```

# How to Insert/Update a column in the table ?
- It's all in the same way ``update``.
```php
self::$simplifyLibasynql->update("users", [
    "id" => 100,
    "name" => "VennV",
    "age" => 20
]);
```

# How to remove a column in the table ?
```php
# `users` is name table and `100` is primary key.
self::$simplifyLibasynql->removeDataTable("users", 100);
```
- Or remove tables
```php
# `users` is name table.
self::$simplifyLibasynql->removeTable("users");
```

# How to get All data or a data in the table ?
- First, you need to understand what asynchrony is. and how to deal with the [Vapm](https://github.com/VennDev/Vapm/blob/main/README.md) library.
- With PocketMine-PMMP you don't need the method ``endSingleJob`` or ``endMultiJobs``, because you already have plugins that handle them that are: [VapmRunable](https://poggit.pmmp.io/ci/VennDev/VapmRunable/VapmRunable)
```php
# `users` is name table.
self::$simplifyLibasynql->fetchAll("users")->then(function($result) {
  var_dump($result);
})->catch(function($error) {
  var_dump($error);
});
```
```php
# `users` is name table and `100` is primary key.
self::$simplifyLibasynql->fetchData("users", 100)->then(function($result) {
  var_dump($result);
})->catch(function($error) {
  var_dump($error);
});
```
# Mixing method
- To get to this level, you need to watch the steps above.
- You can change data when use function fetchData.
```php
# `users` is name table and `100` is primary key.
self::$simplifyLibasynql->fetchData("users", 100)->then(function($result) {
    self::$simplifyLibasynql->update("users", [
        "id" => $result["id"],
        "name" => "VennVDev",
        "age" => $result["age"]
    ]);
})->catch(function($error) {
  var_dump($error);
});
```
