
#PHP
~~~~
namespace SomeNameSpace;

require "vendor/autoload.php";
use Balise\Bridge\Model;
use Balise\Bridge\Controller;
use Balise\Bridge\Database;
use Balise\Bridge\Router;

// Load all tables
$DB = new DataBase(__NAMESPACE__);
$files=glob(__DIR__."/app/databases/*.sql");
foreach ($files as $file) {
    $DB->create($file);
}

//Load all models
$files=glob(__DIR__."/app/models/*.php");
foreach ($files as $file) {
    require_once($file);
}

//Load all routes
$files=glob(__DIR__."/app/routes/*.php");
foreach ($files as $file) {
    require_once($file);
}
~~~~
