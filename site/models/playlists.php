<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright  ( C ) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die ;

class MiwovideosModelPlaylists extends MiwovideosModel {

    public function __construct() {
		parent::__construct('playlists');

        $this->_getUserStates();
        $this->_buildViewQuery();
	}

    public function _getUserStates() {
        $this->filter_order			= parent::_getSecureUserState($this->_option . '.' . $this->_context . '.filter_order',			'filter_order',			'p.title',	'cmd');
        $this->filter_order_Dir		= parent::_getSecureUserState($this->_option . '.' . $this->_context . '.filter_order_Dir',		'filter_order_Dir',		'DESC',     'word');
        /* Filter fix */
        //$filter_videos = $this->_mainframe->getUserStateFromRequest($this->_option . '.' . $this->_context . '.filter_videos',		'filter_videos',	        'uploads',		    'string');
        //if ($filter_videos == 'playlists') {
            $this->filter_order = str_replace('created_on', 'p.created', $this->filter_order);
            if ($this->filter_order == 'p.created') {
                $this->filter_order_Dir = 'ASC';
            }
            $this->filter_order = str_replace('title_az', 'p.title', $this->filter_order);
            if ($this->filter_order == 'p.title') {
                $this->filter_order_Dir = 'ASC';
            }
            $this->filter_order = str_replace('created_no', 'p.created', $this->filter_order);
            $this->filter_order = str_replace('title_za', 'p.title', $this->filter_order);
        //}
        /* Filter fix */
        $this->search				= parent::_getSecureUserState($this->_option . '.' . $this->_context . '.miwovideos_search', 	'miwovideos_search', 	'',         'string');
        $this->search 	 			= MString::strtolower($this->search);
    }

    public function _buildViewQuery() {
        $where = $this->_buildViewWhere();

        $orderby = "";
        if (!empty($this->filter_order) and !empty($this->filter_order_Dir)) {
            $orderby = " ORDER BY {$this->filter_order} {$this->filter_order_Dir}";
        }

	    $this->_query = "SELECT
                    p.*,
                    c.id channel_id, c.title channel_title
                FROM (SELECT DISTINCT psub.* FROM #__miwovideos_playlist_videos pv LEFT JOIN #__miwovideos_playlists psub ON (pv.playlist_id = psub.id)) p
                LEFT JOIN #__miwovideos_channels c ON (c.id = p.channel_id)" .$where.$orderby;
    }

    public function getTotal() {
        if (empty($this->_total)) {
	        $this->_total = MiwoDB::loadResult("SELECT COUNT(*)
						FROM (SELECT DISTINCT psub.*
							    FROM #__miwovideos_playlist_videos pv
							    LEFT JOIN #__miwovideos_playlists psub ON (pv.playlist_id = psub.id)) p
                        LEFT JOIN #__miwovideos_channels c ON (c.id = p.channel_id)".$this->_buildViewWhere());
        }

        return $this->_total;
    }

    public function _buildViewWhere() {
        $where = array();
        $user = MFactory::getUser();
        $user_channel_id = MiwoVideos::get('channels')->getDefaultChannel()->id;

        $channel_id = MRequest::getInt('channel_id', null);
        $video_id = MRequest::getInt('video_id', null);

        $where[] = 'p.published = 1';
        $where[] = 'p.type = 0';
        $where[] = 'p.access IN ('.implode(',', $user->getAuthorisedViewLevels()).')';

        if ($this->_mainframe->getLanguageFilter()) {
            $where[] = 'p.language IN (' . $this->_db->Quote(MFactory::getLanguage()->getTag()) . ',' . $this->_db->Quote('*') . ')';
        }

        if (!empty($this->search)) {
            $src = parent::secureQuery($this->search, true);
            $where[] = "(LOWER(p.title) LIKE {$src} OR LOWER(p.introtext) OR LOWER(p.fulltext) LIKE {$src})";
        }

        if(!empty($channel_id)) { //Channel Page
            $where[] = "channel_id = ".$channel_id;
            if($channel_id === (int)$user_channel_id) {
                unset($where[1]); //p.type = 0
            }
        }

        if(!empty($video_id)) { // Video page
            $where[] = "p.user_id = ".$user->id;
            unset($where[1]); //p.type = 0
        }
		
		$where[] = 'DATE(p.created) <= CURDATE()';

        $where = (count($where) ? ' WHERE '. implode(' AND ', $where) : '');

        return $where;
    }

    public function getItems() {
        $rows = parent::getItems();
        foreach ($rows as $row) {
            $row->total = $this->_totalPlaylistVideos($row->id);
            $row->videos = $this->_playlistVideos($row->id);
        }

        return $rows;
    }

    public function _playlistVideos($playlist_id) {
        $result = MiwoDB::loadObjectList("SELECT v.id video_id, v.title, pv.playlist_id, v.duration, v.thumb
                        FROM #__miwovideos_videos v
                        LEFT JOIN #__miwovideos_playlist_videos pv ON (pv.video_id=v.id) WHERE playlist_id = {$playlist_id}");

        return $result;
    }

    public function getChannelPlaylists() {
        $rows = MiwoDB::loadObjectList($this->_query, '', $this->getState('limitstart'), $this->getState('limit'));

        foreach ($rows as $row) {
            $row->total = $this->_totalPlaylistVideos($row->id);
            $row->videos = $this->_playlistVideos($row->id);
        }

        return $rows;
    }

    public function _totalPlaylistVideos($playlist_id) {
        $total = MiwoDB::loadResult("SELECT COUNT(*) FROM #__miwovideos_playlist_videos WHERE playlist_id = {$playlist_id}");

        return $total;
    }

    public function addVideoToPlaylist($playlist_id, $video_id, $ordering) {
        if ($ordering === "on") {
            MiwoDB::query("UPDATE #__miwovideos_playlist_videos SET ordering = ordering + 1 WHERE playlist_id = {$playlist_id}");
        }
        $ordering = 0;

        MiwoDB::query("INSERT INTO #__miwovideos_playlist_videos (playlist_id, video_id, ordering) VALUES ({$playlist_id}, {$video_id}, {$ordering})");

        return true;
    }

    public function removeVideoToPlaylist($playlist_id, $video_id) {
        MiwoDB::query("DELETE FROM #__miwovideos_playlist_videos WHERE playlist_id = {$playlist_id} AND video_id = {$video_id}");

        return true;
    }
}