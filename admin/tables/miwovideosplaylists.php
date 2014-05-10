<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright  ( C ) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die('Restricted Access');

class TableMiwovideosPlaylists extends MTable {

    public $id 	 		    	= 0;
    public $channel_id 	 		= 0;
    public $user_id 	 		= 0;
    public $type 	 			= 0;
    public $title 			    = '';
    public $alias			    = '';
    public $introtext			= '';
    public $fulltext			= '';
    public $thumb			    = null;
    public $fields	 			= null;
    public $likes	 			= 0;
    public $dislikes	 		= 0;
    public $hits	 			= 0;
    public $subscriptions	 	= 0;
    public $params	 			= '';
    public $ordering		    = 0;
    public $access		        = 1;
    public $language			= '*';
    public $created				= null;
    public $modified			= null;
	public $meta_desc 			= '';
    public $meta_key			= '';
    public $meta_author			= '';
    public $share_others		= 0;
    public $featured			= 0;
    public $published		    = 1;
    

	public function __construct($db) {
		parent::__construct('#__miwovideos_playlists', 'id', $db);
	}
	
	public function check() {
        # Set title
        $this->title = htmlspecialchars_decode($this->title, ENT_QUOTES);

        # Set alias
        $this->alias = MApplication::stringURLSafe($this->alias);
        if (empty($this->alias)) {
            $this->alias = MApplication::stringURLSafe($this->title);
        }

        # Description Exploding
        $delimiter = "<hr id=\"system-readmore\" />";

        if(strpos($this->introtext, $delimiter) == true){
            $exp = explode($delimiter, $this->introtext);
            $this->introtext	= $exp[0];
            $this->fulltext		= $exp[1];
        } else {
            $this->fulltext		= "";
        }

        return true;
    }
}