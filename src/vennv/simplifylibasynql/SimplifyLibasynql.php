<?php

/*
 * Copyright (c) 2023 VennV
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace vennv\simplifylibasynql;

use Exception;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use vennv\Promise;
use Throwable;

final class SimplifyLibasynql extends PluginBase
{

    private const MAX_LENGTH_TYPE_LONG_TEXT = 4294967295;

    private static DataConnector $database;

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

    public static function addTable(string $name, array $value) : void
    {
        self::$database->executeSelect("get_tables", [], function(array $rows) use ($name, $value)
        {
            $havePrimaryKey = false;
            foreach ($value as $case => $data)
            {
                if ($data === TypeData::PRIMARY_KEY)
                {
                    $havePrimaryKey = true;
                }
            }

            if (!$havePrimaryKey)
            {
                throw new Exception("You must have a primary key!");
            }

            $rows = array_column($rows, "name");

            if (!isset($rows[$name]))
            {
                self::$database->executeInsert(
                    "add_table", [
                        "name" => $name,
                        "data" => json_encode($value)
                    ]
                );

                self::updateDataHandler([
                    $name => []
                ]);
            }
        });
    }

    public static function updateTable(string $name, array $value) : void
    {
        self::$database->executeSelect("get_tables", [], function(array $rows) use ($name, $value)
        {
            $rows = array_column($rows, "name");

            if (in_array($name, $rows))
            {
                self::$database->executeChange(
                    "update_table", [
                        "name" => $name,
                        "data" => json_encode($value)
                    ]
                );
            }
            else
            {
                throw new Exception("Table not found!");
            }
        });
    }

    public static function updateDataHandler(array $handler) : void
    {
        self::$database->executeSelect("get_data_handler", [], function(array $rows) use ($handler)
        {
            $array = array_keys($rows);
            $keyEnd = end($array);

            if (isset($rows[$keyEnd]))
            {
                $lastData = $rows[$keyEnd];

                $data = $lastData["data"];

                $encodeData = json_encode($handler);

                $dataAfterAdd = json_decode($data, true);
                $dataAfterAdd = array_merge($dataAfterAdd, $handler);

                $dataAfterAdd = json_encode($dataAfterAdd);

                $length = strlen($dataAfterAdd);

                if ($length >= self::MAX_LENGTH_TYPE_LONG_TEXT)
                {
                    self::$database->executeInsert(
                        "add_data_handler", [
                            "id" => $keyEnd + 1,
                            "data" => $encodeData
                        ]
                    );
                }
                else
                {
                    self::$database->executeChange(
                        "update_data_handler", [
                            "id" => $keyEnd,
                            "data" => $dataAfterAdd
                        ]
                    );
                }
            }
            else
            {
                self::$database->executeInsert(
                    "add_data_handler", [
                        "id" => 0,
                        "data" => json_encode($handler)
                    ]
                );
            }
        });
    }

    public static function update(string $tableName, array $data) : void
    {
        self::$database->executeSelect("get_tables", [], function(array $rows) use ($tableName, $data)
        {
            $dataTables = [];

            foreach ($rows as $dataQuery)
            {
                $name = $dataQuery["name"];
                $dataTable = json_decode($dataQuery["data"], true);

                $dataTables[$name] = $dataTable;
            }

            if (isset($dataTables[$tableName]))
            {
                $dataTable = $dataTables[$tableName];

                foreach ($dataTable as $key => $value)
                {
                    if (!isset($data[$key]))
                    {
                        throw new Exception("You must fill in all the data!");
                    }
                }

                $namePrimaryKey = array_search(TypeData::PRIMARY_KEY, $dataTable);
                $primaryKey = $data[$namePrimaryKey];

                $result = [];
                $result[$primaryKey] = $data;

                self::updateDataHandler([
                    $tableName => $result
                ]);
            }
            else
            {
                throw new Exception("Table not found!");
            }
        });
    }

    public static function removeDataTable(string $tableName, string $primaryKey) : void
    {
        self::$database->executeSelect("get_data_handler", [], function(array $rows) use ($tableName, $primaryKey)
        {
            foreach ($rows as $key => $data)
            {
                $dataHandler = json_decode($data["data"], true);

                if (isset($dataHandler[$tableName][$primaryKey]))
                {
                    unset($dataHandler[$tableName][$primaryKey]);

                    self::$database->executeChange(
                        "update_data_handler", [
                            "id" => $key,
                            "data" => json_encode($dataHandler)
                        ]
                    );
                }
            }
        });
    }

    public static function removeTable(string $tableName) : void
    {
        self::$database->executeSelect("get_data_handler", [], function(array $rows) use ($tableName)
        {
            foreach ($rows as $key => $data)
            {
                $dataHandler = json_decode($data["data"], true);

                if (isset($dataHandler[$tableName]))
                {
                    unset($dataHandler[$tableName]);

                    self::$database->executeChange(
                        "update_data_handler", [
                            "id" => $key,
                            "data" => json_encode($dataHandler)
                        ]
                    );
                }
            }
        });
    }

    /**
     * @throws Throwable
     */
    public static function fetchData(string $tableName, string $primaryKey) : Promise
    {
        return new Promise(function($resolve, $reject) use ($tableName, $primaryKey)
        {
            self::$database->executeSelect("get_data_handler", [], function(array $rows) use ($tableName, $primaryKey, $resolve, $reject)
            {
                foreach ($rows as $data)
                {
                    $data = json_decode($data["data"], true);

                    if (isset($data[$tableName][$primaryKey]))
                    {
                        $resolve($data[$tableName][$primaryKey]);
                    }
                    else
                    {
                        $reject(false);
                    }
                }
            });
        });
    }

    /**
     * @throws Throwable
     */
    public static function fetchAll(string $tableName) : Promise
    {
        return new Promise(function($resolve, $reject) use ($tableName)
        {
            self::$database->executeSelect("get_data_handler", [], function(array $rows) use ($tableName, $resolve, $reject)
            {
                $result = [];

                foreach ($rows as $data)
                {
                    $result = array_merge(json_decode($data["data"], true), $result);
                }

                if (isset($result[$tableName]))
                {
                    $resolve($result[$tableName]);
                }
                else
                {
                    $reject(false);
                }
            });
        });
    }

}