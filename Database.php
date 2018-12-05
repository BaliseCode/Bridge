<?php
// SetUp Databases
namespace Balise\Bridge;

class Database {
    protected $table_prefix = "";
    function __construct() {
        global $wpdb;
        $table_prefix = $this->getPrefix();
        $this->table_prefix = $wpdb->prefix.$table_prefix.'_';
    }
    protected function getPrefix() {
        $backtrace =  debug_backtrace();
        if (str_replace(WP_PLUGIN_DIR, '', $backtrace[0]['file']) !== $backtrace[0]['file']) {
            // If in a plugin
            $table_prefix = explode('/',substr(str_replace(WP_PLUGIN_DIR, '', $backtrace[0]['file']),1));
            $table_prefix = $table_prefix[0];
        } else {
            // If in the theme
            $table_prefix = "_theme";
        }
        return $table_prefix;
    }
    public function build($file) {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $content = file_get_contents($file);
        $content = preg_replace('/CREATE\s+(TEMPORARY\s+)?TABLE\s+(IF\s+NOT\s+EXISTS\s+)?/mi','CREATE TABLE ',$content);
        preg_match_all('/CREATE\s+TABLE\s+(\w+)/mi',$content,$matches);
        if ($matches[1]) {
            foreach ($matches[1] as $table) {
                $sql = "CREATE TABLE ".$this->table_prefix.$table." (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id)
                );";
                dbDelta($sql);
            }
        }
        $content = preg_replace('/(CREATE\s+TABLE\s+)(\w+)/mi','$1'.$this->table_prefix.'$2',$content);
        dbDelta($content);
    }
    public function create($file) {
        $backtrace = debug_backtrace();
        $object = $this;
        register_activation_hook( $backtrace[0]['file'], function () use ($file, $object) {
            $object->build($file);
        });
        if (WP_DEBUG) {
            $this->build($file);
        }
    }
}
