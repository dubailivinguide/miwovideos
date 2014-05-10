<?php
/*
* @package		MiwoVideos
* @copyright	Copyright  ( C ) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/
# No Permission
defined('MIWI') or die ('Restricted access');

# Controller Class
class MiwovideosControllerUpload extends MiwovideosController {

    # Main constructer
    public function __construct() {
        parent::__construct('upload');
    }

    public function upload() {
        $upload = MiwoVideos::get('upload');

        $dashboard = '';
        if (MiwoVideos::isDashboard()) {
            $dashboard = '&dashboard=1';
        }

        // Add embed code
        if (!$upload->process()) {
            if (MRequest::getWord('format') != 'raw') {
                MError::raiseWarning(500, $upload->getError());
                $this->setRedirect('index.php?option=com_miwovideos&view=upload'.$dashboard);
            }
            else {
                $result = array(
                    'status' => '0',
                    'error' => $upload->getError(),
                    'code' => 0
                );
            }
        }
        else {
            if (MRequest::getWord('format') != 'raw') {
                $this->_mainframe->enqueueMessage(MText::sprintf('COM_MIWOVIDEOS_SUCCESSFULLY_UPLOADED_X', $upload->_title));
                $this->setRedirect('index.php?option=com_miwovideos&view=videos&task=edit&cid[]=' . $upload->_id.$dashboard);
            }
            else {
                $result = array(
                    'success' => 1,
                    'id' => $upload->_id,
                    'href' => MiwoVideos::get('utility')->route('index.php?option=com_miwovideos&view=videos&task=edit&cid[]=' . $upload->_id.$dashboard),
                    'filename' => $upload->_filename
                );
            }
        }

        echo json_encode($result);
    }

    public function uberUpload() {
        $upload = MiwoVideos::get('upload');

        $dashboard = '';
        if (MiwoVideos::isDashboard()) {
            $dashboard = '&dashboard=1';
        }

        // Add embed code
        if (!$upload->uber()) {
            MError::raiseWarning(500, $upload->getError());
            $this->setRedirect('index.php?option=com_miwovideos&view=videos'.$dashboard);
        }
        else {
            $this->_mainframe->enqueueMessage(MText::sprintf('COM_MIWOVIDEOS_SUCCESSFULLY_UPLOADED_X', $upload->_title));
            $this->setRedirect('index.php?option=com_miwovideos&view=videos&task=edit&cid[]=' . $upload->_id.$dashboard);
        }

        return $upload;
    }

    public function link_upload() {
        header('Content-type: text/javascript');
        MiwoVideos::get('uber.ubr_link_upload');
        return;
    }

    public function set_progress() {
        header('Content-type: text/javascript');
        MiwoVideos::get('uber.ubr_set_progress');
        return;
    }

    public function get_progress() {
        header('Content-type: text/javascript');
        MiwoVideos::get('uber.ubr_get_progress');
        return;
    }

    public function convertToHtml5() {
        $config = MiwoVideos::getConfig();
	    $video_id = MRequest::getInt('video_id');
	    $filename =  MRequest::getString('filename');

	    if (!$config->get('auto_process_video')) {
		    MiwoVideos::get('videos')->convertToHtml5($video_id, $filename);
	    }
	    else {
		    $cli = MPATH_MIWI.'/cli/miwovideoscli.php';
		    if (substr(PHP_OS, 0, 3) != "WIN") {
			    // @TODO Log if throw an error
			    @exec("env -i " . $this->config->get('php_path', '/usr/bin/php') . " $cli convertToHtml5 " . $video_id . " " . $filename . " > /dev/null 2>&1  &", $output, $error);
		    }
		    else {
			    @exec('where php.exe', $php_path);
			    // @TODO Log if throw an error
			    @exec($config->get('php_path', $php_path)." $cli convertToHtml5 " . $video_id . " " . $filename . " NUL", $output , $error);
		    }

		    MiwoVideos::log('CLI : ');
		    MiwoVideos::log($output);
		    MiwoVideos::log($error);

		    if (!$error) {
			    $json = array(
				    'success' => 1,
				    'href' => MiwoVideos::get('utility')->route('index.php?option=com_miwovideos&view=videos&task=edit&cid[]=' . $video_id)
			    );
			    echo json_encode($json);
			    return true;
		    }
		    else {
			    $json['error'] = MText::_('COM_MIWOVIDEOS_ERROR_FRAMES_PROCESSING');
			    echo json_encode($json);
			    return false;
		    }
	    }
    }

    public function remoteLink() {
        $upload = MiwoVideos::get('upload');

        $dashboard = '';
        if (MiwoVideos::isDashboard()) {
            $dashboard = '&dashboard=1';
        }

        $upload->remoteLink();
        if (!count($upload->getErrors())) {
            if ($upload->_count > 1) {
                $this->_mainframe->enqueueMessage(MText::sprintf('COM_MIWOVIDEOS_SUCCESSFULLY_UPLOADED'));
                $this->setRedirect('index.php?option=com_miwovideos&view=videos'.$dashboard);
            }
            else {
                $this->_mainframe->enqueueMessage(MText::sprintf('COM_MIWOVIDEOS_SUCCESSFULLY_UPLOADED_X', $upload->_title));
                $redirect_url = MiwoVideos::get('utility')->route('index.php?option=com_miwovideos&view=videos&task=edit&cid[]=' . $upload->_id.$dashboard);
                $this->setRedirect($redirect_url);
            }
        } else {
            $this->_mainframe->enqueueMessage($upload->getError(0));
            $redirect_url = MiwoVideos::get('utility')->route('index.php?option=com_miwovideos&view=upload'.$dashboard);
            $this->setRedirect($redirect_url);
        }

        return $upload;
    }
}