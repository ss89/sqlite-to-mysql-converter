<?php
/**
 * Created by PhpStorm.
 * User: sebastian
 * Date: 16.03.14
 * Time: 13:49
 */

class mysql_table
{
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