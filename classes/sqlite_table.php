<?php
/**
 * Created by PhpStorm.
 * User: sebastian
 * Date: 16.03.14
 * Time: 13:50
 */

class sqlite_table {
    private $name;
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
        if(!$result=$this->db1->execute($query))
        {
            die("error selecting rows from '".$this->name."'".nl);
        }
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