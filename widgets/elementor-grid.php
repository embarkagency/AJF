<?php


/**
 * Elementor AJF Grid Widget.
 *
 * Elementor widget that inserts an AJF Grid (AJAX filterable Grid) onto the page.
 *
 * @since 1.0.0
 */
class Elementor_AJF_Grid_Widget extends \Elementor\Widget_Base {

    public static $slug = 'ajf-grid';

	public function __construct($data = [], $args = null) {
		parent::__construct($data, $args);
		wp_register_script( 'ajf-elementor-js', plugins_url('../js/ajf-elementor.js', __FILE__), [ 'elementor-frontend' ], '1.0.0', true );
	}
  
	public function get_script_depends() {
		return [ 'ajf-elementor-js' ];
	}

	/**
	 * Get widget name.
	 *
	 * Retrieve AJF widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return self::$slug;
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve AJF widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'AJF Grid', self::$slug );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve AJF widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'fad fa-th';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the AJF widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'general' ];
	}

	/**
	 * Register AJF widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls() {
        global $AJF;

		$this->start_controls_section('content_section', [
			'label' => __( 'Configuration', self::$slug ),
			'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
		]);

		//Grid Slug (text)
		//Source (select) (post_type, external_api)
		//


		$this->add_control('source', [
			'label' => __( 'Source', self::$slug ),
			'type' => \Elementor\Controls_Manager::SELECT,
			'default' => 'post',
			'options' => get_post_types([], 'names'),
		]);

		$this->add_control('count', [
			'label' => __( 'Count', self::$slug ),
			'type' => \Elementor\Controls_Manager::NUMBER,
			'min' => -1,
			'max' => 100,
			'step' => 1,
			'default' => 10,
		]);

		$this->add_control('pagination', [
			'label' => __( 'Pagination', self::$slug ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'label_on' => __( 'Show', self::$slug ),
			'label_off' => __( 'Hide', self::$slug ),
			'return_value' => 'yes',
			'default' => 'yes',
		]);

		$this->add_control('has_nav', [
			'label' => __( 'Prev/Next', self::$slug ),
			'type' => \Elementor\Controls_Manager::SWITCHER,
			'label_on' => __( 'Show', self::$slug ),
			'label_off' => __( 'Hide', self::$slug ),
			'return_value' => 'yes',
			'default' => 'yes',
		]);

		$this->end_controls_section();

	}

	/**
	 * Render AJF widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
        global $AJF;

		$settings = $this->get_settings_for_display();

        if(isset($settings['source']) && !empty($settings['source'])){
			$source = $settings['source'];
			$grid_type = $source . '-elementor';
			unset($settings['source']);

			$config = ['data' => $source];
			
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

			$config["render"] = function ($details) {
                return $details["post_title"] . "<br />";
            };

			// echo '<pre>' . var_export($settings, true) . '</pre>';

			$AJF->register_grid($grid_type, $config);
			$AJF->trigger_init($grid_type);

            echo do_shortcode('[' . $grid_type . '-grid]');
        }
	}

}