<?php

namespace Balise\Bridge;

class Route {
    static function get($route, $callback,$template = null) {
        static::$routes[] = array('GET',$route,$callback,$template);
    }
    static function post($route, $callback,$template = null) {
        static::$routes[] = array('POST',$route,$callback,$template);
    }

    /* Make sure the function is callable */
    public static function convertToCallable($callable) {
        if (!is_callable($callable)) {
            /* If the format Class@method is used */
            /* convert it to callable */
            if (is_string($callable)) {
                $parts = explode('@',$callable);
                if (class_exists($parts[0])) {
                    $object = new $parts[0]();
                    $callable = array($object, $parts[1]);
                }
            }

        }
        // If the callable function is still not callable, return empty function
        if (!is_callable($callable)) {
            $callable = function() {
                return '';
            };
        }
        return $callable;
    }
    /* This is where the magic happens (routes get selected) */
    public static function routesResolver($routes,$prefix="") {
        $selected = null;
        foreach($routes as $route) {
            if ($_SERVER['REQUEST_METHOD'] === $route[0]) {

                // Store the keys used
                preg_match_all('/{(\w+)}/i',$route[1],$keys);

                // Make the attibutes value as wildcard
                $resolver = preg_replace('/{\w+}/i','([^\/]+)',$route[1]);

                // If the route match
                if (preg_match('#^/'.$prefix.$resolver.'/?$#i', $_SERVER['REQUEST_URI'], $data)) {
                    $selected = $route;

                    // Make sure the attribute 2 is a callback
                    $selected[2] = self::convertToCallable($selected[2]);

                    // Match the value to the keys and add it to the selected route
                    $values = array();
                    foreach($keys[1] as $key=>$id) {
                        $values[$id] = $data[$key+1];
                    }
                    $selected[] = $values;

                    // Return selected route with data
                    return $selected;
                    break;
                }
            }
        }
        return $_SERVER['REQUEST_URI'];
    }
}

class FrontRoute extends WPRoute {
    public static $routes = array();
    static function start() {
        add_action('init', 'flush_rewrite_rules' );
        add_action('init', array('WPFrontRoute', 'onInit'));
        add_action('parse_query', array('WPFrontRoute', 'bypassQuery'));
    }
    static function onInit() {

        /* This is where we hook our routes to the  */
        global $wp_query;
        add_rewrite_tag( '%frontendroute%', '([^&]+)' );
        foreach (self::$routes as $route) {
            $resolver = preg_replace('/{\w+}/i','([^\/]+)',$route[1]);
            add_rewrite_rule('^'.$resolver.'$', 'index.php?frontendroute=1', 'top');
        }
    }
    static function bypassQuery() {
        if (get_query_var("frontendroute", false)) {
            global $wp_query, $post;

            // Make a fake query to ensure
            // the data is passed to a template
            $post = new stdClass();
            $post->ID= 1;
            $post->post_category= array();
            $post->post_content='';
            $post->post_status='publish';
            $post->post_title= '';
            $post->post_type='page';
            $post->comment_status = 'closed';
            setup_postdata($post);
            $wp_query -> queried_object = array($post);
            $wp_query -> posts = array($post);
            $wp_query -> post = $post;
            $wp_query -> found_posts = 1;
            $wp_query -> current_post = -1;
            $wp_query -> in_the_loop = false;
            $wp_query -> post_count = 1;
            $wp_query -> max_num_pages = 1;
            $wp_query -> is_single = 1;
            $wp_query -> is_404 = false;
            $wp_query -> is_posts_page = 0;
            $wp_query -> is_home = 0;
            $wp_query -> page = 0;
            $wp_query -> is_post = false;
            $wp_query -> is_page = true;
            $wp_query -> page = false;

            // Resolve the route
            $resolved = self::routesResolver(self::$routes);

            // Call the callback
            $post->post_content = call_user_func_array ($resolved[2], $resolved[4]);

            // Put the data in the template
            if ( $overridden_template = locate_template( 'page.php' ) ) {
                load_template($overridden_template);
                die();
            }

        }
    }
}
class BackRoute extends WPRoute {

    public static $routes = array();
    static function start() {
        add_action('init', 'flush_rewrite_rules' );
        add_action('init', array('WPBackRoute', 'onInit'));
        add_action('parse_query', array('WPBackRoute', 'bypassQuery'));
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
FrontRoute::start();
BackRoute::start();
