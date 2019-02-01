<?php
// Setup FrontRoute
namespace Balise\Bridge;

class FrontRoute extends Route
{
    public static $routes = array();
    public static function start()
    {
        add_action('init', array('Balise\Bridge\FrontRoute', 'onInit'));

        add_filter('theme_templates', array('Balise\AnchorFramework\Anchor', 'loadThemeTemplates'), 10, 4);
        $types = array('index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'embed', 'home', 'frontpage', 'page', 'paged', 'search', 'single', 'singular', 'attachment');
        foreach ($types as $type) {
            add_filter("{$type}_template", array('Balise\AnchorFramework\Anchor', 'getThemeTemplate'), 10, 3);
        }
        add_action('parse_query', array('Balise\Bridge\FrontRoute', 'bypassQuery'));

        $types = array('index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'embed', 'home', 'frontpage', 'page', 'paged', 'search', 'single', 'singular', 'attachment');
        foreach ($types as $type) {
            add_filter("{$type}_template_hierarchy", array('Balise\Bridge\FrontRoute', 'loadThemeTemplates'), 1, 4);
        }

    }
    public static function loadThemeTemplates($post_templates)
    {
        $resolved = self::routesResolver(self::$routes);
        array_unshift($post_templates, isset($resolved[3]) ? $resolved[3] : 'index' . ".php");
        return $post_templates;
    }

    public static function routesResolver($routes, $prefix = "")
    {
        $selected = null;
        foreach ($routes as $route) {
            if ($_SERVER['REQUEST_METHOD'] === $route[0]) {

                // Store the keys used
                preg_match_all('/{(\w+)}/i', $route[1], $keys);

                // Make the attibutes value as wildcard
                $resolver = preg_replace('/{\w+}/i', '([^\/]+)', $route[1]);

                // If the route match

                $request = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $remove = substr(get_bloginfo('url'), strpos(get_bloginfo('url'), '://') + 3);

                if (preg_match('#^/' . $prefix . $resolver . '/?$#i', str_replace($remove, "", $request), $data)) {
                    $selected = $route;

                    // Make sure the attribute 2 is a callback
                    $selected[2] = self::convertToCallable($selected[2]);

                    // Match the value to the keys and add it to the selected route
                    $values = array();
                    foreach ($keys[1] as $key => $id) {
                        $values[$id] = $data[$key + 1];
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

    public static function onInit()
    {

        /* This is where we hook our routes to the  */
        global $wp_query;
        add_rewrite_tag('%frontendroute%', '([^&]+)');
        foreach (self::$routes as $route) {
            $resolver = preg_replace('/{\w+}/i', '([^\/]+)', $route[1]);
            add_rewrite_rule('^' . $resolver . '$', 'index.php?frontendroute=1', 'top');
        }

        // This is added to work with the AnchorFramework and boilerplate
        \add_filter('balise-anchor-getData', function () {
            if (get_query_var("frontendroute", false)) {
                global $wp_query, $post;
                $post->ID = 1;
                $post->post_category = array();
                $post->post_content = '';
                $post->post_status = 'publish';
                $post->post_title = '';
                $post->post_type = 'page';
                $post->comment_status = 'closed';
                setup_postdata($post);

                // Resolve the route
                $resolved = self::routesResolver(self::$routes);

                // Call the callback
                $post->post_content = call_user_func_array($resolved[2], $resolved[4]);

                return new \Balise\AnchorFramework\PostWrapper($post, true);
            }

        });
    }
    public static function bypassQuery()
    {
        if (get_query_var("frontendroute", false) === 1) {
            global $wp_query, $post;

            // Make a fake query to ensure
            // the data is passed to a template
            $post = new \stdClass();
            $post->ID = 1;
            $post->post_category = array();
            $post->post_content = '';
            $post->post_status = 'publish';
            $post->post_title = '';
            $post->post_type = 'page';
            $post->comment_status = 'closed';
            setup_postdata($post);
            $wp_query->queried_object = array($post);
            $wp_query->posts = array($post);
            $wp_query->post = $post;
            $wp_query->found_posts = 1;
            $wp_query->current_post = -1;
            $wp_query->in_the_loop = false;
            $wp_query->post_count = 1;
            $wp_query->max_num_pages = 1;
            $wp_query->is_single = 1;
            $wp_query->is_singular = true;
            $wp_query->is_404 = false;
            $wp_query->is_posts_page = 0;
            $wp_query->is_home = 0;
            $wp_query->page = 0;
            $wp_query->is_post = false;
            $wp_query->is_page = true;
            $wp_query->page = false;

            // Resolve the route
            $resolved = self::routesResolver(self::$routes);

            // Call the callback
            $post->post_content = call_user_func_array($resolved[2], $resolved[4]);

            // Put the data in the template
            if ($overridden_template = locate_template('index.php')) {
                load_template($overridden_template);
                die();
            }

        }
    }
}

FrontRoute::start();
