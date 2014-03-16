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
class mysql_database {
    private $handle;
    private $dbname;
    function __construct($host,$user,$pass,$dbname)
    {
        $this->handle=mysqli_connect($host,$user,$pass);
        mysqli_query($this->handle,"SET NAMES UTF8");
        $this->dbname=$dbname;
    }
    function create()
    {
        return mysqli_query($this->handle,"CREATE DATABASE ".$this->dbname." DEFAULT CHARACTER SET UTF8");
    }
    function drop()
    {
        return mysqli_query($this->handle,"DROP DATABASE IF EXISTS ".$this->dbname);
    }
    function select()
    {
        return mysqli_select_db($this->handle,$this->dbname);
    }
    function execute($query)
    {
        return mysqli_query($this->handle,$query);
    }
    function lastError()
    {
        return mysqli_error($this->handle);
    }
    function escape($what)
    {
        return mysqli_real_escape_string($this->handle,$what);
    }

}
class sqlite_database{
    private $handle;
    public  $result;
    private $storedResults=array();
    function __construct($filename)
    {
        $this->handle=new SQLite3($filename);
    }
    function execute($query)
    {
        return $this->result=$this->handle->query($query);
    }
    function fetch_assoc()
    {
        return $this->result->fetchArray(SQLITE3_ASSOC);
    }
    function storeResult()
    {
        array_push($this->storedResults,$this->result);
    }
    function restoreResult()
    {
        $this->result=array_pop($this->storedResults);
    }
    function describe($tablename)
    {
        $query="PRAGMA table_info(".$tablename.")";
        $this->storeResult();
        $this->execute($query);
        $rows=array();
        while($row=$this->fetch_assoc())
        {
            array_push($rows,$row);
        }
        $this->restoreResult();
        return $rows;
    }
}
class mysql_table{
    private $primaryKeys=array();
    private $autoIncrement;
    private $tablename;
    private $data;
    function __construct($tablename,$options)
    {
        $this->tablename=$tablename;
        $this->data=$options;
        foreach($this->data as $key => $row)
        {
            if(strstr($row['type'],"CLOB")!==FALSE)
            {
                $this->data[$key]['type']=str_replace("CLOB","TEXT",$row['type']);
            }
            foreach($row as $colname => $contents)
            {
                if($colname=="pk" && $contents==1)
                {
                    array_push($this->primaryKeys,$row['name']);
                }
                if(strstr($row['name'],"_id")!==FALSE && empty($this->autoIncrement))
                {
                    $this->autoIncrement=$row['name'];
                }
            }
        }
        if(!empty($this->autoIncrement) && empty($this->primaryKeys))
        {
            foreach($this->data as $row)
            {
                if(strstr($row['name'],"_id")!==FALSE)
                {
                    array_push($this->primaryKeys,$row['name']);
                    break;
                }
            }
        }
    }
    function generate()
    {
        $table= "CREATE TABLE IF NOT EXISTS `".$this->tablename."` (";
        foreach($this->data as $row)
        {
            $table .= " `".$row['name']."` ".$row['type']." ";
            if($row['notnull']==1)
            {
                $table.=" NOT NULL ";
            }
            else
            {
                $table.=" NULL ";
            }
            if(empty($row['dflt_value']))
            {
                if(!empty($this->autoIncrement) && $this->autoIncrement==$row['name'])
                {
                    $table.=" ";
                }
                else
                {
                    if(strstr($row['type'],"INT")!==FALSE)
                    {
                        $table.=" DEFAULT 0 ";
                    }
                    else
                    {
                        $table.=" DEFAULT '' ";
                    }
                }
            }
            else
            {
                $table.=" DEFAULT ".$row['dflt_value']." ";
            }
            if(!empty($this->autoIncrement) && $this->autoIncrement==$row['name'] && strstr($row['type'],"INT")!==FALSE)
            {
                $table.=" AUTO_INCREMENT ";
            }
            $table.=", ";
        }
        $table=substr($table,0,-2);
        if(!empty($this->primaryKeys))
        {
            $table.=", PRIMARY KEY (";
            $table.=implode(", ",$this->primaryKeys);
            $table.=")";
        }
        $table.=")";
        return $table;
    }
}
class table
{
    private $name;
    private $rows=array();
    private $db1;
    private $db2;
    function __construct($name, sqlite_database $db1=null, mysql_database $db2=null)
    {
        //expecting db1 to be sqlite_database
        //expecting db2 to be mysql_database
        $this->name=$name;
        $this->db1=$db1;
        $this->db2=$db2;
    }
    function migrateToMySQL()
    {
        $query="SELECT * FROM ".$this->name;
        $result=$this->db1->execute($query);
        $rowCounter=0;
        while($row=$this->db1->result->fetchArray(SQLITE3_ASSOC))
        {
            $rowCounter++;
            $query="INSERT INTO `".$this->name."` (";
            $cols=array();
            $contents=array();
            foreach($row as $col => $content)
            {
                array_push($cols,"`".$col."`");
                if(is_integer($content))
                {
                }
                else
                {
                    $content="'".$this->db2->escape($content)."'";
                }
                array_push($contents,$content);
            }
            $query.=implode(",",$cols).") VALUES (";
            $query.=implode(",",$contents).");";
            if(!$this->db2->execute($query))
            {
                die("error executing last query: ".$query.nl."mysql returned: ".$this->db2->lastError());
            }
        }
        return $rowCounter;
    }
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
                    $table=new table($tablename,$sqlite,$mysql);
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