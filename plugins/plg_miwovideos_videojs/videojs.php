<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright  ( C ) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die ('Restricted access');

mimport('framework.plugin.plugin');
require_once(MPATH_WP_PLG.'/miwovideos/admin/library/miwovideos.php');

class plgMiwovideosVideoJs extends MPlugin {

    public function __construct(&$subject, $config) {
        parent::__construct($subject, $config);
        $this->config = MiwoVideos::getConfig();
    }

	public function getPlayer(&$output, $pluginParams, $item) {
        if (strpos($output, '{miwovideos ') === false) {
            return false;
        }

        $this->output = $output;
        $this->pluginParams = $pluginParams;
        $this->item = $item;

        $output = preg_replace_callback('#{miwovideos\s*(.*?)}#s', array(&$this, '_processMatches'), $output);

		$input = MiwoVideos::getInput();
		$document = MFactory::getDocument();

		$document->addStyleSheet(MURL_WP_CNT.'/miwi/plugins/plg_miwovideos_videojs/video-js/video-js.css');
		$document->addScript(MURL_WP_CNT.'/miwi/plugins/plg_miwovideos_videojs/video-js/video.dev.js');

		#Video Plugins
        if ($item->duration) {
            $document->addStyleSheet(MURL_WP_CNT.'/miwi/plugins/plg_miwovideos_videojs/video-js/videojs.thumbnails.css');
            $document->addScript(MURL_WP_CNT.'/miwi/plugins/plg_miwovideos_videojs/video-js/videojs.thumbnails.js');
        }
		if ($input->getCmd('view') == 'video' and $input->getInt('playlist_id', 0) > 0) {
			$document->addStyleSheet(MURL_MIWOVIDEOS.'/site/assets/css/playlist_videojs.css');
		}

		$document->addStyleDeclaration('
		.videoWrapper {
			position: relative;
			padding-top: 0px;
			height: 0px;
			z-index: 3;
			/*overflow: hidden;*/
		}
		video {
			position: absolute !important;
			top: 0;
			left: 0;
			width: 100% !important;
			height: 100% !important;
			/*z-index: 1;*/
		}
		video.video-js {
			z-index: 3;
		}
		.video-js .vjs-controls {
			z-index: 1002;
		}
		.video-js .vjs-big-play-button {
			z-index: 1002;
		}
		.videoWrapper .video-js {
			position: absolute;
			top: 0;
			left: 0;
			width: 100% !important;
			height: 100% !important;
			z-index: 1;
			background: #000000;
		}
		.videoWrapper object,
		.videoWrapper embed {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100% !important;
			z-index: 1;
		}
		.vjs-spinner {
		  /*display: none !important;*/
		}
		.video-js img.vjs-poster {
			height: 100% !important;
			width: 100% !important;
			max-width: 100%;
			z-index: 1;
		}');

        $tmpl = MFactory::getApplication()->getTemplate();

        if (!MRequest::getInt('playlist_id') and MFolder::exists(MPATH_THEMES.'/'.$tmpl.'/html/com_miwovideos')) {
            $document->addStyleDeclaration('
            .videoSizer_1 {
                margin-bottom : 30px
            }');
		}

		return true;
	}

    public function _processMatches(&$matches) {
        static $id = 1;
        $videoParams = $matches[1];
        $videoParamsList = $this->getParams($videoParams);
        $html = $this->getHtmlOutput($id, $videoParamsList);
        if ($this->item->duration and $this->config->get('frames')) {
            $html .= $this->getFramesOutput();
        }

        if(isset($id)) {
            $id++;
        }

        $pattern = str_replace('[', '\[', $matches[0]);
        $pattern = str_replace(']', '\]', $pattern);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = str_replace('|', '\|', $pattern);

        $output = preg_replace('/'.$pattern.'/', $html, $this->output, 1);

        return $output;
    }

	protected function getParams($videoParams) {
        $pluginParams = $this->pluginParams;
		$videoParamsList['width'] 				= $pluginParams->get('width');
		$videoParamsList['height'] 				= $pluginParams->get('height');
		$videoParamsList['controls']			= $pluginParams->get('controls');
		$videoParamsList['autoplay']			= $pluginParams->get('autoplay');
		$videoParamsList['preload']				= $pluginParams->get('preload');
		$videoParamsList['loop']				= $pluginParams->get('loop');
		$videoParamsList['poster_visibility']	= $pluginParams->get('poster_visibility');
		$videoParamsList['video_mp4']			= '';
		$videoParamsList['video_webm']			= '';
		$videoParamsList['video_ogg']			= '';
		$videoParamsList['poster']				= '';
		$videoParamsList['text_track']			= '';

		$items = explode(' ', $videoParams);

		foreach ($items as $item) {
			if ($item != '') {
				$item	= explode('=', $item);
				$name 	= $item[0];
				$value	= strtr($item[1], array('['=>'', ']'=>''));
				if ($name == "text_track") {
					$videoParamsList[$name][] = $value;
				} else {
					$videoParamsList[$name] = $value;
				}
			}
		}

		return $videoParamsList;
	}

	protected function getHtmlOutput($id, &$videoParamsList) {
        $pluginParams = $this->pluginParams;
        $item = $this->item;
		$width 				= $videoParamsList['width'];
		$height 			= $videoParamsList['height'];
		$controls			= $videoParamsList['controls'];
		$autoplay			= $videoParamsList['autoplay'];
		$preload			= $videoParamsList['preload'];
		$loop				= $videoParamsList['loop'];
		$poster_visibility	= $videoParamsList['poster_visibility'];
		$original_mp4		= $videoParamsList['video_mp4'];
		$original_webm		= $videoParamsList['video_webm'];
		$original_ogg		= $videoParamsList['video_ogg'];
		$poster				= $videoParamsList['poster'];
		$tracks				= $videoParamsList['text_track'];
		$ratio				= ($height/$width)*100;

		// Controls
		if ($controls == "1") {
			$controls_html 	= ' controls="controls"';
		} else {
			$controls_html 	= '';
		}

		// Autoplay
        switch ($autoplay) {
            case "global":
                if ($this->config->get('autoplay') == 1) {
                    $autoplay_html 	= ' autoplay="autoplay"';
                } else {
                    $autoplay_html 	= '';
                }
                break;
            case "1":
                $autoplay_html 	= ' autoplay="autoplay"';
                break;
            case "0":
                $autoplay_html 	= '';
        }

		// Preload
        if ($preload == "auto" || $preload == "metadata" || $preload == "none") {
			$preload_html 	= ' preload="'.$preload.'"';
		}

		// Loop
		if ($loop == "1") {
			$loop_html		= ' loop="loop"';
		} else {
			$loop_html 		= '';
		}

		// Poster image
		if ($poster_visibility == "1" && $poster != "") {
			$poster_html 	= ' poster="'.$poster;
		} else {
			$poster_html 	= '';
		}

		// Text tracks
		if (!empty($tracks)) {
			$text_track_html = '';
			foreach ($tracks AS $track) {
				$track_items = explode('|', $track);
				$text_track_html .= '<track kind="'.$track_items[0].'" src="'.$track_items[1].'" srclang="'.$track_items[2].'" label="'.$track_items[3].'" />';
			}
		} else {
			$text_track_html = '';
		}

        $video_mp4 = $video_webm = $video_ogg = '';

        $files = MiwoVideos::get('files')->getVideoFiles($item->id);
        $utility = MiwoVideos::get('utility');
        $default_size = $utility->getVideoSize(MIWOVIDEOS_UPLOAD_DIR.'/videos/'.$item->id.'/orig/'.$item->source);
        $default_res = '';

        if ($this->config->get('video_quality') == $default_size) {
            $default_res = 'true';
        }
        foreach ($files as $file) {

            if (!$item->duration) {
                $orig = '<source src="' . MURL_MEDIA.'/com_miwovideos/videos/' . $item->id . '/orig/' . $file->source . '" type="video/'. $file->ext .'"/>';;
            }

            if ($file->process_type == '200' or $file->process_type < 7) continue;
            $size = $utility->getSize($file->process_type);

            if ($this->config->get('video_quality') == $size) {
                $default_res = 'true';
            }

            $src = $utility->getVideoFilePath($file->video_id, $size, $file->source, 'url');

            if ($file->ext == 'mp4' and $file->process_type == '100') {
                $src = $utility->getVideoFilePath($file->video_id, $default_size, $original_mp4, 'url');
                $video_mp4 .= '<source src="' . $src . '" type="video/mp4" data-res="' . $default_size . 'p" data-default="'.$default_res.'" />';
            } else if ($file->ext == 'mp4') {
                $video_mp4 .= '<source src="' . $src . '" type="video/mp4" data-res="' . $size . 'p" data-default="'.$default_res.'" />';
            }

            if ($file->ext == 'webm' and $file->process_type == '100') {
                $src = $utility->getVideoFilePath($file->video_id, $default_size, $original_webm, 'url');
                $video_webm .= '<source src="' . $src . '" type="video/webm" data-res="' . $default_size . 'p" data-default="'.$default_res.'" />';
            } else if ($file->ext == 'webm') {
                $video_webm .= '<source src="' . $src . '" type="video/webm" data-res="' . $size . 'p" data-default="'.$default_res.'" />';
            }

            if (($file->ext == 'ogg' or $file->ext == 'ogv') and $file->process_type == '100') {
                $src = $utility->getVideoFilePath($file->video_id, $default_size, $original_ogg, 'url');
                $video_ogg .= '<source src="' . $src . '" type="video/ogg" data-res="' . $default_size . 'p" data-default="'.$default_res.'" />';
            } else if ($file->ext == 'ogg' or $file->ext == 'ogv') {
                $video_ogg .= '<source src="' . $src . '" type="video/ogg" data-res="' . $size . 'p" data-default="'.$default_res.'" />';
            }
            $default_res = '';
        }

		// HTML output
		$html = '<div class="videoSizer_'.$id.'"><div class="videoWrapper_'.$id.' videoWrapper">';

        if ($pluginParams->get('id')) {
            $html .= '<video id="plg_videojs_'.$id.'" class="video-js vjs-default-skin vjs-big-play-centered"'.$controls_html.$autoplay_html.$preload_html.$loop_html.$poster_html.'" data-setup=\'{ "techOrder": ["youtube"], "src": "http://www.youtube.com/watch?v='.$pluginParams->get('id').'"}\'>';
        }
		else {
            $html .= '<video id="plg_videojs_'.$id.'" class="video-js vjs-default-skin vjs-big-play-centered"'.$controls_html.$autoplay_html.$preload_html.$loop_html.$poster_html.'" data-setup="{}">';
            if (!empty($video_mp4)) {
                $html .= $video_mp4;
            }

            if (!empty($video_webm)) {
                $html .= $video_webm;
            }

            if (!empty($video_ogg)) {
                $html .= $video_ogg;
            }

            if (!$item->duration) {
                $html .= $orig;
            }

            $html .= $text_track_html;
        }

        $html .= ' </video>';
		$html .= '</div></div>';

		$html .= '<style type="text/css">
		.videoSizer_'.$id.' { max-width: '.$width.'px; }
		.videoWrapper_'.$id.' { padding-bottom: '.$ratio.'%; }
		</style>';

		return $html;
	}

    public function getFramesOutput() {
        if (strpos($this->item->source,'http://') === false) {
            $output = "<script type=\"text/javascript\"><!--
						jQuery(document).ready(function() {
                        var video = videojs('plg_videojs_1');
                        var duration = ".$this->item->duration.";
                        video.thumbnails({
                            0: {
                                src: '".MURL_MEDIA."/miwovideos/images/videos/".$this->item->id."/frames/out1.jpg',
                                style: {
                                    left: '-60px',
                                    width: '100px',
                                    height: '80px'
                                }
                            },";
            for ($i = 1; $i < $this->item->duration; $i++) {
                $output .= $i.":{
                                src: '".MURL_MEDIA."/miwovideos/images/videos/".$this->item->id."/frames/out". $i .".jpg',
                                    style: {
                                        left: '-60px',
                                        width: '100px',
                                        height: '80px'
                                    }
                                },";
            }
            $output .= $this->item->duration.": {
                                                src: '".MURL_MEDIA."/miwovideos/images/videos/".$this->item->id."/frames/out".$this->item->duration.".jpg',
                                                    style: {
                                                        width: '100px',
                                                        height: '80px'
                                                }
            }
            });
            video.resolutions();
			});
            //--></script>";

            return $output;
        }
    }
}