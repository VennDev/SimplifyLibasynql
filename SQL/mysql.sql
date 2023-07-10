-- #!mysql
-- #{ init_tables
CREATE TABLE IF NOT EXISTS tables (
     name VARCHAR(100) PRIMARY KEY,
     data LONGTEXT NOT NULL
);
-- #}

-- #{ init_data_handler
CREATE TABLE IF NOT EXISTS data_handler (
     id INT(255) UNSIGNED PRIMARY KEY,
     data LONGTEXT NOT NULL
);
-- #}

-- #{ get_tables
SELECT * FROM tables;
-- #}

-- #{ get_data_handler
SELECT * FROM data_handler;
-- #}

-- #{ add_table
-- #	:name string
-- #	:data string
INSERT INTO tables (name, data)
VALUES (:name, :data);
-- #}

-- #{ update_table
-- #	:name string
-- #	:data string
UPDATE tables SET data = :data
WHERE name = :name;
-- #}

-- #{ add_data_handler
-- #	:id int
-- #	:data string
INSERT INTO data_handler (id, data)
VALUES (:id, :data);
-- #}

-- #{ update_data_handler
-- #	:id int
-- #	:data string
UPDATE data_handler SET data = :data
WHERE id = :id;
-- #}