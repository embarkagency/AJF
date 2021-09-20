<?php
/*
Plugin Name: WP AJAX Filtering
Plugin URI: https://embarkagency.com.au
Description: Easily add archives for post types with ajax filtering and shortcode attributes.
Version: 5.10.2
Author: Daniel Garden
Author URI: #
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('wp_enqueue_scripts', 'wp_ajf_init');
function wp_ajf_init()
{
    wp_enqueue_script('wp-ajf-js', plugins_url('/js/wp_ajf.js', __FILE__), array('jquery'));
}

$WP_AJF_DATA = [];

function register_grid($post_type, $default_data)
{
    global $WP_AJF_DATA;

    if (!isset($WP_AJF_DATA[$post_type])) {
        $WP_AJF_DATA[$post_type] = [];
    }
    foreach ($default_data as $default_key => $default_property) {
        $WP_AJF_DATA[$post_type][$default_key] = $default_property;
    }
}

function register_filters($post_type, $filters)
{
    global $WP_AJF_DATA;

    if (!isset($WP_AJF_DATA[$post_type])) {
        $WP_AJF_DATA[$post_type] = [];
    }

    $WP_AJF_DATA[$post_type]["temp_filters"] = $filters;
}

function real_register_filters($post_type, $filters)
{
    global $WP_AJF_DATA;

    if (!isset($WP_AJF_DATA[$post_type])) {
        $WP_AJF_DATA[$post_type] = [];
    }

    $args = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
    );

    $loop = new WP_Query($args);
    while ($loop->have_posts()): $loop->the_post();
        $id = get_the_ID();
        $plan_details = ($WP_AJF_DATA[$post_type]["get_details"])($id);
        foreach ($filters as $filter_key => $filter_data) {
            if (isset($plan_details[$filter_key])) {
                $filters[$filter_key] = (object) $filters[$filter_key];
                $filters[$filter_key]->options[] = $plan_details[$filter_key];
            }
        }
    endwhile;
    wp_reset_postdata();

    foreach ($filters as $filter_key => $filter_data) {
        $filter_data->options = array_unique($filter_data->options);
        sort($filter_data->options);
    }

    return $filters;
}

function render_grid_items($atts, $post_data)
{
    $loop = new WP_Query([
        'post_type' => $atts["post_type"],
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);
    $items = [];
    while ($loop->have_posts()): $loop->the_post();
        $id = get_the_ID();
        $details = ($post_data["get_details"])($id);

        if (isset($post_data["filters"])) {
            $filter_options = $post_data["filters"];
            $matches = true;

            foreach ($filter_options as $filter) {
                if (!($filter->matches)($atts, $details)) {
                    $matches = false;
                }
            }

            if ($matches) {
                $items[] = $details;
            }
        } else {
            $items[] = $details;
        }
    endwhile;
    wp_reset_postdata();

    $count = isset($atts["count"]) && !empty($atts["count"]) ? intval($atts["count"]) : 10;
    $total = count($items);
    $items = array_slice($items, 0, $count);

    $output = '';
    $container_class = isset($post_data["class"]) ? $post_data["class"] : "archive-grid";

    if (count($items) > 0) {
        $output .= '<div class="' . $container_class . '">';
        foreach ($items as $details) {
            if (isset($post_data["render"])) {
                $output .= ($post_data["render"])($details);
            }
        }
        $output .= '</div>';
        if (isset($post_data["view_more"]) && $count < $total) {
            $output .= '<div class="view-more-container">';
            $output .= '<button class="view-more-button" data-post-type="' . $atts["post_type"] . '">' . $post_data["view_more"] . '</button>';
            $output .= '</div>';
        }
    } else {
        $output .= '<div class="no-results">' . (isset($post_data["no_results"]) ? $post_data["no_results"] : "No results found") . '</h4>';
    }

    return ["html" => $output];
}

add_action('init', function () {
    global $WP_AJF_DATA;

    foreach ($WP_AJF_DATA as $ajf_post_type => $ajf_data_type) {

        if (isset($ajf_data_type["temp_filters"])) {
            $real_filters = real_register_filters($ajf_post_type, $ajf_data_type["temp_filters"]);
            $ajf_data_type["filters"] = $real_filters;
        }

        $shortcode_tag = $ajf_post_type . "-grid";

        if (isset($ajf_data_type["filters"])) {
            add_shortcode($ajf_post_type . '-filters', function () use ($ajf_post_type, $ajf_data_type) {
                $output = '';

                $filter_options = $ajf_data_type["filters"];

                $output .= '<div class="filter-options">';
                foreach ($filter_options as $filter_key => $filter) {
                    $output .= '<div class="filter-option">';
                    if (isset($filter->name)) {
                        $output .= '<label for="' . $ajf_post_type . '-filter-' . $filter_key . '">' . $filter->name . '</label>';
                    }
                    $output .= '<div class="filter-select-wrapper">';
                    if (isset($filter->icon)) {
                        $output .= '<div class="filter-icon" style="background-image: url(' . $filter->icon . ')"></div>';
                    }
                    $output .= '<select id="' . $ajf_post_type . '-filter-' . $filter_key . '" class="filter-value" data-type="' . $filter_key . '" data-post-type="' . $ajf_post_type . '">';
                    $output .= '<option value="">Any</option>';
                    foreach ($filter->options as $option) {
                        $output .= '<option value="' . $option . '">' . $option . '</option>';
                    }
                    $output .= '</select>';

                    $output .= '<div class="filter-chevron">';
                    $output .= '<i class="fal fa-chevron-down"></i>';
                    $output .= '</div>';

                    $output .= '</div>';

                    $output .= '</div>';
                }
                $output .= '</div>';

                return $output;
            });
        }

        add_shortcode($shortcode_tag, function ($atts) use ($ajf_post_type, $ajf_data_type, $shortcode_tag) {
            $defaults = [];
            if (isset($ajf_data_type["filters"])) {
                $defaults = $ajf_data_type["filters"];
                $defaults = array_map(function () {
                    return "";
                }, $defaults);
            }

            $defaults = array_merge([
                "post_type" => $ajf_post_type,
                "count" => $ajf_data_type["count"],
            ], $defaults);

            $atts = shortcode_atts($defaults, $atts, $shortcode_tag);
            $render = render_grid_items($atts, $ajf_data_type);

            $output = '';
            $output .= '<div class="archive-container" data-post-type="' . $defaults["post_type"] . '" data-post-count="' . $atts["count"] . '">';
            $output .= $render["html"];
            $output .= '</div>';

            return $output;

        });

        add_action('rest_api_init', function () use ($ajf_post_type, $ajf_data_type) {
            register_rest_route('ajf_get', '/' . $ajf_post_type, array(
                'permission_callback' => '__return_true',
                'methods' => 'GET',
                'callback' => function ($data) use ($ajf_post_type, $ajf_data_type) {
                    $atts = $data->get_params();
                    $atts["post_type"] = $ajf_post_type;
                    $render = render_grid_items($atts, $ajf_data_type);

                    $result = new WP_REST_Response(array(
                        "html" => $render["html"],
                    ), 200);

                    $result->set_headers(array('Cache-Control' => 'max-age=3600'));

                    return $result;
                },
            ));
        });
    }

});

add_action('wp_footer', function () {
    ?>
	<script>
		window.ajf_rest_url = '<?=get_rest_url(null, 'ajf_get');?>';
	</script>
	<?php
});
