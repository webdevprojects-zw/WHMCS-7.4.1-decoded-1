<?php 
namespace WHMCS;


class ListTable
{
    private $pagination = true;
    private $columns = array(  );
    private $rows = array(  );
    private $output = array(  );
    private $showmassactionbtnstop = false;
    private $massactionurl = "";
    private $massactionbtns = "";
    private $sortableTableCount = 0;
    private $pageObj = NULL;

    public function __construct(Pagination $obj, $tableCount = 0)
    {
        $this->pageObj = $obj;
        $this->sortableTableCount = $tableCount;
    }

    public function getPageObj()
    {
        return $this->pageObj;
    }

    public function setPagination($boolean)
    {
        $this->pagination = $boolean;
    }

    public function isPaginated()
    {
        return ($this->pagination ? true : false);
    }

    public function setMassActionURL($url)
    {
        $this->massactionurl = $url;
        return true;
    }

    public function getMassActionURL()
    {
        $url = $this->massactionurl;
        if( !$url ) 
        {
            $url = $_SERVER["PHP_SELF"];
        }

        if( strpos($url, "?") ) 
        {
            $url .= "&";
        }
        else
        {
            $url .= "?";
        }

        $url .= "filter=1";
        return $url;
    }

    public function setMassActionBtns($btns)
    {
        $this->massactionbtns = $btns;
        return true;
    }

    public function getMassActionBtns()
    {
        return $this->massactionbtns;
    }

    public function setShowMassActionBtnsTop($boolean)
    {
        $this->showmassactionbtnstop = $boolean;
        return true;
    }

    public function getShowMassActionBtnsTop()
    {
        return ($this->showmassactionbtnstop ? true : false);
    }

    public function setColumns($array)
    {
        if( !is_array($array) ) 
        {
            return false;
        }

        $this->columns = $array;
        $orderbyvals = array(  );
        foreach( $array as $vals ) 
        {
            if( is_array($vals) && $vals[0] ) 
            {
                $orderbyvals[] = $vals[0];
            }

        }
        $this->getPageObj()->setValidOrderByValues($orderbyvals);
        return true;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function addRow($array)
    {
        if( !is_array($array) ) 
        {
            return false;
        }

        $this->rows[] = $array;
        return true;
    }

    public function getRows()
    {
        return $this->rows;
    }

    public function outputTableHeader()
    {
        global $aInt;
        $page = $this->getPageObj()->getPage();
        $pages = $this->getPageObj()->getTotalPages();
        $numResults = $this->getPageObj()->getNumResults();
        $content = "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "?filter=1\">\n<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\"><tr>\n<td width=\"50%\" align=\"left\">" . $numResults . " " . $aInt->lang("global", "recordsfound") . ", " . $aInt->lang("global", "page") . " " . $page . " " . $aInt->lang("global", "of") . " " . $pages . "</td>\n<td width=\"50%\" align=\"right\">" . $aInt->lang("global", "jumppage") . ": <select name=\"page\" onchange=\"submit()\">";
        for( $newpage = 1; $newpage <= $pages; $newpage++ ) 
        {
            $content .= "<option value=\"" . $newpage . "\"";
            if( $page == $newpage ) 
            {
                $content .= " selected";
            }

            $content .= ">" . $newpage . "</option>";
        }
        $content .= "</select> <input type=\"submit\" value=\"" . $aInt->lang("global", "go") . "\" class=\"btn btn-xs btn-default\" /></td>\n</tr></table>\n</form>\n";
        $this->addOutput($content);
    }

    public function outputTable()
    {
        global $aInt;
        $orderby = $this->getPageObj()->getOrderBy();
        $sortDirection = $this->getPageObj()->getSortDirection();
        $content = "";
        if( $this->getMassActionURL() ) 
        {
            $content .= "<form method=\"post\" action=\"" . $this->getMassActionURL() . "\">";
        }

        if( $this->getShowMassActionBtnsTop() ) 
        {
            $content .= "<div style=\"padding-bottom:2px;\">" . $aInt->lang("global", "withselected") . ": " . $this->getMassActionBtns() . "</div>";
        }

        $content .= "\n<div class=\"tablebg\">\n<table id=\"sortabletbl" . $this->sortableTableCount . "\" class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n<tr>";
        $columns = $this->getColumns();
        foreach( $columns as $column ) 
        {
            if( is_array($column) ) 
            {
                $sortableheader = true;
                list($columnid, $columnname) = $column;
                $width = (isset($column[2]) ? $column[2] : "");
                if( !$columnid ) 
                {
                    $sortableheader = false;
                }

            }
            else
            {
                $sortableheader = false;
                $columnid = $width = "";
                $columnname = $column;
            }

            if( !$columnname ) 
            {
                $content .= "<th width=\"20\"></th>";
            }
            else
            {
                if( $columnname == "checkall" ) 
                {
                    $aInt->internaljquerycode[] = "\n\$('#checkall" . $this->sortableTableCount . "').click(function (event) {\n    // Starting from the checkbox that got the event,\n    // Climb up the tree until you reach .datatable\n    // Then find all the input elements within just this table\n    // Then make their checked status match the master checkbox.\n    \$(event.target).parents('.datatable').find('input').prop('checked',this.checked);\n});";
                    $content .= "<th width=\"20\"><input type=\"checkbox\" id=\"checkall" . $this->sortableTableCount . "\"></th>";
                }
                else
                {
                    $width = ($width ? " width=\"" . $width . "\"" : "");
                    $content .= "<th" . $width . ">";
                    if( $sortableheader ) 
                    {
                        $content .= "<a href=\"" . $_SERVER["PHP_SELF"] . "?orderby=" . $columnid . "\">";
                    }

                    $content .= $columnname;
                    if( $sortableheader ) 
                    {
                        $content .= "</a>";
                        if( $orderby == $columnid ) 
                        {
                            $content .= " <img src=\"images/" . strtolower($sortDirection) . ".gif\" class=\"absmiddle\" />";
                        }

                    }

                    $content .= "</th>";
                }

            }

        }
        $content .= "</tr>\n";
        $totalcols = count($columns);
        $rows = $this->getRows();
        if( count($rows) ) 
        {
            foreach( $rows as $vals ) 
            {
                if( $vals[0] == "dividingline" ) 
                {
                    $content .= "<tr><td colspan=\"" . $totalcols . "\" style=\"background-color:#efefef;\"><div align=\"left\"><b>" . $vals[1] . "</b></div></td></tr>";
                }
                else
                {
                    $trAttributes = array(  );
                    if( is_array($vals[0]) ) 
                    {
                        if( isset($vals[0]["trAttributes"]) && is_array($vals[0]["trAttributes"]) ) 
                        {
                            $trAttributes = $vals[0]["trAttributes"];
                        }

                        $vals[0] = (isset($vals[0]["output"]) ? $vals[0]["output"] : "");
                    }

                    $content .= "<tr";
                    foreach( $trAttributes as $trKey => $trValue ) 
                    {
                        $content .= " " . $trKey . "=\"" . $trValue . "\"";
                    }
                    $content .= ">";
                    foreach( $vals as $val ) 
                    {
                        $content .= "<td>" . $val . "</td>";
                    }
                    $content .= "</tr>";
                }

            }
        }
        else
        {
            $content .= "<tr><td colspan=\"" . $totalcols . "\">" . $aInt->lang("global", "norecordsfound") . "</td></tr>";
        }

        $content .= "</table>\n</div>";
        if( $this->getMassActionBtns() ) 
        {
            $content .= "" . $aInt->lang("global", "withselected") . ": " . $this->getMassActionBtns() . "\n</form>\n";
        }

        $this->addOutput($content);
    }

    public function outputTablePagination()
    {
        global $aInt;
        $prevPage = $this->getPageObj()->getPrevPage();
        $nextPage = $this->getPageObj()->getNextPage();
        $content = "<ul class=\"pager\">";
        if( $prevPage ) 
        {
            $content .= "<li class=\"previous\"><a href=\"" . $_SERVER["PHP_SELF"] . "?page=" . $prevPage . "&filter=1\">";
        }
        else
        {
            $content .= "<li class=\"previous disabled\"><a href=\"#\">";
        }

        $content .= "&laquo; " . $aInt->lang("global", "previouspage");
        $content .= "</a></li>";
        if( $nextPage ) 
        {
            $content .= "<li class=\"next\"><a href=\"" . $_SERVER["PHP_SELF"] . "?page=" . $nextPage . "&filter=1\">";
        }
        else
        {
            $content .= "<li class=\"next disabled\"><a href=\"#\">";
        }

        $content .= $aInt->lang("global", "nextpage") . " &raquo;";
        $content .= "</a></li>";
        $content .= "</ul>";
        $this->addOutput($content);
    }

    public function addOutput($content)
    {
        $this->output[] = $content;
    }

    public function output()
    {
        if( $this->isPaginated() ) 
        {
            $this->outputTableHeader();
        }

        $this->outputTable();
        if( $this->isPaginated() ) 
        {
            $this->outputTablePagination();
        }

        return implode("\n", $this->output);
    }

}


