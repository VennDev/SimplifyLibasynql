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
use poggit\libasynql\DataConnector;
use vennv\Promise;
use Throwable;

final class SimplifyLibasynql
{

    private const MAX_LENGTH_TYPE_LONG_TEXT = 4294967295;

    private DataConnector $database;

    public function register(DataConnector $database) : SimplifyLibasynql
    {
        $this->database = $database;

        return $this;
    }

    public function getDatabase() : DataConnector
    {
        return $this->database;
    }

    public function addTable(string $name, array $value) : void
    {
        $this->database->executeSelect("get_tables", [], function(array $rows) use ($name, $value)
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

            /**
             * @var string $case
             * @var string $data
             */
            $rows = array_column($rows, "name");

            if (!in_array($name, $rows))
            {
                $this->database->executeInsert(
                    "add_table", [
                        "name" => $name,
                        "data" => json_encode($value)
                    ]
                );

                self::updateDataHandler([
                    $name => []
                ]);
            }
            else
            {
                throw new Exception("Table already exists!");
            }
        });
    }

    public function updateTable(string $name, array $value) : void
    {
        $this->database->executeSelect("get_tables", [], function(array $rows) use ($name, $value)
        {
            $rows = array_column($rows, "name");

            if (in_array($name, $rows))
            {
                $this->database->executeChange(
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

    public function updateDataHandler(array $handler) : void
    {
        $this->database->executeSelect("get_data_handler", [], function(array $rows) use ($handler)
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
                    $this->database->executeInsert(
                        "add_data_handler", [
                            "id" => $keyEnd + 1,
                            "data" => $encodeData
                        ]
                    );
                }
                else
                {
                    $this->database->executeChange(
                        "update_data_handler", [
                            "id" => $keyEnd,
                            "data" => $dataAfterAdd
                        ]
                    );
                }
            }
            else
            {
                $this->database->executeInsert(
                    "add_data_handler", [
                        "id" => 0,
                        "data" => json_encode($handler)
                    ]
                );
            }
        });
    }

    public function update(string $tableName, array $data) : void
    {
        $this->database->executeSelect("get_tables", [], function(array $rows) use ($tableName, $data)
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

    public function removeDataTable(string $tableName, string $primaryKey) : void
    {
        $this->database->executeSelect("get_data_handler", [], function(array $rows) use ($tableName, $primaryKey)
        {
            foreach ($rows as $key => $data)
            {
                $dataHandler = json_decode($data["data"], true);

                if (isset($dataHandler[$tableName][$primaryKey]))
                {
                    unset($dataHandler[$tableName][$primaryKey]);

                    $this->database->executeChange(
                        "update_data_handler", [
                            "id" => $key,
                            "data" => json_encode($dataHandler)
                        ]
                    );
                }
            }
        });
    }

    public function removeTable(string $tableName) : void
    {
        $this->database->executeSelect("get_data_handler", [], function(array $rows) use ($tableName)
        {
            foreach ($rows as $key => $data)
            {
                $dataHandler = json_decode($data["data"], true);

                if (isset($dataHandler[$tableName]))
                {
                    unset($dataHandler[$tableName]);

                    $this->database->executeChange(
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
    public function fetchData(string $tableName, string $primaryKey) : Promise
    {
        return new Promise(function($resolve, $reject) use ($tableName, $primaryKey)
        {
            $this->database->executeSelect("get_data_handler", [], function(array $rows) use ($tableName, $primaryKey, $resolve, $reject)
            {
                $result = [];

                foreach ($rows as $data)
                {
                    $result = array_merge(json_decode($data["data"], true), $result);
                }

                if (isset($result[$tableName][$primaryKey]))
                {
                    $resolve($result[$tableName][$primaryKey]);
                }
                else
                {
                    $reject(false);
                }
            });
        });
    }

    /**
     * @throws Throwable
     */
    public function fetchAll(string $tableName) : Promise
    {
        return new Promise(function($resolve, $reject) use ($tableName)
        {
            $this->database->executeSelect("get_data_handler", [], function(array $rows) use ($tableName, $resolve, $reject)
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