<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright  ( C ) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die ;

class MiwovideosModelChannel extends MiwovideosModel {

    public function __construct() {
		parent::__construct('channel', 'channels');

        $this->_getUserStates();
        $this->_buildViewQuery();
	}

    public function _getUserStates() {
        $this->filter_order			= parent::_getSecureUserState($this->_option . '.' . $this->_context . '.filter_order',			'filter_order',			'v.title',	'string');
        $this->filter_order_Dir		= parent::_getSecureUserState($this->_option . '.' . $this->_context . '.filter_order_Dir',		'filter_order_Dir',		'DESC',     'word');
        /* Filter fix */
        //$filter_videos = $this->_mainframe->getUserStateFromRequest('com_miwovideos.history.filter_videos',		'filter_videos',	        'uploads',		    'string');
        //if ($filter_videos == 'uploads') {
            $this->filter_order = str_replace('created_on', 'v.created', $this->filter_order);
            if ($this->filter_order == 'v.created') {
                $this->filter_order_Dir = 'ASC';
            }
            $this->filter_order = str_replace('title_az', 'v.title', $this->filter_order);
            if ($this->filter_order == 'v.title') {
                $this->filter_order_Dir = 'ASC';
            }
            $this->filter_order = str_replace('created_no', 'v.created' , $this->filter_order);
            $this->filter_order = str_replace('title_za', 'v.title' , $this->filter_order);
        //}
        /* Filter fix */
        $this->search				= parent::_getSecureUserState($this->_option . '.' . $this->_context . '.miwovideos_search', 	'miwovideos_search', 	'',         'string');
        $this->search 	 			= MString::strtolower($this->search);
    }

    public function getItem() {
        $channel_id = MRequest::getInt('channel_id');

        $row = MiwoVideos::getTable('MiwovideosChannels');
        $row->load($channel_id);

        $row->subs = MiwoVideos::get('model')->getSubscriberCount($channel_id);

        return $row;
    }

    public function getChannelVideos() {
        $user = MFactory::getUser();
        $nullDate = $this->_db->getNullDate();

        $query = $this->_buildChannelVideosQuery();
        $this->_db->setQuery($query);

        $rows = MiwoDB::loadObjectList($query, '', $this->getState('limitstart'), $this->getState('limit'));
		
		return $rows;
	}

    public function getChannelVideosPagination() {
        mimport('framework.html.pagination');
        $pagination = new MPagination($this->getChannelVideosTotal(), $this->getState($this->_option.'.' . $this->_context . '.limitstart'), $this->getState($this->_option.'.' . $this->_context . '.limit'));

        return $pagination;
    }

    public function getChannelVideosTotal() {
        $total = MiwoDB::loadResult('SELECT COUNT(*) FROM #__miwovideos_videos AS v '.$this->_buildChannelVideosWhere());

        return $total;
    }

    public function _buildChannelVideosQuery() {
		$where = $this->_buildChannelVideosWhere();

        if ($this->config->get('order_videos') == 2) {
            $orderby = ' ORDER BY v.created ';
        }
        else {
            $orderby = ' ORDER BY v.ordering ';
        }

        if (!empty($this->filter_order) and !empty($this->filter_order_Dir)) {
            $orderby = " ORDER BY {$this->filter_order} {$this->filter_order_Dir}";
        }

        $query = 'SELECT v.*'
                . ' FROM  #__miwovideos_videos AS v '
			    . ' LEFT JOIN #__miwovideos_channels AS c '
			    . ' ON (c.id = v.channel_id)'
			    . $where
			    . ' GROUP BY v.id '
			    . $orderby
		        ;

		return $query;
	}

    public function _buildChannelVideosWhere() {
		$db = MFactory::getDbo();
		$user = MFactory::getUser();
        $app = MFactory::getApplication();
        $channel_id = MRequest::getInt('channel_id', 0);

		$where = array();

		$where[] = 'v.published = 1';
		$where[] = 'v.access IN ('.implode(',', $user->getAuthorisedViewLevels()).')';

		if ($channel_id) {
			$where[] = 'v.channel_id = ' .$channel_id;
		}

        $where[] = 'DATE(v.created) <= CURDATE()';

		if ($app->getLanguageFilter()) {
			$where[] = 'v.language IN (' . $db->Quote(MFactory::getLanguage()->getTag()) . ',' . $db->Quote('*') . ')';
		}

        if (!empty($this->search)) {
            $src = parent::secureQuery($this->search, true);
            $where[] = "(LOWER(v.title) LIKE {$src} OR LOWER(v.introtext) LIKE {$src})";
        }

		$where = (count($where) ? ' WHERE '. implode(' AND ', $where) : '');
				
		return $where;
	}
} 