<?php
/**
 * Loader class for Zeko QA
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Class Zeko_QA_Loader. */
class Zeko_QA_Loader {

	/**
	 * Actions.
	 *
	 * @var mixed Actions.
	 */
	private $actions = array();
	/**
	 * Filters.
	 *
	 * @var mixed Filters.
	 */
	private $filters = array();

	/**
	 * Add action.
	 *
	 * @param mixed     $hook Hook.
	 * @param mixed     $component Component.
	 * @param mixed     $callback Callback.
	 * @param int|float $priority Priority.
	 * @param int|float $accepted_args Accepted args.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return true;
	}

	/**
	 * Add filter.
	 *
	 * @param mixed     $hook Hook.
	 * @param mixed     $component Component.
	 * @param mixed     $callback Callback.
	 * @param int|float $priority Priority.
	 * @param int|float $accepted_args Accepted args.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		return true;
	}

	/**
	 * Run.
	 */
	public function run() {
		foreach ( $this->actions as $action ) {
			add_action( $action['hook'], $action['callback'], $action['priority'], $action['accepted_args'] );
		}
		foreach ( $this->filters as $filter ) {
			add_filter( $filter['hook'], $filter['callback'], $filter['priority'], $filter['accepted_args'] );
		}
	}
}
