<?php
// Setup BackRoute
namespace Balise\Bridge;

class BackRoute extends Route {
    public static $routes = array();
    static function start() {
        if ($_GET['page'] === 'wpbackendroutingtest') {
            add_filter('removable_query_args', function () {
                return false;
            });
        }
        add_action('init', 'flush_rewrite_rules');
        add_action('init', array('\Balise\Bridge\BackRoute', 'onInit'));
        add_action('parse_query', array('\Balise\Bridge\BackRoute', 'bypassQuery'));

    }
    static function onInit() {
        global $wp_query;
        add_action('admin_head', function () {
            echo '<base href="' . admin_url() . '">';
        });
        add_action('admin_menu', function () {
            add_submenu_page(null, 'wpbackendroutingtest', 'test', 'read', 'wpbackendroutingtest', function () {
                $adminUrl = parse_url(admin_url());
                $wpadmin  = substr($adminUrl['path'], 1);
                 $_SERVER['REQUEST_URI'] = $_GET['url'];
                $resolved = self::routesResolver(self::$routes, $wpadmin);
                echo '<div class="wrap">' . call_user_func_array($resolved[2], $resolved[4]) . '</div>';
            });
        });

    }
    static function triggerRoutes() {
        $adminUrl = parse_url(admin_url());
        $wpadmin  = substr($adminUrl['path'], 1);
        foreach (self::$routes as $route) {
            $resolver = preg_replace('/{\w+}/i', '([^\/]+)', $route[1]);
            if (preg_match('/^\/' . str_replace('/', '\/', $wpadmin . $resolver) . '\/?$/', $_SERVER['REQUEST_URI'])) {
                $opts    = array('http' => array('header' => 'Cookie: ' . $_SERVER['HTTP_COOKIE'] . "\r\n"));
                $context = stream_context_create($opts);
                echo file_get_contents(admin_url('admin.php?page=wpbackendroutingtest&url=' . urlencode($_SERVER['REQUEST_URI'])), null, $context);

                die();
            }
        }
    }
    static function addMenu($name, $route, $permission = 'read', $icon = 'dashicons-yes') {
        add_action('admin_menu', function () use ($icon, $name, $permission, $route) {
            add_menu_page($name, $name, $permission, $route, '', $icon);
        });
    }
    static function bypassQuery() {
        global $_wp_submenu_nopriv, $wp_db_version, $menu;
        if (get_query_var("backendroute", false)) {
            $_wp_submenu_nopriv = array();
            $menu               = array();
            $wp_db_version      = get_option('db_version');

            $_GET['post_type'] = 'post';
            $_GET['page']      = 'wpbackendroutingtest';

            require_once ABSPATH . "/wp-admin/index.php";
            die();

        }
    }
}
BackRoute::start();
