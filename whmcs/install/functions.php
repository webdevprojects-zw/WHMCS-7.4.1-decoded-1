<?php 
function mysql_import_file($filename, $basedir = NULL)
{
    if( !$basedir ) 
    {
        $basedir = dirname(__FILE__) . "/sql/";
    }

    $querycount = 0;
    $queryerrors = "";
    if( file_exists($basedir . $filename) ) 
    {
        $lines = file($basedir . $filename);
        if( !$lines ) 
        {
            $errmsg = "cannot open file " . $filename;
            return false;
        }

        $scriptfile = false;
        foreach( $lines as $line ) 
        {
            $line = trim($line);
            if( substr($line, 0, 2) != "--" ) 
            {
                $scriptfile .= " " . $line;
            }

        }
        $queries = explode(";", $scriptfile);
        foreach( $queries as $query ) 
        {
            $query = trim($query);
            $querycount++;
            if( $query == "" ) 
            {
                continue;
            }

            if( !mysql_query($query) ) 
            {
                $queryerrors .= "Line " . $querycount . " - " . mysql_error() . "<br>";
            }

        }
        if( $queryerrors ) 
        {
            echo "<b>Errors Occurred</b><br><br>Please open a ticket with the debug information below for support<br><br>File: " . $filename . "<br>" . $queryerrors;
        }

        return true;
    }
    else
    {
        $errmsg = "cannot open file " . $filename;
        return false;
    }

}


