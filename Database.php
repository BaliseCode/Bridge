<?php
// SetUp Databases
namespace Balise\Bridge;

class Database {
    public function create($file) {
        register_activation_hook( dirname(__DIR__).'/config.php', function () use ($file) {
            global $wpdb;
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $content = file_get_contents($file);

            $content = preg_replace('/CREATE\s+(TEMPORARY\s+)?TABLE\s+(IF\s+NOT\s+EXISTS\s+)?/mi','CREATE TABLE ',$content);
            preg_match_all('/CREATE\s+TABLE\s+(\w+)/mi',$content,$matches);
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
            $content = preg_replace('/(CREATE\s+TABLE\s+)(\w+)/mi','$1'.$table_prefix.'$2',$content);
            dbDelta($a);
        });
    }
}
