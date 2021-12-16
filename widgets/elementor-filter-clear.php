<?php

require_once(plugin_dir_path( __FILE__ ) . '/elementor-filter.php');

class Elementor_AJF_Filter_Clear_Widget extends Elementor_AJF_Filter_Widget {

	public function __construct($data = [], $args = null) {

		$this->filter_type = 'clear';
		$this->default_label = 'Clear Selected Filters';
		$this->default_slug = 'clear';
		$this->hide_slug = true;

		parent::__construct($data, $args);
	}
	
	public function add_filter_controls() {
		
	}
}