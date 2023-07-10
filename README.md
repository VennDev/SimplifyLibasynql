# SimplifyLibasync [STILL-DEV]
- One plugin for PocketMine-PMMP, Help you create a database through the 3rd library [Libasynql](https://poggit.pmmp.io/ci/poggit/libasynql/libasynql)

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

# How to create table ?
```php
SimplifyLibasynql::addTable("users", [
    "id" => TypeData::PRIMARY_KEY,
    "name" => "",
    "age" => 0
]);
```

# How to Insert/Update a column in the table ?
```php
SimplifyLibasynql::update("users", [
    "id" => 100,
    "name" => "VennV",
    "age" => 20
]);
```

# How to remove a column in the table ?
```php
SimplifyLibasynql::removeDataTable("users", 100);
```
- Or remove tables
```php
SimplifyLibasynql::removeTable("users");
```

# How to get All data or a data in the table ?
```php
SimplifyLibasynql::fetchAll("users")->then(function($result) {
  var_dump($result);
});
```
```php
SimplifyLibasynql::fetchData("users", 100)->then(function($result) {
  var_dump($result);
});
