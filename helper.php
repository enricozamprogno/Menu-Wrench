<?php defined('_JEXEC') or die;

/**
 * File       helper.php
 * Created    8/6/13 3:41 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/Menu-Wrench/issues
 * Copyright  Copyright (C) 2013 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v3 or later
 */

jimport('joomla.application.menu');

class modMenuwrenchHelper {

	/**
	 * Constructor
	 *
	 * @param JRegistry $params: module parameters
	 * @since 0.1
	 *
	 */
	public function __construct($params) {
		$this->app    = JFactory::getApplication();
		$this->menu   = $this->app->getMenu();
		$this->active = $this->menu->getActive();
		$this->params = $params;
	}

	/**
	 * Retrieves all menu items, sorts, combines, mixes, stirs, and purges what we want in a logical order
	 *
	 * @return mixed
	 * @since 0.1
	 *
	 */
	function getBranches() {
		$parentItems  = $this->params->get('parentItems');
		$showChildren = $this->params->get('showChildren');
		// http://stackoverflow.com/questions/3787669/how-to-get-specific-menu-items-from-joomla/10218419#10218419
		$items = $this->menu->getItems(NULL, NULL);

		// Convert parentItems to an array if only one item is selected
		if (!is_array($parentItems)) {
			$parentItems = str_split($parentItems, strlen($parentItems));
		}

		/**
		 * Builds menu hierarchy by nesting children in parent object's 'children' property.
		 * First builds an item Id based array, then discards old nodes.
		 */
		foreach ($items as $key => $item) {

			$items[$item->id] = $item;

			unset($items[$key]);

			if ($item->parent_id != 1) {
				$items[$item->parent_id]->children[$item->id] = $item;
			}
		}

		foreach ($items as $key => $item) {

			/**
			 * Remove non-selected menu item objects
			 * At this point, all selected items to render are in the first level of the array
			 */
			if (!in_array($key, $parentItems)) {
				unset($items[$key]);
			}

			/**
			 * Builds object classes
			 */
			$item->class = 'item' . $item->id . ' ' . $item->alias;

			// Add parent class to all parents
			if (isset($item->children)) {
				$item->class .= ' parent';
			}

			// Add current class to specific item
			if ($item->id == $this->active->id) {
				$item->class .= ' current';
			}

			// Add active class to all items in active branch
			if (in_array($item->id, $this->active->tree)) {
				$item->class .= ' active';
			}

			// Hide sub-menu items if parameter set to no and parent not active
			if (!in_array($item->id, $this->active->tree) && $showChildren == 0) {
				unset($item->children);
			}
		}

		return $items;
	}

	/**
	 * Renders the menu
	 *
	 * @param $item                 : the menu item
	 * @param string $containerTag  : optional, declare a different container HTML element
	 * @param string $containerClass: optional, declare a different container class
	 * @param string $itemTag       : optional, declare a different menu item HTML element
	 * @param int $level            : counter for level of depth that is rendering.
	 * @return string
	 *
	 * @since 0.1
	 */

	public function render($item, $containerTag = '<ul>', $containerClass = 'menu', $itemTag = '<li>', $level = 0) {

		$itemOpenTag       = str_replace('>', ' class="' . $item->class . '">', $itemTag);
		$itemCloseTag      = str_replace('<', '</', $itemTag);
		$containerOpenTag  = str_replace('>', ' class="' . $containerClass . '">', $containerTag);
		$containerCloseTag = str_replace('<', '</', $containerTag);
		$depth             = htmlspecialchars($this->params->get('depth'));

		if ($item->type == 'separator') {
			$output = $itemOpenTag . '<span class="separator">' . $item->name . '</span>';
		} else {
			$output = $itemOpenTag . '<a href="' . JRoute::_($item->link . '&Itemid=' . $item->id) . '"/>' . $item->title . '</a>';
		}

		$level++;

		if (isset($item->children) && $level <= $depth) {

			$output .= $containerOpenTag;

			foreach ($item->children as $item) {

				$output .= $this->render($item, $containerTag, $containerClass, $itemTag, $level);
			}
			$output .= $itemCloseTag;
			$output .= $containerCloseTag;
		}

		$output .= $itemCloseTag;

		return $output;
	}
}
