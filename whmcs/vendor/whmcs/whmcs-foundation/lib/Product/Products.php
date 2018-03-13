<?php 
namespace WHMCS\Product;


class Products
{
    public function getProducts($groupId = NULL)
    {
        $where = array(  );
        if( $groupId ) 
        {
            $where["tblproducts.gid"] = (int) $groupId;
        }

        $products = array(  );
        $result = select_query("tblproducts", "tblproducts.id,tblproducts.gid,tblproducts.retired,tblproducts.name,tblproductgroups.name AS groupname", $where, "tblproductgroups`.`order` ASC, `tblproducts`.`order` ASC, `name", "ASC", "", "tblproductgroups ON tblproducts.gid=tblproductgroups.id");
        while( $data = mysql_fetch_assoc($result) ) 
        {
            $products[] = $data;
        }
        return $products;
    }

}


