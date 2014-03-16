<?php
/**
 * Created by PhpStorm.
 * User: strussi
 * Date: 10.03.14
 * Time: 20:52
 */
$filename="/Users/sebastian/Desktop/owncloud.db";
$host="127.0.0.1";
$user="root";
$pass="mysql";
$dbname="owncloud";
$sqls=array();
if(stristr(PHP_SAPI,"CLI")!==FALSE)
{
    define("nl",chr(13).chr(10));
}
else
{
    define("nl","<br>");
}
$required=array("mysql_database","mysql_table","sqlite_database","sqlite_table");
foreach($required as $require)
{
    if(!file_exists("classes/".$require.".php"))
    {
        die($require.".php was not found in classes folder".nl);
    }
    require_once 'classes/'.$require.".php";
}
header("Content-Type: text/html; charset=UTF-8");
$sqlite=new sqlite_database($filename);
if($sqlite)
{
    echo "connected to sqlite db".nl;
    $query="SELECT * FROM sqlite_master WHERE type='table'";
    $result=$sqlite->execute($query);
    $tables=array();
    while($row=$sqlite->result->fetchArray(SQLITE3_ASSOC))
    {
        //echo "<pre>".print_r($row,true)."</pre>";
        array_push($sqls,$row['sql']);
        if($row['tbl_name']!="sqlite_sequence")
        {
            array_push($tables,$row['tbl_name']);
        }
    }
    $mysql=new mysql_database($host,$user,$pass,$dbname);
    if($mysql)
    {
        echo "connected to mysql".nl;
        $drop=$mysql->drop();
        if($drop)
        {
            echo "dropped mysql database".nl;
        }
        else
        {
            die ("failed to drop database: ".$mysql->lastError());
        }
        $create=$mysql->create();
        if($create)
        {
            echo "created database".nl;
        }
        else
        {
            die ("failed to create database: ".$mysql->lastError());
        }
        $select=$mysql->select();
        if($select)
        {
            echo "target database selected".nl;
            foreach($tables as $tablename)
            {
                $description=$sqlite->describe($tablename);
                $table=new mysql_table($tablename,$description);
                $createTable=$table->generate();
                $create=$mysql->execute($createTable);
                if($create)
                {
                    echo "table ".$tablename." created".nl;
                    $table=new sqlite_table($tablename,$sqlite,$mysql);
                    $migrated=$table->migrateToMySQL();
                    if($migrated || is_numeric($migrated))
                    {
                        echo "table ".$tablename." migrated (".$migrated." Rows)".nl;
                    }
                }
                else
                {
                    die ("error executing: ".$mysql->lastError());
                }
            }
        }
        else
        {
            die ("failed to select db: ".$mysql->lastError());
        }
    }
    else
    {
        die ("failed to connect to mysql db: ".$mysql->lastError());
    }
}
else
{
    die ("failed to connect sqlite db");
}