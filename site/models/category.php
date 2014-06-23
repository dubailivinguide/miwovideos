<?php
/**
 * @package        MiwoVideos
 * @copyright      Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license        GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die;

class MiwovideosModelCategory extends MiwovideosModel {

	public function __construct() {
		parent::__construct('category', 'videos');

		$this->_buildViewQuery();
	}

	public function _buildViewQuery() {
		$where = $this->_buildViewWhere();

		if ($this->config->get('order_videos') == 2) {
			$orderby = ' ORDER BY v.created ';
		}
		else {
			$orderby = ' ORDER BY v.ordering ';
		}

		$this->_query = 'SELECT v.* '
		                .' FROM #__miwovideos_videos AS v '
		                .$where
		                .' GROUP BY v.id '
		                .$orderby;
	}

	public function _buildViewWhere() {
		$category_id = MRequest::getInt('category_id');

		$where = array();

		$where[] = 'v.published = 1';
		$where[] = 'v.access IN ('.implode(',', MFactory::getUser()->getAuthorisedViewLevels()).')';

		if ($this->_mainframe->getLanguageFilter()) {
			$where[] = 'v.language IN ('.$this->_db->Quote(MFactory::getLanguage()->getTag()).','.$this->_db->Quote('*').')';
		}

		if ($category_id) {
			$where[] = 'v.id IN (SELECT video_id FROM #__miwovideos_video_categories WHERE category_id='.$category_id.')';
		}

		$where[] = 'DATE(v.created) <= CURDATE()';


		$where = (count($where) ? ' WHERE '.implode(' AND ', $where) : '');

		return $where;
	}

	public function getVideos() {
		if (empty($this->_data)) {
			$this->_data = parent::getItems();
		}

		return $this->_data;
	}

	public function getTotal() {
		if (!empty($this->_total)) {
			return $this->_total;
		}

		$c_id = MRequest::getCmd('category_id');
		if (empty($c_id)) {
			if (MiwoVideos::getConfig()->get('show_empty_cat')) {
				$this->_total = MiwoDB::loadResult("SELECT COUNT(*) FROM #__miwovideos_categories AS c".$this->_buildCategoriesWhere());
			}
			else {
				$this->_total = MiwoDB::loadResult("SELECT COUNT(*) FROM #__miwovideos_categories AS c RIGHT JOIN #__miwovideos_video_categories AS vc ON (c.id = vc.category_id)".$this->_buildCategoriesWhere());
			}
		}
		else {
			$this->_total = MiwoDB::loadResult("SELECT COUNT(*) FROM #__miwovideos_{$this->_table} AS v".$this->_buildViewWhere());
		}

		return $this->_total;
	}

	public function _buildCategoriesWhere() {
		$category_id = MiwoVideos::getInput()->getInt('category_id');

		$where = array();

		$where[] = 'c.id <> 1';
		if (!empty($category_id)) {
			$where[] = 'c.parent = '.$category_id;
		}
		else {
			$where[] = 'c.parent = 0';
		}
		$where[] = 'c.access IN ('.implode(',', MFactory::getUser()->getAuthorisedViewLevels()).')';
		$where[] = 'c.published = 1';

		if ($this->_mainframe->getLanguageFilter()) {
			$where[] = 'c.language IN ('.$this->_db->Quote(MFactory::getLanguage()->getTag()).','.$this->_db->Quote('*').')';
		}

		$where = (count($where) ? ' WHERE '.implode(' AND ', $where) : '');

		return $where;
	}

	public function getCategories() {
		$c_id = MRequest::getCmd('category_id');
		if (empty($c_id)) {
			$rows = MiwoDB::loadObjectList($this->_buildCategoriesQuery(), '', $this->getState($this->_option.'.'.$this->_context.'.limitstart'), $this->getState($this->_option.'.'.$this->_context.'.limit'));
		}
		else {
			$rows = MiwoDB::loadObjectList($this->_buildCategoriesQuery());
		}

		foreach ($rows as $row) {
			$row->total_categories = MiwoDB::loadResult('SELECT COUNT(*) FROM #__miwovideos_categories WHERE parent = '.$row->id.' AND published = 1');
			$row->total_videos     = MiwoVideos::get('videos')->getTotalVideosByCategory($row->id);
		}

		return $rows;
	}

	public function _buildCategoriesQuery() {
		$where = $this->_buildCategoriesWhere();

		if (MiwoVideos::getConfig()->get('show_empty_cat')) {
			$query = 'SELECT c.* FROM #__miwovideos_categories AS c '.$where.' ORDER BY c.ordering';
		}
		else {
			$query = 'SELECT DISTINCT c.* FROM #__miwovideos_categories AS c RIGHT JOIN #__miwovideos_video_categories AS vc ON (c.id = vc.category_id) '.$where.' ORDER BY c.ordering';
		}

		return $query;
	}
} 