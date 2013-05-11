<?php
namespace SimpleORM;


class Base extends SimpleORM
{
    protected function provide_dbh()
    {
        $global_conf_file = getcwd().'/config/autoload/global.php';
        $local_conf_file = getcwd().'/config/autoload/local.php';
        $global = include $global_conf_file;
        $local = include $local_conf_file;
        $dsn = $global['db']['dsn'];
        $user = $local['db']['username'];
        $pass = $local['db']['password'];
        $db = new \PDO($dsn,$user,$pass);
        return $db; 
    }
    protected function provide_dbh_type()
    {
        return 'mysql'; 
    } 
    protected function include_prefix()
    {
        return APPLICATION_PATH .'/models/'; 
    }



      public static function get_where($where = null, $limit_or_only_one = false, $order_by = null) {
        //$db = Globals::getDb();

        $global_conf_file = getcwd().'/config/autoload/global.php';
        $local_conf_file = getcwd().'/config/autoload/local.php';
        $global = include $global_conf_file;
        $local = include $local_conf_file;
        $dsn = $global['db']['dsn'];
        $user = $local['db']['username'];
        $pass = $local['db']['password'];
        $db = new \PDO($dsn,$user,$pass);


        ///  Because we are STATIC, and most everything we need is NON-STATIC
        ///    we first need a backtrace lead to tell us WHICH object is even
        ///    our parent, and then we can create an empty parent Non-Static
        ///    object to get the few params we need...
        $bt = debug_backtrace();
        if ( $bt[1]['function'] != 'get_where' && $bt[1]['function'] != 'get_where_cached' ) {
            trigger_error("Use of get_where() when not set up!  The hack for whetever object you are calling is not set up!<br/>\n
                           You need to add a get_where() stub to your object (the one you are referring to in ". $bt[0]['file'] ." on line ". $bt[0]['line'] ."), that looks like:<br/>\n".'
                           public static function get_where($where = null, $limit_or_only_one = false, $order_by = null) { return parent::get_where($where, $limit_or_only_one, $order_by);'."<br/>\n".'
                           public static function get_where_cached($where = null, $limit_or_only_one = false, $order_by = null) { return parent::get_where($where, $limit_or_only_one, $order_by); }
                           ' , E_USER_ERROR);
        }
        $parent_class = $bt[1]['class'];

        ///  Otherwise, just get the parent object and continue
        $tmp_obj = new $parent_class ();


        ///  Assemble a generic SQL based on the table of this object
        $values = array();

        if( $where ) {
            $where_ary = array();
            foreach ($where as $col => $val) {
                ///  If the where condition is just a string (not an assocative COL = VALUE), then just add it..
                if ( is_int($col) ) { $where_ary[] = $val; }
                ///  Otherwise, basic ( assocative COL = VALUE )
                else { $where_ary[] = "$col = ?";  $values[] = $val; }
            }
        }
        $sql = "SELECT *
                  FROM ". $tmp_obj->get_table() ."
                 WHERE ". ( isset($where_ary) ? join(' AND ', $where_ary) : '1' ) ."
                 ". ( ! is_null($order_by) ? ( "ORDER BY ". $order_by ) : '' ) ."
              ". ( ( $limit_or_only_one !== true && $limit_or_only_one ) ? ( "LIMIT " . $limit_or_only_one ) : '' ) ."
                ";

        $data = $db->createStatement($sql,$values)->execute();
        ///  Get the objs
        $objs = array();
        foreach ( $data as $row ) {
            $pk_values = array(); foreach( $tmp_obj->get_primary_key() as $pkey_col ) $pk_values[] = $row[ $pkey_col ];
            $objs[] = new $parent_class ( $pk_values, $row );
        }

        ///  If they only ask asking for one object, just guve them that, not the array
        return ( ($limit_or_only_one === true || $limit_or_only_one === 1) ? ( empty( $objs ) ? null :  $objs[0] ) : $objs );
    }




}

