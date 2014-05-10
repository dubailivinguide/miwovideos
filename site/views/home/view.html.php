<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright  ( C ) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die ;

class MiwovideosViewHome extends MiwovideosView {

    public function display($tpl = null) {
        $this->params = $this->_mainframe->getParams();
      $this->widgets = array();

	    $modules = MModuleHelper::getModules();
	    $renderer = MFactory::getDocument()->loadRenderer('module');

	    foreach ($modules as $module) {
		    $params = new MRegistry();
		    $params->loadString($module->params);
		    if ($params->get('position', null) === "0") {
			    ob_start();
			    echo $renderer->render($module);
			    switch ($module->id) {
				    case $module->id == 'mod_miwovideos_videos_featured';
				        $this->widget_top = ob_get_contents();
					    break;
				    case $module->id == 'mod_miwovideos_videos_latest';
				        $this->widget_left = ob_get_contents();
					    break;
				    case $module->id == 'mod_miwovideos_videos_popular';
				        $this->widget_right = ob_get_contents();
					    break;
			    }
			    ob_end_clean();
		    }
	    }

        parent::display($tpl);
    }
}