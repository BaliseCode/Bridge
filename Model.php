<?php
// Setup Models
namespace Balise\Bridge;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Str;

class Model extends \Illuminate\Database\Eloquent\Model {
    public function getTable()
    {

        $backtrace =  debug_backtrace();
        if (str_replace(WP_PLUGIN_DIR, '', $backtrace[0]['file']) !== $backtrace[0]['file']) {
            // If in a plugin
            $table_prefix = explode('/',substr(str_replace(WP_PLUGIN_DIR, '', $backtrace[0]['file']),1));
            $table_prefix = $table_prefix[0];
        } else {
            // If in the theme
            $table_prefix = "_theme";
        }



        if (! isset($this->table)) {
            return $table_prefix."_".str_replace(
                '\\', '', Str::snake(Str::plural(class_basename($this)))
            );
        }
        return $table_prefix."_".$this->table;
    }

    static function _registerModels() {
        global $wpdb;
        include_once(ABSPATH.'wp-admin/includes/plugin.php');

        $capsule = new Capsule;


        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => DB_HOST,
            'database'  => DB_NAME,
            'username'  => DB_USER,
            'password'  => DB_PASSWORD,
            'charset'   => DB_CHARSET,
            'collation' => ((DB_COLLATE) ? DB_COLLATE : NULL),
            'prefix'    => $wpdb->prefix
        ]);

        // Make this Capsule instance available globally via static methods
        $capsule->setAsGlobal();

        // Setup the Eloquent ORM
        $capsule->bootEloquent();
    }
}
Model::_registerModels();
