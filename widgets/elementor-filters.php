<?php


/**
 * Elementor AJF Filters Widget.
 *
 * Elementor widget that inserts AJF Filters (AJAX filterable Grid) onto the page.
 *
 * @since 1.0.0
 */
class Elementor_AJF_Filters_Widget extends \Elementor\Widget_Base {

    public static $slug = 'ajf-filters';

	public function __construct($data = [], $args = null) {
		parent::__construct($data, $args);
		wp_register_script( 'ajf-elementor-js', plugins_url('../js/ajf-elementor.js', __FILE__), [ 'elementor-frontend' ], '1.0.0', true );
		wp_register_style( 'ajf-elementor-filters-css', plugins_url( '../css/ajf-widget-filters.css', __FILE__ ) );
	}
  
	public function get_script_depends() {
		return [ 'ajf-elementor-js' ];
	}

	public function get_style_depends() {
		return [ 'ajf-elementor-filters-css' ];
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
		return __( 'AJF Filters', self::$slug );
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
		return 'fad fa-filter';
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

		$this->start_controls_section('config_section', [
			'label' => __( 'Configuration', self::$slug ),
			'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
		]);
		
		$this->add_control('source', [
			'label' => __( 'Source', self::$slug ),
			'type' => \Elementor\Controls_Manager::SELECT,
			'default' => 'post',
			'options' => $AJF->get_post_sources(),
		]);

		$this->add_control('unique_id', [
			'label' => __( 'Unique ID', self::$slug ),
			'type' => \Elementor\Controls_Manager::TEXT,
			'default' => __( '', self::$slug ),
			'placeholder' => __( '', self::$slug ),
			'description' => 'This should be the same as the ID of the AJF Grid these filters should control.',
		]);

		$this->add_control('filter_type', [
			'label' => __( 'Filter Type', self::$slug ),
			'type' => \Elementor\Controls_Manager::HIDDEN,
			'default' => 'text',
		]);

		$this->add_control('name', [
			'label' => __( 'Label', self::$slug ),
			'type' => \Elementor\Controls_Manager::TEXT,
			'default' => __( '', self::$slug ),
			'placeholder' => __( '', self::$slug ),
		]);

		$this->add_control('slug', [
			'label' => __( 'Slug', self::$slug ),
			'type' => \Elementor\Controls_Manager::TEXT,
			'default' => __( '', self::$slug ),
			'placeholder' => __( '', self::$slug ),
			'description' => 'Unique slug for filters, primary used for the URL.',
		]);

		$this->add_control('placeholder', [
			'label' => __( 'Placeholder', self::$slug ),
			'type' => \Elementor\Controls_Manager::TEXT,
			'default' => __( '', self::$slug ),
			'placeholder' => __( '', self::$slug ),
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
		$shortcode = $AJF->register_filters_widget($settings, true);

		if($shortcode) {
			echo do_shortcode($shortcode);
		}
	}

}