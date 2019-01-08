<?php
// Setup BackRoute
namespace Balise\Bridge;

class BackRoute extends Route {
    public static $routes = array();
    static function start() {
        add_action('init', 'flush_rewrite_rules' );
        add_action('init', array('\Balise\Bridge\BackRoute', 'onInit'));
        add_action('parse_query', array('\Balise\Bridge\BackRoute', 'bypassQuery'));
    }
    static function onInit() {
        global $wp_query;
        add_rewrite_tag( '%backendroute%', '(.*)' );
        $adminUrl = parse_url(admin_url());
        $wpadmin = substr($adminUrl['path'],1);
        foreach (self::$routes as $route) {
            $resolver = preg_replace('/{\w+}/i','([^\/]+)',$route[1]);
            add_rewrite_rule('^'.$wpadmin.$resolver.'$', 'index.php?backendroute=1', 'top');
        }
    }
    static function addMenu($name, $route, $permission = 'read', $icon='dashicons-yes') {
    	add_action( 'admin_menu', function () {
	    	add_menu_page('Membres','Membres','read', 'membres','',$icon);
    	});
    }
    static function bypassQuery() {
        global $_wp_submenu_nopriv, $wp_db_version, $menu;
        if (get_query_var("backendroute", false)) {
            $_wp_submenu_nopriv = array();
            $menu = array();
            $wp_db_version = get_option('db_version');
            add_submenu_page(null, 'wpbackendroutingtest', 'test', 'read', 'wpbackendroutingtest', function(){
                $adminUrl = parse_url(admin_url());
                $wpadmin = substr($adminUrl['path'],1);
                $resolved = self::routesResolver(self::$routes, $wpadmin);
                echo '<div class="wrap">'.call_user_func_array ($resolved[2], $resolved[4]).'</div>';
            });
            $_GET['post_type'] = 'post';
            $_GET['page'] = 'wpbackendroutingtest';
            add_action( 'admin_head', function() {
                echo '<base href="'.admin_url().'">';
            } );

            require_once(ABSPATH."/wp-admin/index.php");
            die();

        }
    }
}

BackRoute::start();
