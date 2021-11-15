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

function register_grid($post_type, $default_data = [])
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

function wp_ajf_get_filter_options($filters, $details)
{
    foreach ($filters as $filter_key => $filter_data) {
        if (isset($details[$filter_key])) {
            $filters[$filter_key] = (object) $filters[$filter_key];
            if ($filters[$filter_key]->type === "select") {
                if (!isset($filters[$filter_key]->options)) {
                    $filter[$filter_key]->options = [];
                }

                if (isset($details[$filter_key])) {
                    if (is_array($details[$filter_key])) {
                        $filters[$filter_key]->options = array_merge($filters[$filter_key]->options, $details[$filter_key]);
                    } else {
                        $filters[$filter_key]->options[] = $details[$filter_key];
                    }
                }

            }
        }
    }

    return $filters;
}

function get_source_data($atts, $fn)
{
    global $WP_AJF_DATA;

    $source = ($fn)($atts);
    return $source;
}

function wp_ajf_real_register_filters($post_type, $filters)
{
    global $WP_AJF_DATA;

    if (!isset($WP_AJF_DATA[$post_type])) {
        $WP_AJF_DATA[$post_type] = [];
    }

    $source = null;

    $has_select = false;
    foreach ($filters as $filter_key => $filter_data) {
        $filter_data = (object) $filter_data;
        $filters[$filter_key] = $filter_data;
        if ($filter_data->type === "select") {
            $has_select = true;
        }
    }

    if ($has_select) {
        if (isset($WP_AJF_DATA[$post_type]["data"])) {
            $data = $WP_AJF_DATA[$post_type]["data"];
            if (is_callable($data)) {
                $source = get_source_data([
                    "post_type" => $post_type,
                    "count" => isset($WP_AJF_DATA[$post_type]["count"]) ? $WP_AJF_DATA[$post_type]["count"] : 0,
                    "pge" => 1,
                ], $data);
            } else if (is_string($data)) {
                $post_type = $WP_AJF_DATA[$post_type]["data"];
            }
        }
    }

    if (isset($source)) {
        foreach ($source as $details) {
            if ($has_select) {
                $filters = wp_ajf_get_filter_options($filters, (array) $details);
            }
        }
    } else {
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        $loop = new WP_Query($args);
        while ($loop->have_posts()): $loop->the_post();
            $id = get_the_ID();

            $details;
            if (isset($WP_AJF_DATA[$post_type]["get_details"])) {
                $details = ($WP_AJF_DATA[$post_type]["get_details"])($id);
                $details = (array) $details;
            } else {
                $details = get_post($id, ARRAY_A);
            }

            $filters = wp_ajf_get_filter_options($filters, $details);
        endwhile;
        wp_reset_postdata();
    }

    foreach ($filters as $filter_key => $filter_data) {
        if ($filter_data->type === "select") {
            $filter_data->options = array_unique($filter_data->options);
            sort($filter_data->options);
        }
    }

    return $filters;
}

function wp_ajf_contains($needle = null, $haystack = null)
{
    return isset($needle) && !empty($needle) ? strpos(strtolower($haystack), strtolower($needle)) > -1 : true;
}

function wp_ajf_like($needle = null, $haystack = null)
{
    return isset($needle) && !empty($needle) ? strtolower($needle) == strtolower($haystack) : true;
}

function wp_ajf_exact($needle = null, $haystack = null)
{
    return isset($needle) && !empty($needle) ? $needle == $haystack : true;
}

function wp_ajf_run_filter($post_data, $items, $details, $atts)
{
    $details = (array) $details;
    if (isset($post_data["filters"])) {
        $filter_options = $post_data["filters"];
        $matches = true;
        foreach ($filter_options as $filter_key => $filter) {
            if ($filter->type === "clear") {
                continue;
            }
            if ($filter->type === "checkbox") {
                if (isset($atts[$filter_key]) && $atts[$filter_key] === "true") {
                    $atts[$filter_key] = true;
                } else {
                    unset($atts[$filter_key]);
                }
            }
            if (isset($filter->matches)) {
                if (!($filter->matches)($atts, $details)) {
                    $matches = false;
                }
            }
        }
        if ($matches) {
            $items[] = $details;
        }
    } else {
        $items[] = $details;
    }
    return $items;
}

function wp_ajf_render_grid_items($atts, $post_data, $include_items = false)
{
    $output = '';
    $pagination = '';

    $post_type = null;
    $source = null;

    if (isset($post_data["data"])) {
        $data = $post_data["data"];
        if (is_callable($data)) {
            $source = get_source_data($atts, $data);
        } else if (is_string($data)) {
            $post_type = $post_data["data"];
        }
    } else {
        $post_type = $atts["post_type"];
    }

    $items = [];

    if (isset($source)) {
        foreach ($source as $details) {
            $items = wp_ajf_run_filter($post_data, $items, $details, $atts);
        }
    } else {
        $loop = new WP_Query([
            's' => null,
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        while ($loop->have_posts()): $loop->the_post();
            $id = get_the_ID();
            if (isset($post_data["get_details"])) {
                $details = ($post_data["get_details"])($id);
            } else {
                $details = get_post($id, ARRAY_A);
            }

            $items = wp_ajf_run_filter($post_data, $items, $details, $atts);

        endwhile;
        wp_reset_postdata();
    }

    $page;
    if (isset($atts["pge"])) {
        $page = intval($atts["pge"]);
    } else {
        $page = 1;
    }

    $count = isset($atts["count"]) && !empty($atts["count"]) ? intval($atts["count"]) : -1;
    $total = count($items);

    $page_count = ceil($total / $count);

    if ($page > $page_count) {
        $page = $page_count;
    }
    if ($page < 1) {
        $page = 1;
    }

    $offset = ($page - 1) * $count;
    $page_numbers = [];

    if ((!isset($post_data["has_nav"]) || (isset($post_data["has_nav"]) && $post_data["has_nav"] !== false)) && $page > 1) {
        $page_numbers[] = ["page" => $page - 1, "label" => "Prev"];
    }
    for ($i = 0; $i < $page_count; $i++) {
        $page_numbers[] = ["page" => ($i + 1), "label" => ($i + 1)];
    }
    if ((!isset($post_data["has_nav"]) || (isset($post_data["has_nav"]) && $post_data["has_nav"] !== false)) && $page < $page_count) {
        $page_numbers[] = ["page" => $page + 1, "label" => "Next"];
    }

    //sorting
    if (isset($atts["order"])) {
        $order = $atts["order"];

        if ($order === "random") {
            shuffle($items);
        } else if (is_callable($order)) {
            usort($items, $order);
        }
    }

    if ($count > 0) {
        $items = array_slice($items, $offset, $count);
    }

    $container_class = isset($post_data["class"]) ? $post_data["class"] : "archive-grid";
    $as = isset($post_data["as"]) ? $post_data["as"] : "div";
    $as_end = strtok($as, " ");

    $prepend = isset($post_data["prepend"]) ? $post_data["prepend"] : "";
    $append = isset($post_data["append"]) ? $post_data["append"] : "";

    $output .= $prepend;

    if (count($items) > 0) {
        $output .= '<' . $as . ' class="' . $container_class . '">';
        foreach ($items as $details) {
            if (isset($post_data["render"])) {
                $details = (array) $details;
                $output .= ($post_data["render"])($details, $atts);
            } else {
                $output .= "Please specify a render function";
                break;
            }
        }
        $output .= '</' . $as_end . '>';
        if (!isset($post_data["pagination"]) && isset($post_data["view_more"]) && $count < $total) {
            $output .= '<div class="view-more-container">';
            $output .= '<button class="view-more-button" data-post-type="' . $atts["post_type"] . '">' . $post_data["view_more"] . '</button>';
            $output .= '</div>';
        }
    } else {
        $output .= '<div class="no-results">' . (isset($post_data["no_results"]) ? $post_data["no_results"] : "No results found") . '</div>';
    }

    $output .= $append;

    if (count($page_numbers) > 0 && $count > 0 && isset($post_data["pagination"])) {
        $pagination .= '<div class="pagination-grid">';
        foreach ($page_numbers as $page_number) {
            $page_active = '';
            if ($page_number["page"] === $page) {
                $page_active = 'active';
            }
            $pagination .= '<a class="pagination-num ' . $page_active . '" data-page="' . $page_number["page"] . '" href="?pge=' . $page_number["page"] . '" data-post-type="' . $atts["post_type"] . '">';
            $pagination .= $page_number["label"];
            $pagination .= '</a>';
        }
        $pagination .= '</div>';
    }

    $response = ["html" => $output];

    if ((isset($post_data["include_items"]) && $post_data["include_items"] === true) || $include_items === true) {
        $response["items"] = $items;
    }
    $response["total"] = $total;

    if ($count > 0 && isset($post_data["pagination"]) && isset($pagination) && !empty($pagination)) {
        $response["pagination"] = $pagination;
    } else {
        $response["pagination"] = "";
    }

    return $response;
}

function wp_ajf_render_filter($ajf_post_type, $filter, $filter_key)
{
    $output = '';

    $output .= '<div class="filter-option">';

    if ($filter_key === "s" || $filter_key === "search") {
        $output .= '<div class="filter-option-error">`' . $filter_key . '` property is not allowed in filters. To fix please capitalize or change.</div>';
    } else {
        if (isset($filter->name) && $filter->type !== "clear") {
            $output .= '<label for="' . $ajf_post_type . '-filter-' . $filter_key . '">' . $filter->name . '</label>';
        }

        $default_props = ' id="' . $ajf_post_type . '-filter-' . $filter_key . '" class="filter-value" data-type="' . $filter_key . '" data-post-type="' . $ajf_post_type . '" data-input-type="' . $filter->type . '"';

        $get_value = isset($_GET[$filter_key]) ? $_GET[$filter_key] : null;
        if ($filter->type === "clear") {
            $output .= '<div class="filter-text-wrapper clear-filter">';
            $output .= '<a href="javascript: void(0);"' . $default_props . '>' . $filter->name . '</a>';
            $output .= '</div>';
        } else if ($filter->type === "text") {
            $output .= '<div class="filter-text-wrapper">';
            $output .= '<input value="' . (isset($get_value) ? $get_value : '') . '" type="text"' . $default_props . ' placeholder="' . (isset($filter->placeholder) ? $filter->placeholder : "") . '"/>';
            $output .= '</div>';
        } else if ($filter->type === "checkbox") {
            $output .= '<div class="filter-text-wrapper">';
            $output .= '<input type="checkbox"' . $default_props . ' ' . (isset($get_value) && $get_value === "true" ? 'checked' : '') . '/>';
            $output .= '</div>';
        } else if ($filter->type === "select") {
            $output .= '<div class="filter-select-wrapper">';
            if (isset($filter->icon)) {
                $output .= '<div class="filter-icon" style="background-image: url(' . $filter->icon . ')"></div>';
            }
            $output .= '<select' . $default_props . '>';
            if (!isset($filter->has_any) || (isset($filter->has_any) && $filter->has_any !== false)) {
                $output .= '<option value="">Any</option>';
            }
            foreach ($filter->options as $option) {
                $output .= '<option value="' . $option . '" ' . (isset($get_value) && $get_value === $option ? 'selected' : '') . '>' . $option . '</option>';
            }
            $output .= '</select>';

            $output .= '<div class="filter-chevron">';
            $output .= '<i class="fal fa-chevron-down"></i>';
            $output .= '</div>';

            $output .= '</div>';
        }
    }

    $output .= '</div>';
    return $output;
}

function wp_ajf_get_defaults($ajf_data_type, $ajf_post_type)
{
    $defaults = [];
    if (isset($ajf_data_type["filters"])) {
        $defaults = $ajf_data_type["filters"];
        foreach ($defaults as $filter_key => $filter) {
            if (isset($filter->default) && !empty($filter->default)) {
                $defaults[$filter_key] = $filter->default;
            } else if ($filter_key === "count") {
                $defaults[$filter_key] = isset($ajf_data_type["count"]) ? $ajf_data_type["count"] : "";
            } else {
                $defaults[$filter_key] = null;
            }
        }
    }

    $defaults = array_merge([
        "post_type" => $ajf_post_type,
        "count" => isset($ajf_data_type["count"]) ? $ajf_data_type["count"] : 0,
        "order" => isset($ajf_data_type["order"]) ? $ajf_data_type["order"] : null,
        "pge" => 1,
        "pagination" => isset($ajf_data_type["pagination"]) ? $ajf_data_type["pagination"] : false,
    ], $defaults);

    return $defaults;
}

function get_grid_data($ajf_post_type, $atts = [])
{
    global $WP_AJF_DATA;

    $ajf_data_type = isset($WP_AJF_DATA[$ajf_post_type]) ? $WP_AJF_DATA[$ajf_post_type] : null;

    if ($ajf_data_type) {
        $defaults = wp_ajf_get_defaults($ajf_data_type, $ajf_post_type);
        $atts = array_merge($defaults, $atts);
        $render = wp_ajf_render_grid_items($atts, $ajf_data_type, true);

        return $render["items"];
    }

    return [];
}

add_action('init', function () {
    global $WP_AJF_DATA;

    foreach ($WP_AJF_DATA as $ajf_post_type => $ajf_data_type) {

        if (isset($ajf_data_type["temp_filters"])) {
            $real_filters = wp_ajf_real_register_filters($ajf_post_type, $ajf_data_type["temp_filters"]);
            $ajf_data_type["filters"] = $real_filters;
        }

        $shortcode_tag = $ajf_post_type . "-grid";

        if (isset($ajf_data_type["filters"])) {
            add_shortcode($ajf_post_type . '-filters', function () use ($ajf_post_type, $ajf_data_type) {
                $output = '';

                $filter_options = $ajf_data_type["filters"];

                $output .= '<div class="filter-options">';
                foreach ($filter_options as $filter_key => $filter) {
                    $output .= wp_ajf_render_filter($ajf_post_type, $filter, $filter_key);
                }
                $output .= '</div>';

                return $output;
            });

            foreach ($ajf_data_type["filters"] as $filter_key => $filter) {
                add_shortcode($ajf_post_type . "-filters-" . $filter_key, function () use ($ajf_post_type, $filter, $filter_key) {
                    return wp_ajf_render_filter($ajf_post_type, $filter, $filter_key);
                });
            }
        }

        add_shortcode($shortcode_tag, function ($atts) use ($ajf_post_type, $ajf_data_type, $shortcode_tag) {

            $defaults = wp_ajf_get_defaults($ajf_data_type, $ajf_post_type);

            $atts = shortcode_atts($defaults, $atts, $shortcode_tag);

            foreach ($_GET as $get_key => $get_value) {
                if (isset($get_value) && $get_value !== "") {
                    $atts[$get_key] = $get_value;
                }
            }

            $render = wp_ajf_render_grid_items($atts, $ajf_data_type);

            $output = '';

            $wrapper = isset($ajf_data_type["wrapper"]) ? $ajf_data_type["wrapper"] : "div";
            $wrapper_end = strtok($wrapper, " ");

            $output .= '<' . $wrapper . ' class="archive-container" data-post-type="' . $defaults["post_type"] . '" data-post-count="' . $atts["count"] . '" data-page="' . $atts["pge"] . '">';
            $output .= $render["html"];
            $output .= '</' . $wrapper_end . '>';

            if (filter_var($atts["pagination"], FILTER_VALIDATE_BOOLEAN) !== false && isset($render["pagination"])) {
                $output .= '<div class="pagination-container" data-post-type="' . $defaults["post_type"] . '">';
                $output .= $render["pagination"];
                $output .= '</div>';
            }

            return $output;
        });

        add_action('rest_api_init', function () use ($ajf_post_type, $ajf_data_type) {
            register_rest_route('ajf_get', '/' . $ajf_post_type, array(
                'permission_callback' => '__return_true',
                'methods' => 'GET',
                'callback' => function ($data) use ($ajf_post_type, $ajf_data_type) {
                    $atts = $data->get_params();
                    $atts["post_type"] = $ajf_post_type;
                    $atts["order"] = isset($ajf_data_type["order"]) ? $ajf_data_type["order"] : null;

                    $data = wp_ajf_render_grid_items($atts, $ajf_data_type);

                    $result = new WP_REST_Response($data, 200);

                    if (!isset($ajf_data_type["cache"]) || isset($ajf_data_type["cache"]) && $ajf_data_type["cache"] !== false) {
                        $cache_time = isset($ajf_data_type["cache"]) && is_numeric($ajf_data_type["cache"]) ? $ajf_data_type["cache"] : 3600;

                        $result->set_headers(array('Cache-Control' => 'max-age=' . $cache_time));
                    }

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