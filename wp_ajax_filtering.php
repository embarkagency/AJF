<?php
/*
Plugin Name: AJAX Filterables (AJF)
Plugin URI: #
Description: Easily add archives for post types with ajax filtering and shortcode attributes.
Version: 5.10.2
Author: Daniel Garden
Author URI: #
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Elementor\Plugin;

require plugin_dir_path( __FILE__ ) . '/Mustache/Autoloader.php';
Mustache_Autoloader::register();


define( 'AJF', __FILE__ );

class AJF_Instance
{    
    /**
     * __construct
     *
     * @return void
     */
    function __construct()
    {
        $this->version = '1';

        $this->grids = [];
        $this->templates = [];

        $this->rest_route = 'ajf';

        $this->init_actions();
        $this->init_default_templates();
        $this->init_elementor_widgets();
    }

    /**
     * init_actions
     *
     * @return void
     */
    function init_actions()
    {
        add_filter('rest_request_before_callbacks', [ $this, 'peak_cache' ], 1000, 3);
        add_action('wp_enqueue_scripts', [ $this, 'init_scripts' ], 1000);
        add_action('init', [ $this, 'init_shortcodes' ], 1000);
        add_action('rest_api_init', [ $this, 'init_rest_api' ], 1000);
        add_action('wp_footer', [ $this, 'init_footer_config' ], 1000);
    }

    /**
     * init_scripts
     *
     * @return void
     */
    function init_scripts()
    {
        wp_enqueue_script('wp-ajf-js', plugins_url('/js/ajf-main.js', __FILE__), array('jquery'));
    }

    /**
     * init_elementor_widgets
     *
     * @return void
     */
    function init_elementor_widgets()
    {
        add_action( 'elementor/widgets/widgets_registered', function() {
            require_once('widgets/elementor-grid.php');
            require_once('widgets/elementor-filters.php');
        
            $grid_widget =	new Elementor_AJF_Grid_Widget();
            $filters_widget =	new Elementor_AJF_Filters_Widget();
        
            // Let Elementor know about our widget
            Plugin::instance()->widgets_manager->register_widget_type( $grid_widget );
            Plugin::instance()->widgets_manager->register_widget_type( $filters_widget );
        }, 1000); 
    }
    
    /**
     * register_grid_widget
     *
     * @param  mixed $settings
     * @param  mixed $include_cache
     * @return void
     */
    function register_grid_widget($settings, $include_cache=false)
    {
        if(isset($settings['source']) && !empty($settings['source'])) {
			$source = $settings['source'];
			$grid_type = sanitize_title($settings['source'] . '-elementor');
            

            if(isset($settings['unique_id']) && !empty($settings['unique_id'])){
                $grid_type .= '-' . sanitize_title($settings['unique_id']);
            }

			$config = ['data' => $settings['source']];
			
			if(isset($settings['count']) && !empty($settings['count'])){
				$config['count'] = $settings['count'];
			}

			if(isset($settings['pagination']) && !empty($settings['pagination'])){
				$config['pagination'] = true;;
			} else {
				$config['pagination'] = false;
			}

			if(isset($settings['has_nav']) && !empty($settings['has_nav'])){
				$config['has_nav'] = true;
			} else {
				$config['has_nav'] = false;
			}

            if(isset($settings['order_by']) && $settings["order_by"] !== "default") {
                $order = $settings["order_by"];
                if($order === "random") {
                    $config["order"] = "random";
                } else if($order === "id-0") {
                    $config["order"] = function($a, $b) {
                        return $a['ID'] - $b['ID'];
                    };
                } else if($order === "id-9") {
                    $config["order"] = function($a, $b) {
                        return $b['ID'] - $a['ID'];
                    };
                }
                else if($order === "title-a") {
                    $config["order"] = function($a, $b) {
                        return strnatcmp($a['post_title'], $b['post_title']);
                    };
                } else if($order === "title-z") {
                    $config["order"] = function($b, $a) {
                        return strnatcmp($a['post_title'], $b['post_title']);
                    };
                } else if($order === "date-old") {
                    $config["order"] = function($a, $b) {
                        $t1 = strtotime($a['post_modified']);
                        $t2 = strtotime($b['post_modified']);
                        return $t1 - $t2;
                    };
                } else if($order === "date-new") {
                    $config["order"] = function($a, $b) {
                        $t1 = strtotime($a['post_modified']);
                        $t2 = strtotime($b['post_modified']);
                        return $t2 - $t1;
                    };
                }
            }

            if(isset($settings['debug_mode']) && !empty($settings['debug_mode'])){
                $config["render"] = function($details) use($settings) {
                    return $this->debug_mode_variables($details);
                };
            } else {
                $config["render"] = $settings["render_template"];
            }

            // $config["cache"] = false;
            $config["is_widget"] = true;
            $config["theme_atts"] = [
                "data-widget-source" => $settings["source"],
            ];
            $config["extra_styles"] = '';

			$this->register_grid($grid_type, $config);
			$this->trigger_init($grid_type);

            if($include_cache) {
                $this->set_cache($grid_type, $settings);
            }

            return '[' . $grid_type . '-grid]';
        }
    }

    function register_filters_widget($settings, $include_cache = false) {
        if(isset($settings['source']) && !empty($settings['source'])) {
			$source = $settings['source'];
			$grid_type = sanitize_title($settings['source'] . '-elementor');
            

            if(isset($settings['unique_id']) && !empty($settings['unique_id'])){
                $grid_type .= '-' . sanitize_title($settings['unique_id']);
            }

			$config = [

            ];

            $this->register_filters($grid_type, $config);
			$this->trigger_init($grid_type);

            if($include_cache) {
                $this->set_cache($grid_type, $settings);
            }

            return '[' . $grid_type . '-filters]';
        }
    }

    function render_from_template($template, $details)
    {
        $m = new Mustache_Engine(array('entity_flags' => ENT_QUOTES));
        return $m->render($template, $details);
    }
    
    /**
     * get_variables_as_string
     *
     * @param  mixed $array
     * @param  mixed $prev_key
     * @return void
     */
    function get_variables_as_string($array, $prev_key='')
    {
        $variables = [];
        foreach($array as $key => $value){
            if(is_array($value)){
                $variables = array_merge($variables, $this->get_variables_as_string($value, $key));
            } else {
                if($prev_key != ''){
                    $variables[] = $prev_key . '.' . $key;
                } else {
                    $variables[] = $key;
                }
            }
        }
        return $variables;
    }
    
    /**
     * debug_mode_variables
     *
     * @param  mixed $details
     * @return void
     */
    function debug_mode_variables($details)
    {
        $variables = $this->get_variables_as_string($details);
        return '<pre>' . var_export($details, true) . '</pre>';
    }
    
    /**
     * cache_key
     *
     * @param  mixed $grid_type
     * @return void
     */
    function cache_key($grid_type)
    {
        return 'ajf-cache' . $grid_type;
    }
    
    /**
     * set_cache
     *
     * @param  mixed $grid_type
     * @param  mixed $settings
     * @return void
     */
    function set_cache($grid_type, $settings)
    {
        $cache = get_transient($this->cache_key($grid_type));
        if($cache) {
            $settings = array_merge((array) json_decode($cache), $settings);
        }
        $encoded = json_encode($settings);
        set_transient($this->cache_key($grid_type), $encoded);
        return $settings;
    }
    
    /**
     * get_cache
     *
     * @param  mixed $grid_type
     * @return void
     */
    function get_cache($grid_type)
    {
        $settings = get_transient($this->cache_key($grid_type));
        if ($settings === false) {
            return false;
        } else {
            return (array) json_decode($settings);
        }
    }
    
    /**
     * peak_cache
     *
     * @param  mixed $response
     * @param  mixed $handler
     * @param  mixed $request
     * @return void
     */
    function peak_cache( $response, $handler, WP_REST_Request $request )
    {
        $params = $request->get_params();
        if(isset($params["post_type"]) && !empty($params["post_type"])){
            $grid_type = $params["post_type"];
            $settings = $this->get_cache($grid_type);
            if($settings) {
                $this->register_grid_widget($settings);
            }
        }
    }

    /**
     * set_grid_filters
     *
     * @param  mixed $grid_type
     * @return void
     */
    function set_grid_filters($grid_type)
    {
        $real_filters = $this->real_register_filters($grid_type);
        $this->grids[$grid_type]["filters"] = $real_filters;
    }
    
    /**
     * get_grids
     *
     * @return void
     */
    function get_grids()
    {
        $grids = [];
        foreach ($this->grids as $grid_type => $grid_data) {
            $grids[$grid_type] = $grid_type;
        }
        return $grids;
    }
    
    /**
     * get_first_grid_type
     *
     * @return void
     */
    function get_first_grid_type()
    {
        $grids = $this->get_grids();
        return array_shift($grids);
    }
    
    /**
     * real_register_filters
     *
     * @param  mixed $grid_type
     * @return void
     */
    function real_register_filters($grid_type)
    {
        $grid_data = $this->grids[$grid_type];

        if(isset($grid_data["temp_filters"])) {
            $filters = $grid_data["temp_filters"];
            $source = null;
            $post_type = $grid_type;
            $has_select = false;

            foreach ($filters as $filter_key => $filter_data) {
                $filter_data = (object) $filter_data;
                $filters[$filter_key] = $filter_data;
                if ($filter_data->type === "select") {
                    $has_select = true;
                }
            }
            if ($has_select) {
                if (isset($grid_data["data"])) {
                    $data = $grid_data["data"];
                    if (is_callable($data)) {
                        $source = get_source_data([
                            "post_type" => $post_type,
                            "count" => isset($grid_data["count"]) ? $grid_data["count"] : 0,
                            "pge" => 1,
                        ], $data);
                    } else if (is_string($data)) {
                        $post_type = $grid_data["data"];
                    }
                }
            }

            if (isset($source)) {
                foreach ($source as $details) {
                    if ($has_select) {
                        $filters = $this->get_filter_options($filters, (array) $details);
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
                    $details = $this->get_post_details($id, $grid_data);
                    $filters = $this->get_filter_options($filters, $details);
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
    }
    
    /**
     * get_post_details
     *
     * @param  mixed $id
     * @param  mixed $grid_data
     * @param  mixed $atts
     * @return void
     */
    function get_post_details($id, $grid_data, $atts=[])
    {
        $details = [];
        if (isset($grid_data["get_details"])) {
            $details = ($grid_data["get_details"])($id, $atts);
            $details = (array) $details;
        } else {
            $default_fields = get_post($id, ARRAY_A);
            $extra_fields = [
                'thumbnail' => get_the_post_thumbnail_url($id, 'full'),
                'permalink' => get_the_permalink($id)
            ];
            $acf_fields = get_fields($id);
            $acf_fields = isset($acf_fields) && !empty($acf_fields) ? $acf_fields : [];
            $details = array_merge($default_fields, $extra_fields, $acf_fields);
        }

        return $details;
    }

    /**
     * register_grid
     *
     * @param  mixed $grid_type
     * @param  mixed $default_data
     * @param  mixed $template_config
     * @return void
     */
    function register_grid($grid_type, $default_data = [], $template_config = [])
    {
        if (is_string($default_data) && isset($this->templates[$default_data])) {
            $default_data = array_merge($this->templates[$default_data], $template_config);
        }
    
        if (!isset($this->grids[$grid_type])) {
            $this->grids[$grid_type] = [];
        }
        foreach ($default_data as $default_key => $default_property) {
            $this->grids[$grid_type][$default_key] = $default_property;
        }
    }

    /**
     * register_grid_template
     *
     * @param  mixed $grid_type
     * @param  mixed $default_data
     * @return void
     */
    function register_grid_template($grid_type, $default_data = [])
    {
        if (!isset($this->templates[$grid_type])) {
            $this->templates[$grid_type] = [];
        }
        foreach ($default_data as $default_key => $default_property) {
            $this->templates[$grid_type][$default_key] = $default_property;
        }
    }
    
    /**
     * register_filters
     *
     * @param  mixed $grid_type
     * @param  mixed $filters
     * @return void
     */
    function register_filters($grid_type, $filters)
    {
        if (!isset($this->grids[$grid_type])) {
            $this->grids[$grid_type] = [];
        }
    
        $this->grids[$grid_type]["temp_filters"] = $filters;
    }

    /**
     * register_filters_template
     *
     * @param  mixed $grid_type
     * @param  mixed $filters
     * @return void
     */
    function register_filters_template($grid_type, $filters)
    {
        if (!isset($this->templates[$grid_type])) {
            $this->templates[$grid_type] = [];
        }
    
        $this->templates[$grid_type]["temp_filters"] = $filters;
    }

    /**
     * get_filter_options
     *
     * @param  mixed $filters
     * @param  mixed $details
     * @return void
     */
    function get_filter_options($filters, $details)
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

    /**
     * render_filter
     *
     * @param  mixed $grid_type
     * @param  mixed $filter_key
     * @return void
     */
    function render_filter($grid_type, $filter_key)
    {
        $filter = $this->grids[$grid_type]["filters"][$filter_key];

        $output = '';
    
        $output .= '<div class="filter-option">';
    
        if ($filter_key === "s" || $filter_key === "search") {
            $output .= '<div class="filter-option-error">`' . $filter_key . '` property is not allowed in filters. To fix please capitalize or change.</div>';
        } else {
            if (isset($filter->name) && $filter->type !== "clear") {
                $output .= '<label for="' . $grid_type . '-filter-' . $filter_key . '">' . $filter->name . '</label>';
            }
    
            $default_props = ' id="' . $grid_type . '-filter-' . $filter_key . '" class="filter-value" data-type="' . $filter_key . '" data-post-type="' . $grid_type . '" data-input-type="' . $filter->type . '"';
    
            $single_get_value = isset($_GET[$filter_key]) ? $_GET[$filter_key] : null;
            $multi_get_value = isset($_GET[$grid_type . '__' . $filter_key]) ? $_GET[$grid_type . '__' . $filter_key] : null;
    
            $get_value = isset($multi_get_value) ? $multi_get_value : $single_get_value;
            $get_multi = explode("--", $get_value);
    
            $is_multi = count($get_multi) > 1;
    
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
                if (isset($filter->multi) && $filter->multi === true) {
                    $default_props .= ' multiple';
                }
                $output .= '<div class="filter-select-wrapper">';
                if (isset($filter->icon)) {
                    $output .= '<div class="filter-icon" style="background-image: url(' . $filter->icon . ')"></div>';
                }
                $output .= '<select' . $default_props . '>';
                if (!isset($filter->has_any) || (isset($filter->has_any) && $filter->has_any !== false)) {
                    $output .= '<option value="">Any</option>';
                }
    
                foreach ($filter->options as $option) {
                    $is_selected = (isset($get_value) && $get_value === $option ? 'selected' : '');
                    if ($is_multi) {
                        $is_selected = in_array($option, $get_multi) ? 'selected' : '';
                    }
                    $output .= '<option value="' . $option . '" ' . $is_selected . '>' . $option . '</option>';
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

    /**
     * run_filter
     *
     * @param  mixed $post_data
     * @param  mixed $items
     * @param  mixed $details
     * @param  mixed $atts
     * @return void
     */
    function run_filter($post_data, $items, $details, $atts)
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

    /**
     * add_filters_shortcode
     *
     * @param  mixed $grid_type
     * @return void
     */
    function add_filters_shortcode($grid_type)
    {
        $grid_data = $this->grids[$grid_type];
        
        if(isset($grid_data["filters"]) && !empty($grid_data["filters"])){
            add_shortcode($grid_type . '-filters', function () use ($grid_type, $grid_data) {
                $output = '';
    
                $output .= '<div class="filter-options">';
                foreach ($grid_data["filters"] as $filter_key => $filter) {
                    $output .= $this->render_filter($grid_type, $filter_key);
                }
                $output .= '</div>';
    
                return $output;
            });
        }
    }

    /**
     * add_filter_shortcode
     *
     * @param  mixed $grid_type
     * @return void
     */
    function add_filter_shortcode($grid_type)
    {
        $grid_data = $this->grids[$grid_type];
        
        if(isset($grid_data["filters"]) && !empty($grid_data["filters"])) {
            foreach ($grid_data["filters"] as $filter_key => $filter) {
                add_shortcode($grid_type . "-filters-" . $filter_key, function () use ($grid_type, $filter_key) {
                    return $this->render_filter($grid_type, $filter_key);
                });
            }
        }
    }

    /**
     * get_source_data
     *
     * @param  mixed $atts
     * @param  mixed $fn
     * @return void
     */
    function get_source_data($atts, $fn)
    {    
        $source = ($fn)($atts);
        return $source;
    }

    /**
     * render_grid
     *
     * @param  mixed $atts
     * @param  mixed $grid_data
     * @param  mixed $include_items
     * @return void
     */
    function render_grid($atts, $grid_data, $include_items = false)
    {
        foreach ($atts as $att_key => $att) {
            $val = $atts[$att_key];
            if (!empty($val) && is_string($val)) {
                $multi_val = explode("--", $val);
    
                if (count($multi_val) > 1) {
                    $atts[$att_key] = $multi_val;
                }
            }
        }

        $output = '';
        $pagination = '';
    
        $post_type = null;
        $source = null;
    
        if (isset($grid_data["data"])) {
            $data = $grid_data["data"];
            if (is_callable($data)) {
                $source = $this->get_source_data($atts, $data);
            } else if (is_string($data)) {
                $post_type = $grid_data["data"];
            }
        } else {
            $post_type = $atts["post_type"];
        }
    
        $items = [];
    
        if (isset($source)) {
            foreach ($source as $details) {
                $items = $this->run_filter($grid_data, $items, $details, $atts);
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
                $details = $this->get_post_details($id, $grid_data, $atts);
                $items = $this->run_filter($grid_data, $items, $details, $atts);
    
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
    
        if ((!isset($grid_data["has_nav"]) || (isset($grid_data["has_nav"]) && $grid_data["has_nav"] !== false)) && $page > 1) {
            $page_numbers[] = ["page" => $page - 1, "label" => "Prev"];
        }
        for ($i = 0; $i < $page_count; $i++) {
            $page_numbers[] = ["page" => ($i + 1), "label" => ($i + 1)];
        }
        if ((!isset($grid_data["has_nav"]) || (isset($grid_data["has_nav"]) && $grid_data["has_nav"] !== false)) && $page < $page_count) {
            $page_numbers[] = ["page" => $page + 1, "label" => "Next"];
        }
    
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
    
        $container_class = isset($grid_data["class"]) ? $grid_data["class"] : "archive-grid";
        $as = isset($grid_data["as"]) ? $grid_data["as"] : "div";
        $as_end = strtok($as, " ");
    
        $prepend = isset($grid_data["prepend"]) ? $grid_data["prepend"] : "";
        $prepend = is_callable($prepend) ? ($prepend)($items) : $prepend;
    
        $append = isset($grid_data["append"]) ? $grid_data["append"] : "";
        $append = is_callable($append) ? ($append)($items) : $append;
    
        $header = isset($grid_data["header"]) ? $grid_data["header"] : "";
        $header = is_callable($header) ? ($header)($items) : $header;
    
        $footer = isset($grid_data["footer"]) ? $grid_data["footer"] : "";
        $footer = is_callable($footer) ? ($footer)($items) : $footer;
    
        $output .= $prepend;
    
        if (count($items) > 0) {
            $output .= '<' . $as . ' class="' . $container_class . '">';
            $output .= $header;
            foreach ($items as $itemIndex => $details) {
                if (isset($grid_data["render"])) {
                    $details = (array) $details;
                    $details["index"] = $itemIndex;

                    if(is_string($grid_data["render"])) {
                        $output .= $this->render_from_template($grid_data["render"], $details);
                    } else {
                        $output .= ($grid_data["render"])($details, $atts);
                    }
                } else {
                    $output .= "Please specify a render function";
                    break;
                }
            }
            $output .= $footer;
            $output .= '</' . $as_end . '>';
            if (!isset($grid_data["pagination"]) && isset($grid_data["view_more"]) && $count < $total) {
                $output .= '<div class="view-more-container">';
                $output .= '<button class="view-more-button" data-post-type="' . $atts["post_type"] . '">' . $grid_data["view_more"] . '</button>';
                $output .= '</div>';
            }
        } else {
            $output .= '<div class="no-results">' . (isset($grid_data["no_results"]) ? $grid_data["no_results"] : "No results found") . '</div>';
        }
    
        $output .= $append;
    
        if (count($page_numbers) > 0 && $count > 0 && isset($grid_data["pagination"])) {
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
    
        if ((isset($grid_data["include_items"]) && $grid_data["include_items"] === true) || $include_items === true) {
            $response["items"] = $items;
        }
        $response["total"] = $total;
    
        if ($count > 0 && isset($grid_data["pagination"]) && isset($pagination) && !empty($pagination)) {
            $response["pagination"] = $pagination;
        } else {
            $response["pagination"] = "";
        }
    
        return $response;
    }

    /**
     * add_grid_shortcode
     *
     * @param  mixed $grid_type
     * @return void
     */
    function add_grid_shortcode($grid_type)
    {
        $shortcode_tag = $grid_type . "-grid";
        add_shortcode($shortcode_tag, function ($atts) use ($grid_type, $shortcode_tag)
        {
            $grid_data = $this->grids[$grid_type];
            $defaults = $this->get_default_atts($grid_type);
            $atts = shortcode_atts($defaults, $atts, $shortcode_tag);

            foreach ($_GET as $get_key => $get_value) {
                if (isset($get_value) && $get_value !== "") {
                    if (strpos($get_key, $grid_type . '__') === 0) {
                        $get_key = str_replace($grid_type . '__', '', $get_key);
                    }

                    $atts[$get_key] = $get_value;
                }
            }
            $render = $this->render_grid($atts, $grid_data);
            $output = '';

            if (!isset($atts["pagination"]) || (isset($atts["pagination"]) && $atts["pagination"] !== "only")) {
                $wrapper = isset($grid_data["wrapper"]) ? $grid_data["wrapper"] : "div";
                $wrapper_end = strtok($wrapper, " ");

                $grid_attributes = [
                    "class" => "archive-container",
                    "data-post-type" => $grid_type,
                    "data-post-count" => $atts["count"],
                    "data-page" => $atts["pge"],
                    "data-no-cache" => (isset($grid_data["cache"]) && $grid_data["cache"] === false ? "true" : "false"),
                ];

                $pagination_attributes = [
                    "class" => "pagination-container",
                    "data-post-type" => $grid_type,
                ];

                if(isset($grid_data["is_widget"]) && $grid_data["is_widget"] === true) {
                    $grid_attributes["data-is-widget"] = "true";
                    $pagination_attributes["data-is-widget"] = "true";

                    if(isset($grid_data["theme_atts"])) {
                        $grid_attributes = array_merge($grid_attributes, $grid_data["theme_atts"]);
                        $pagination_attributes = array_merge($pagination_attributes, $grid_data["theme_atts"]);
                    }

                    if(isset($grid_data["extra_styles"])) {
                        $output .= '<style>' . $grid_data["extra_styles"] . '</style>';
                    }
                }

                $output .= '<' . $wrapper . ' ' . $this->html_attributes($grid_attributes) . '>';
                $output .= $render["html"];
                $output .= '</' . $wrapper_end . '>';
            }

            if ((filter_var($atts["pagination"], FILTER_VALIDATE_BOOLEAN) !== false || isset($atts["pagination"]) && $atts["pagination"] === "only") && isset($render["pagination"])) {
                $output .= '<div ' . $this->html_attributes($pagination_attributes) . '>';
                $output .= $render["pagination"];
                $output .= '</div>';
            }

            return $output;
        });
    }

    function get_post_sources()
    {
		$post_types = get_post_types([], 'objects');
		array_walk($post_types, function(&$a, $b) {
			$a = $a->label;
		});
        return $post_types;
    }

    function html_attributes($attributes)
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= $key . '="' . $value . '" ';
        }
        return $html;
    }

    /**
     * match_exact
     *
     * @param  mixed $needle
     * @param  mixed $haystack
     * @return void
     */
    function match_exact($needle = null, $haystack = null)
    {
        return isset($needle) && !empty($needle) ? $needle == $haystack : true;
    }
    
    /**
     * match_contains
     *
     * @param  mixed $needle
     * @param  mixed $haystack
     * @return void
     */
    function match_contains($needle = null, $haystack = null)
    {
        return isset($needle) && !empty($needle) ? strpos(strtolower($haystack), strtolower($needle)) > -1 : true;
    }
    
    /**
     * match_like
     *
     * @param  mixed $needle
     * @param  mixed $haystack
     * @return void
     */
    function match_like($needle = null, $haystack = null)
    {
        return isset($needle) && !empty($needle) ? strtolower($needle) == strtolower($haystack) : true;
    }

    /**
     * get_grid_data
     *
     * @param  mixed $grid_type
     * @param  mixed $atts
     * @return void
     */
    function get_grid_data($grid_type, $atts = [])
    {
        $grid_data = isset($this->grids[$grid_type]) ? $this->grids[$grid_type] : null;

        if($grid_data) {
            $defaults = $this->get_default_atts($grid_type);
            $atts = array_merge($defaults, $atts);
            $render = $this->render_grid($atts, $grid_data, true);
    
            return isset($render["items"]) ? $render["items"] : [];
        }

        return [];
    }

    function init_rest_api()
    {
        register_rest_route($this->rest_route, '/v' . $this->version, array(
            'permission_callback' => '__return_true',
            'methods' => 'GET',
            'callback' => function ($data) {
                $atts = $data->get_params();
                $grid_type = isset($atts["post_type"]) ? $atts["post_type"] : "";

                if(isset($this->grids[$grid_type])) {
                    $grid_data = $this->grids[$grid_type];
    
                    $atts["grid_type"] = $grid_type;
                    $atts["order"] = isset($grid_data["order"]) ? $grid_data["order"] : null;
    
                    $data = $this->render_grid($atts, $grid_data);
    
                    $result = new WP_REST_Response($data, 200);
    
                    if (!isset($grid_data["cache"]) || isset($grid_data["cache"]) && $grid_data["cache"] !== false) {
                        $cache_time = isset($grid_data["cache"]) && is_numeric($grid_data["cache"]) ? $grid_data["cache"] : 3600;
    
                        $result->set_headers(array('Cache-Control' => 'max-age=' . $cache_time));
                    }
    
                    return $result;
                } else {
                    return new WP_Error('rest_grid_type_not_found', 'Grid type not found', array('status' => 404));
                }
                
            },
        ));  
    }

    /**
     * get_default_atts
     *
     * @param  mixed $grid_type
     * @return void
     */
    function get_default_atts($grid_type)
    {
        $grid_data = $this->grids[$grid_type];

        $defaults = [];
        if (isset($grid_data["filters"])) {
            $defaults = $grid_data["filters"];
            foreach ($defaults as $filter_key => $filter) {
                if (isset($filter->default) && !empty($filter->default)) {
                    $defaults[$filter_key] = $filter->default;
                } else if ($filter_key === "count") {
                    $defaults[$filter_key] = isset($grid_data["count"]) ? $grid_data["count"] : "";
                } else {
                    $defaults[$filter_key] = null;
                }
            }
        }
    
        $defaults = array_merge([
            "post_type" => $grid_type,
            "count" => isset($grid_data["count"]) ? $grid_data["count"] : 0,
            "order" => isset($grid_data["order"]) ? $grid_data["order"] : null,
            "pge" => 1,
            "pagination" => isset($grid_data["pagination"]) ? $grid_data["pagination"] : false,
        ], $defaults);
    
        return $defaults;
    }
    
    /**
     * trigger_init
     *
     * @param  mixed $grid_type
     * @return void
     */
    function trigger_init($grid_type) {
        $this->set_grid_filters($grid_type);
        $this->add_filters_shortcode($grid_type);
        $this->add_filter_shortcode($grid_type);
        $this->add_grid_shortcode($grid_type);
    }

    /**
     * init_shortcodes
     *
     * @return void
     */
    function init_shortcodes()
    {
        foreach ($this->grids as $grid_type => $grid_data) {
            $this->trigger_init($grid_type);
        }
    }

    /**
     * init_default_templates
     *
     * @return void
     */
    function init_default_templates()
    {
        $this->register_grid_template("ajf-post", [
            "data" => "post",
            "pagination" => true,
            "has_nav" => false,
            "include_items" => true,
            "count" => 10,
            "render" => function ($details) {
                return $details["post_title"] . "<br />";
            },
        ]);
        
        $this->register_filters_template("ajf-post", [
            "query" => [
                "name" => "Search",
                "type" => "text",
                "matches" => function ($atts, $details) {
                    return $this->match_contains($atts["query"], $details["post_title"]);
                },
            ],
        ]);
        
        $this->register_grid_template("ajf-team", [
            "count" => -1,
            "class" => "team-grid",
            "get_details" => function ($id) {
                $details = [
                    "id" => $id,
                    "photo" => get_field("photo", $id),
                    "name" => get_the_title($id),
                    "position" => get_field("position", $id),
                    "bio" => apply_filters('the_content', get_the_content(null, false, $id)),
                ];
        
                return $details;
            },
            "render" => function ($details) {
                return '
                    <a class="archive-item team" data-fancybox data-src="#team-bio-' . $details["id"] . '" data-touch="false" data-auto-focus="false" href="javascript:;">
                        <div class="photo-container">
                            <div class="photo" style="background-image: url(' . $details["photo"] . ')"></div>
                        </div>
                        <div class="details">
                            <div class="name">' . $details["name"] . '</div>
                            <div class="position">' . $details["position"] . '</div>
                        </div>
                        <div class="bio-popup texture" id="team-bio-' . $details["id"] . '" style="display: none;">
                            <div class="bio-popup-inner">
                                <div class="left-column">
                                    <div class="photo-container">
                                        <div class="photo" style="background-image: url(' . $details["photo"] . ')"></div>
                                    </div>
                                </div>
                                <div class="right-column">
                                    <button class="close-button" onClick="jQuery.fancybox.close();">Close</button>
                                    <div class="details">
                                        <h3 class="name">' . $details["name"] . '</h3>
                                        <div class="position">' . $details["position"] . '</div>
                                        <br />
                                    </div>
                                    <div class="bio">' . $details["bio"] . '</div>
                                </div>
                            </div>
                        </div>
                    </a>
                ';
            },
        ]);
        
        $this->register_filters_template("ajf-team", [
            "query" => [
                "name" => "Search",
                "type" => "text",
                "matches" => function ($atts, $details) {
                    return $this->match_contains($atts["query"], $details["name"]);
                },
            ],
        ]);
    }
        
    /**
     * init_rest_url
     *
     * @return void
     */
    function init_footer_config()
    {
        ?>
        <script>
            window.ajf_rest_url = '<?=get_rest_url(null, $this->rest_route . '/v' . $this->version);?>';
        </script>
        <?php
    }
}


$AJF = new AJF_Instance();
global $AJF;

// Add AJF Methods to the global scope
function register_grid($grid_type, $default_data = [], $template_config = [])
{
    global $AJF;
    return $AJF->register_grid($grid_type, $default_data, $template_config);
}
function register_grid_template($grid_type, $default_data = [])
{
    global $AJF;
    return $AJF->register_grid_template($grid_type, $default_data);
}
function register_filters($grid_type, $filters)
{
    global $AJF;
    return $AJF->register_filters($grid_type, $filters);
}
function register_filters_template($grid_type, $filters)
{
    global $AJF;
    return $AJF->register_filters_template($grid_type, $filters);
}
function get_grid_data($grid_type, $atts = [])
{
    global $AJF;
    return $AJF->get_grid_data($grid_type, $atts);
}
function match_exact($needle = null, $haystack = null)
{
    global $AJF;
    return $AJF->match_exact($needle, $haystack);
}

function match_contains($needle = null, $haystack = null)
{
    global $AJF;
    return $AJF->match_contains($needle, $haystack);
}

function match_like($needle = null, $haystack = null)
{
    global $AJF;
    return $AJF->match_like($needle, $haystack);
}