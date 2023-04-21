<?php

session_start(); //Start the session

require('connect.php');

//Function to clean up an user input for safety reasons (prevent SQL injection)
function SecurizeString_ForSQL($string) {
    $string = trim($string);
    $string = stripcslashes($string);
    $string = addslashes($string);
    $string = htmlspecialchars($string);
    return $string;
}

//Execute a query to our SQL database
function executeQuery($sql, $data) {
    global $connection;
    $query = $connection->prepare($sql); //Preparation of the query
    $values = array_values($data); //Fetching the conditions 
    $types = str_repeat('s', count($values)); //Creating value types (all str here because sql is smart and will replace it)
    //Binding condition values into the query (prevent SQL injection)
    $query->bind_param($types, ...$values); //...$values --> spread all values like : $admin, $username, ...
    $query->execute(); //Execution of the query
    return $query;
}

//For developpement purpose
function displayValue($value) {
    echo "<pre>", print_r($value, true), "</pre>";
}

// Select all all results fitting certain conditions or not 
function selectAll($table, $conditions = []) {
    global $connection;
    $sql = "SELECT * FROM $table"; //Base sql query
    if (empty($conditions)) {
        $query = $connection->prepare($sql); //Preparation of the query
        $query->execute(); //Execution of the query
        $results = $query->get_result()->fetch_all(MYSQL_ASSOC); // Fetching all the results of the query
        return $results;
    } else {
        $i = 0;
        //We add the values separatly from this part to help prevent SQL injection
        foreach ($conditions as $column => $value) {
            if ($i === 0) {
                $sql = $sql . " WHERE $column=? "; 
            } else {
                $sql = $sql . " AND $column=? ";
            }
            $i++;
        }
        $query = executeQuery($sql, $conditions);
        $results = $query->get_result()->fetch_all(MYSQL_ASSOC); // Fetching all the results of the query
        return $results;
    }
    
}

// Select a unique result fitting certain conditions
function selectOne($table, $conditions) {
    global $connection;
    $sql = "SELECT * FROM $table"; //Base sql query

    $i = 0;
    //We add the values separatly from this part to help prevent SQL injection
    foreach ($conditions as $column => $value) {
        if ($i === 0) {
            $sql = $sql . " WHERE $column=? "; 
        } else {
            $sql = $sql . " AND $column=? ";
        }
        $i++;
    }
    $sql = $sql . " LIMIT 1"; //Limit the sql search at 1 to prevent it searching further if the result is found

    $query = executeQuery($sql, $conditions);
    $results = $query->get_result()->fetch_assoc(); // Fetching the result of the query
    return $results;
    
}

//function that creates new rows in our database
function createRow($table, $data) {
    global $connection;
    $sql = "INSERT INTO $table SET";
    $i = 0;
    //We add the values separatly from this part to help prevent SQL injection
    foreach ($data as $column => $value) {
        if ($i === 0) {
            $sql = $sql . " $column=? "; 
        } else {
            $sql = $sql . ", $column=? ";
        }
        $i++;
    }
    $query = executeQuery($sql, $data);
    $id = $query->insert_id;
    return $id;
}

//function that updates rows in our database
function updateRow($table, $id, $data) {
    global $connection;
    $sql = "UPDATE $table SET";
    $i = 0;
    //We add the values separatly from this part to help prevent SQL injection
    foreach ($data as $column => $value) {
        if ($i === 0) {
            $sql = $sql . " $column=? "; 
        } else {
            $sql = $sql . ", $column=? ";
        }
        $i++;
    }
    $sql = $sql . "WHERE id=?";
    displayValue($sql);
    $data['id'] = $id; //Adding the id value to the id key
    $query = executeQuery($sql, $data);
    return $query->affected_rows;
}

//function that delete rows in our database
function deleteRow($table, $id) {
    global $connection;
    $sql = "DELETE FROM $table WHERE id=?";
    $query = executeQuery($sql, ['id' => $id]);
    return $query->affected_rows;
}

//function that fetches all the published posts with the username of its author
function getPublishedPosts() {
    global $connection;
    $sql = "SELECT p.*, u.username FROM posts AS p JOIN users AS u ON p.user_id = u.id WHERE p.published = ?";

    $query = executeQuery($sql, ['published' => 1]);
    $results = $query->get_result()->fetch_all(MYSQL_ASSOC); // Fetching all the results of the query
    return $results;
}

//function that fetches all the published posts that correspond with the terms entered by the user in the search bar
function searchPosts($search) {
    global $connection;
    $term = '%' . $search . '%';
    $sql = "SELECT p.*, u.username FROM posts AS p JOIN users AS u ON p.user_id = u.id 
    WHERE p.published = ? AND p.title LIKE ? OR p.body LIKE ?";

    $query = executeQuery($sql, ['published' => 1, 'title' => $term, 'body' => $term]);
    $results = $query->get_result()->fetch_all(MYSQL_ASSOC); // Fetching all the results of the query
    return $results;
}

//function that fetches all the posts related to a specific topic
function getPostsByTopic($topic) {
    global $connection;
    $sql = "SELECT p.*, u.username FROM posts AS p JOIN users AS u ON p.user_id = u.id 
    WHERE p.published = ? AND topic_id = ?";

    $query = executeQuery($sql, ['published' => 1, 'topic_id' => $topic]);
    $results = $query->get_result()->fetch_all(MYSQL_ASSOC); // Fetching all the results of the query
    return $results;
}