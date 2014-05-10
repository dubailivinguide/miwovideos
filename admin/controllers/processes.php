<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright  ( C ) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die ;

class MiwovideosControllerProcesses extends MiwoVideosController {
	
	public function __construct($config = array()) {
		parent::__construct('processes');
	}
	
	public function process() {
		# Check token
		MRequest::checkToken() or mexit('Invalid Token');
		
		$cid = MRequest::getVar('cid', array(), 'post');
		$ret = false;

		foreach ($cid as $id) {
			$exists = $this->_model->getSuccessful($id);
			if ($exists) {
				$ids[] = $id;
				continue;
			}
            $ret = MiwoVideos::get('processes')->run($id);
		}

		if (isset($ids) and count($ids) > 0) {
			MError::raiseNotice(100, MText::sprintf('COM_MIWOVIDEOS_ALREADY_PROCESSED', implode(',', $ids)));
		}

        if ($ret) {
            $this->_mainframe->enqueueMessage(MText::_('COM_MIWOVIDEOS_RECORD_PROCESSED'));
        } else {
            MError::raiseError(100, MText::_('COM_MIWOVIDEOS_PROCESS_FAILED'));
        }

        $this->setRedirect('index.php?option='.$this->_option.'&view='.$this->_context);
		
	}
	
	public function processAll() {
		$this->process();
	}
}