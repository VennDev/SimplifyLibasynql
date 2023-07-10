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
- First, you need to understand what asynchrony is. and how to deal with the [Vapm](https://github.com/VennDev/Vapm/blob/main/README.md) library.
- With PocketMine-PMMP you don't need the method ``endSingleJob`` or ``endMultiJobs``, because you already have plugins that handle them that are: [VapmRunable](https://poggit.pmmp.io/ci/VennDev/VapmRunable/VapmRunable)
```php
SimplifyLibasynql::fetchAll("users")->then(function($result) {
  var_dump($result);
})->catch(function($error) {
  var_dump($error);
});
```
```php
SimplifyLibasynql::fetchData("users", 100)->then(function($result) {
  var_dump($result);
})->catch(function($error) {
  var_dump($error);
});
```
# Mixing method
- To get to this level, you need to watch the steps above.
- You can change data when use function fetchData.
```php
SimplifyLibasynql::fetchData("users", 100)->then(function($result) {
    SimplifyLibasynql::update("users", [
        "id" => $result["id"],
        "name" => "VennVDev",
        "age" => $result["age"]
    ]);
})->catch(function($error) {
  var_dump($error);
});
```
