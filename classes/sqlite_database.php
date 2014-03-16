<?php
/**
 * Created by PhpStorm.
 * User: sebastian
 * Date: 16.03.14
 * Time: 13:48
 */

class sqlite_database
{
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