<?php
// SetUp Databases
namespace Balise\Bridge;

register_activation_hook( dirname(__DIR__).'/config.php', function () {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $selfData = get_plugin_data(dirname(__DIR__).'/config.php');
    $table_prefix = $wpdb->prefix.$selfData["TextDomain"]."_";
    $files=glob(dirname(__DIR__)."/app/databases/*.sql");
    foreach ($files as $file) {
        $a = file_get_contents($file);
        preg_match_all('/CREATE\s+TABLE\s+(\w+)/mi',$a,$matches);
        if ($matches[1]) {
            foreach ($matches[1] as $table) {
                $sql = "CREATE TABLE ".$table_prefix.$table." (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id)
                );";
                dbDelta($sql);
            }
        }
        $a = preg_replace('/CREATE\s+(TEMPORARY\s+)?TABLE\s+(IF\s+NOT\s+EXISTS\s+)?/mi','CREATE TABLE ',$a);
        $a = preg_replace('/(CREATE\s+TABLE\s+)(\w+)/mi','$1'.$table_prefix.'$2',$a);
        dbDelta($a);
    }
});
class Database {

}
