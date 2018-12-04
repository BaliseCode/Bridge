<?php

namespace Balise\Bridge;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model as Model;

include_once(ABSPATH.'wp-admin/includes/plugin.php');

$capsule = new Capsule;
$selfData = get_plugin_data(dirname(__DIR__).'/config.php');

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => DB_HOST,
    'database'  => DB_NAME,
    'username'  => DB_USER,
    'password'  => DB_PASSWORD,
    'charset'   => DB_CHARSET,
    'collation' => ((DB_COLLATE) ? DB_COLLATE : NULL),
    'prefix'    => $wpdb->prefix.$selfData["TextDomain"]."_"
]);

// Make this Capsule instance available globally via static methods
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

//Load all models
$files=glob(__DIR__."/app/models/*.php");
foreach ($files as $file) {
    require_once($file);
}
do_action( 'activate_plugin', dirname(__DIR__).'/config.php' );
