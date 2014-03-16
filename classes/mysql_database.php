<?php
/**
 * Created by PhpStorm.
 * User: sebastian
 * Date: 16.03.14
 * Time: 13:48
 */

class mysql_database
{
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