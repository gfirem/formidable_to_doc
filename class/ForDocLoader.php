<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ForDocLoader {
	protected $actions;
	protected $filters;

	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Add action to WP
	 *
	 * @param $hook
	 * @param $component
	 * @param $callback
	 * @param $priority
	 * @param $params
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $params = 1) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $params );
	}

	/**
	 * Add filter to WP
	 *
	 * @param $hook
	 * @param $component
	 * @param $callback
	 * @param $priority
	 * @param $params
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $params = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $params );
	}


	private function add( $hooks, $hook, $component, $callback, $priority, $params ) {
		$hooks[] = array(
			'hook'      => $hook,
			'component' => $component,
			'callback'  => $callback,
			'priority'  => $priority,
			'params'  => $params,
		);

		return $hooks;
	}

	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['params'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['params'] );
		}
	}
}