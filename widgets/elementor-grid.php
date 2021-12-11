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

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', self::$slug ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'grid_type',
			[
				'label' => __( 'Select Grid', 'plugin-domain' ),
				'type' => \Elementor\Controls_Manager::SELECT2,
				'multiple' => false,
				'options' => $AJF->get_grids(),
				'default' => [],
			]
		);

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

        if(isset($settings['grid_type']) && !empty($settings['grid_type'])){
            echo do_shortcode('[' . $settings['grid_type'] . '-grid]');
        }

        // echo '<pre>' . var_export($settings, true) . '</pre>';

	}

}