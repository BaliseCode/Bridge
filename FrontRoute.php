<?php
// Setup FrontRoute
namespace Balise\Bridge;

class FrontRoute extends Route {
    public static $routes = array();
    static function start() {
        add_action('init', 'flush_rewrite_rules' );
        add_action('init', array('FrontRoute', 'onInit'));
        add_action('parse_query', array('FrontRoute', 'bypassQuery'));
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

FrontRoute::start();
