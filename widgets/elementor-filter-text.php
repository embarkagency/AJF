<?php

require_once(plugin_dir_path( __FILE__ ) . '/elementor-filter.php');

class Elementor_AJF_Filter_Text_Widget extends Elementor_AJF_Filter_Widget {

	public function __construct($data = [], $args = null) {

		$this->default_label = 'Search';
		$this->filter_type = 'text';
		$this->default_slug = 'query';

		parent::__construct($data, $args);
	}
	
	public function add_filter_controls() {
		$this->add_control('placeholder', [
			'label' => __( 'Placeholder', self::$slug ),
			'type' => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Enter keywords here...', self::$slug ),
			'placeholder' => __( 'Enter keywords here...', self::$slug ),
		]);
	}
}