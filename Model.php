<?php
// Setup Models
namespace Balise\Bridge;

use Illuminate\Database\Capsule\Manager as Capsule;

class Model extends \Illuminate\Database\Eloquent\Model {
    static function _registerModels() {
        include_once(ABSPATH.'wp-admin/includes/plugin.php');

        $prefix = explode('/',plugin_basename( __FILE__ ));
        $prefix = $prefix[0];

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => DB_HOST,
            'database'  => DB_NAME,
            'username'  => DB_USER,
            'password'  => DB_PASSWORD,
            'charset'   => DB_CHARSET,
            'collation' => ((DB_COLLATE) ? DB_COLLATE : NULL),
            'prefix'    => $wpdb->prefix.$prefix."_"
        ]);

        // Make this Capsule instance available globally via static methods
        $capsule->setAsGlobal();

        // Setup the Eloquent ORM
        $capsule->bootEloquent();
    }
}
Model::_registerModels();
