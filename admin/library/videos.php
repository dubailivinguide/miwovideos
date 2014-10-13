<?php
/**
 * @package        MiwoVideos
 * @copyright      Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license        GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die('Restricted Access');

class MiwovideosVideos {

	public function __construct() {
		$this->config = MiwoVideos::getConfig();
	}

	public function getVideo($video_id) {
		static $cache = array();

		if (!isset($cache[ $video_id ])) {
			$cache[ $video_id ] = MiwoDB::loadObject('SELECT * FROM #__miwovideos_videos WHERE id = ' . (int)$video_id . ' AND published = 1');
		}

		return $cache[ $video_id ];
	}

	public function getPlayer($item) {
		$ret = '';

		$config  = MiwoVideos::getConfig();
		$utility = MiwoVideos::get('utility');

		$class = $utility->getHost($item->source);

		if (empty($class)) {
			$class = $config->get('video_player');
		}

		if (!$utility->plgEnabled('miwovideos', $class)) {
			return $ret;
		}

		$plugin = MiwoVideos::getPlugin($class);

		if (!is_object($plugin)) {
			return $ret;
		}

		$params = new MRegistry();
		$params->loadString($plugin->params);

		$files     = MiwoVideos::get('files')->getVideoFiles($item->id);
		$video_mp4 = $video_webm = $video_ogg = '';
		foreach ($files as $file) {
			if ($file->ext == 'mp4' and $file->process_type == '100') {
				$video_mp4 = $file->source;
			}

			if ($file->ext == 'webm' and $file->process_type == '100') {
				$video_webm = $file->source;
			}

			if (($file->ext == 'ogg' or $file->ext == 'ogv') and $file->process_type == '100') {
				$video_ogg = $file->source;
			}
		}

		$output = '{miwovideos video_mp4=[' . $video_mp4 . '] video_webm=[' . $video_webm . '] video_ogg=[' . $video_ogg . ']';

		$plugin->getPlayer($output, $params, $item);

		return $output;
	}

	public function getTotalVideosByCategory($category_id, $inc_children = 1) {
		static $cache = array();

		if (!isset($cache[ $category_id ][ $inc_children ])) {
			$db   = MFactory::getDbo();
			$user = MFactory::getUser();

			$tmp_cats = array();
			$cats     = array();

			$tmp_cats[] = $category_id;
			$cats[]     = $category_id;

			if ($inc_children) {
				while (count($tmp_cats)) {
					$cat_id = array_pop($tmp_cats);

					//Get list of children category
					$db->setQuery('SELECT id FROM #__miwovideos_categories WHERE parent = ' . (int)$cat_id . ' AND published = 1');
					$rows = $db->loadObjectList();

					foreach ($rows as $row) {
						$tmp_cats[] = $row->id;
						$cats[]     = $row->id;
					}
				}
			}

			$sql = 'SELECT COUNT(a.id) FROM #__miwovideos_videos AS a INNER JOIN #__miwovideos_video_categories AS b ON a.id = b.video_id WHERE b.category_id IN(' . implode(',', $cats) . ') AND `access` IN (' . implode(',', $user->getAuthorisedViewLevels()) . ') AND published = 1';

			$db->setQuery($sql);

			$cache[ $category_id ][ $inc_children ] = (int)$db->loadResult();
		}

		return $cache[ $category_id ][ $inc_children ];
	}

	public function getTags($video_id, $getTagData = true, $text_value = false) {
		static $cache = array();

		if (!isset($cache[ $video_id ])) {
			$_tag            = new JHelperTags();
			$_tag->typeAlias = 'com_miwovideos.video';
			$_item_tags      = $_tag->getItemTags('com_miwovideos.video', $video_id, $getTagData);
			if ($text_value == true) {
				$_item_tag_ids   = $_tag->getTagIds($video_id, 'com_miwovideos.video');
				$_item_tag_ids   = explode(',', $_item_tag_ids);
				$_item_tag_names = $_tag->getTagNames($_item_tag_ids);
				foreach ($_item_tags as $_item_tag) {
					$_item_tag->value = $_item_tag->tag_id;
				}

				$i = 0;
				foreach ($_item_tag_names as $_item_tag_name) {
					$_item_tags[ $i ]->text = $_item_tag_name;
					$i++;
				}
			}

			$cache[ $video_id ] = $_item_tags;
		}

		return $cache[ $video_id ];
	}

	public function getDuration($process) {
		$row = MiwoVideos::getTable('MiwovideosVideos');
		$row->load($process->video_id);

		$location = MIWOVIDEOS_UPLOAD_DIR.'/videos/' . $row->id . '/orig/' . $row->source;

		if (!file_exists($location)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_DESTINATION_VIDEO_NOT_EXIST'));
			return false;
		}

		// Get information on original
		if (substr(PHP_OS, 0, 3) == "WIN") {
			$command = "\"" . $this->config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -i $location 2>&1";
			exec($command, $output);
		}
		else {
			$command = $this->config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -i $location 2>&1";
			exec($command, $output);
		}

		MiwoVideos::log('FFmpeg : ' . $command);
		MiwoVideos::log($output);

		$flatoutput = is_array($output) ? implode("\n", $output) : $output;
		if (empty($flatoutput)) {
			MiwoVideos::log('Flatoutput is empty');
			return false;
		}
		else {
			$pos = strpos($flatoutput, "No such file or directory");
			if ($pos !== false) {
				MiwoVideos::log('No such file or directory');
				return false;
			}

			$pos = strpos($flatoutput, "not found");
			if ($pos !== false) {
				MiwoVideos::log('Not found');
				return false;
			}

			$pos = strpos($flatoutput, "Permission denied");
			if ($pos !== false) {
				MiwoVideos::log('Permission denied');
				return false;
			}
		}

		preg_match('/Duration: (.*?),/', implode("\n", $output), $matches);
		$duration_string = $matches[1];

		list($hr, $m, $s) = explode(':', $duration_string);
		$duration = ((int)$hr * 3600) + ((int)$m * 60) + (int)$s;
		$duration = (int)$duration;

		if ($duration <= 0) {
			MiwoVideos::log('0 Duration');
			return false;
		}

		// Create an object to bind to the database
		$data             = array();
		$data['duration'] = $duration;

		if (!$row->bind($data)) {
			$this->setError($row->getError());
			return false;
		}

		if (!$row->store()) {
			$this->setError($row->getError());
			return false;
		}

		return true;
	}

	public function processThumb($process, $fileType, $size) {
		// Create a new query object.
		$config = $this->config;

		$item = MiwoVideos::getTable('MiwovideosVideos');
		$item->load($process->video_id);

		$location = MIWOVIDEOS_UPLOAD_DIR.'/videos/' . $item->id . '/orig/' . $item->source;

		if (!file_exists($location)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_SOURCE_VIDEO_NOT_EXIST'));
		}

		// Get information on original
		if (substr(PHP_OS, 0, 3) == "WIN") {
			$command = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -i $location 2>&1";
			exec($command, $output);
		}
		else {
			$command = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -i $location 2>&1";
			exec($command, $output);
		}

		MiwoVideos::log('FFmpeg : ' . $command);
		MiwoVideos::log($output);

		$flatoutput = is_array($output) ? implode("\n", $output) : $output;
		if (empty($flatoutput)) {
			MiwoVideos::log('Flatoutput is empty');
			return false;
		}
		else {
			$pos = strpos($flatoutput, "No such file or directory");
			if ($pos !== false) {
				MiwoVideos::log('No such file or directory');
				return false;
			}

			$pos = strpos($flatoutput, "not found");
			if ($pos !== false) {
				MiwoVideos::log('Not found');
				return false;
			}

			$pos = strpos($flatoutput, "Permission denied");
			if ($pos !== false) {
				MiwoVideos::log('Permission denied');
				return false;
			}
		}

		$ffmpeg_version = 0;
		$input_width    = 0;
		$input_height   = 0;
		$duration       = 0;

		// Get ffmpeg version
		if (preg_match('#FFmpeg version(.*?), Copyright#', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}
		elseif (preg_match('#ffmpeg version(.*?) Copyright#i', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}

		// Get original size
		if (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tbr/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}
		elseif (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tb/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}

		// Get duration
		if (preg_match('/Duration: (.*?),/', implode("\n", $output), $matches)) {
			$duration_string = $matches[1];
			list($hr, $m, $s) = explode(':', $duration_string);
			$duration = ((int)$hr * 3600) + ((int)$m * 60) + (int)$s;
			$duration = (int)$duration;
		}

		if ($input_width == 0 || $input_height == 0) {
			MiwoVideos::log('0 Width or height ');
			return false;
		}

		if ($input_height < $size) {
			MiwoVideos::log('Video size is not more than ' . $size);
			return false;
		}

		MFolder::create(MIWOVIDEOS_UPLOAD_DIR."/images/videos/" . $item->id . "/" . $size);
		$new_source   = hash('haval256,5', $item->title) . ".jpg";
		$new_location = MIWOVIDEOS_UPLOAD_DIR."/images/videos/" . $item->id . "/" . $size . "/" . $new_source;

		// Calculate input aspect
		$input_aspect  = $input_width / $input_height;
		$output_aspect = ($input_aspect > 0 ? $input_aspect : 1.333);

		// Calculate output sizes
		$output_width = intval($size * $output_aspect);
		$output_width % 2 == 1 ? $output_width += 1 : false;
		$output_height = $size;

		// Calculate padding (for black bar letterboxing/pillarboxing)
		$input_aspect = $input_width / $input_height;
		$conv_height  = intval(($output_width / $input_aspect));
		$conv_height % 2 == 1 ? $conv_height -= 1 : false;
		$conv_pad = intval((($output_height - $conv_height) / 2.0));
		$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

		if ($input_aspect < 1.33333333333333) {
			$aspect_mode = 'pillarboxing';
		}
		else {
			$aspect_mode = 'letterboxing';
		}

		if ($conv_pad < 0) {
			$input_aspect = $input_width / $input_height;
			$conv_width   = intval(($output_height * $input_aspect));
			$conv_width % 2 == 1 ? $conv_width -= 1 : false;
			$conv_pad = intval((($output_width - $conv_width) / 2.0));
			$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

			$conv_pad = abs($conv_pad);
			$pad      = " -vf pad=$output_width:$output_height:$conv_pad:0 ";

			$wxh = $conv_width . 'x' . $output_height;
		}
		else {
			$wxh = $output_width . 'x' . $conv_height;
			$pad = " -vf pad=$output_width:$output_height:0:$conv_pad ";
		}

		if (version_compare($ffmpeg_version, '0.7.0', '<') || $conv_pad == 0) {
			$pad = '';
		}

		// Take the screenshot at 4 seconds into the movie unless the duration can be obtained,
		// in which case take the screenshot half way through
		$offset = 4;
		if ($duration) {
			$offset = $duration / 2;
			$offset = (int)$offset;
		}

		try {
			if (substr(PHP_OS, 0, 3) == "WIN") {
				$command = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -y -itsoffset -$offset -i $location -vcodec mjpeg -vframes 1 -an -f rawvideo -s $wxh $pad $new_location 2>&1";
				exec($command, $output);
			}
			else {
				$command = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -y -itsoffset -$offset -i $location -vcodec mjpeg -vframes 1 -an -f rawvideo -s $wxh $pad $new_location 2>&1";
				exec($command, $output);
			}

			MiwoVideos::log('FFmpeg : ' . $command);
			MiwoVideos::log($output);

			if (file_exists($new_location) && filesize($new_location) == 0) {
				mimport('framework.filesystem.file');
				MFile::delete($new_location);
			}
		} catch (Exception $e) {
			MiwoVideos::log($e->getMessage());
		}

		if (file_exists($new_location) && filesize($new_location) > 0) {
			$model = MiwoVideos::get('controller')->getModel('videos');
			MRequest::setVar('cid', $item->id, 'post');
			MiwoVideos::get('controller')->updateField('videos', 'thumb', hash('haval256,5', $item->title) . ".jpg", $model);
			if (isset($process->process_type)) {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size, $process->process_type);
			}
			else {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size);
			}

			return true;
		}

		MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_DESTINATION_VIDEO_NOT_EXIST'));

	}

	public function processMp4($process, $fileType, $size) {
		$config = $this->config;

		$item = MiwoVideos::getTable('MiwovideosVideos');
		$item->load($process->video_id);

		$location = MIWOVIDEOS_UPLOAD_DIR.'/videos/' . $item->id . '/orig/' . $item->source;

		if (!file_exists($location)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_SOURCE_VIDEO_NOT_EXIST'));
			return false;
		}

		// Get information on original
		if (substr(PHP_OS, 0, 3) == "WIN") {
			$command = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -i $location 2>&1";
			exec($command, $output);
		}
		else {
			$command = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -i $location 2>&1";
			exec($command, $output);
		}

		MiwoVideos::log('FFmpeg : ' . $command);
		MiwoVideos::log($output);

		$flatoutput = is_array($output) ? implode("\n", $output) : $output;
		if (empty($flatoutput)) {
			MiwoVideos::log('Flatoutput is empty');
			return false;
		}
		else {
			$pos = strpos($flatoutput, "No such file or directory");
			if ($pos !== false) {
				MiwoVideos::log('No such file or directory');
				return false;
			}

			$pos = strpos($flatoutput, "not found");
			if ($pos !== false) {
				MiwoVideos::log('Not found');
				return false;
			}

			$pos = strpos($flatoutput, "Permission denied");
			if ($pos !== false) {
				MiwoVideos::log('Permission denied');
				return false;
			}
		}

		$ffmpeg_version = 0;
		$input_width    = 0;
		$input_height   = 0;
		$input_bitrate  = 0;

		// Get ffmpeg version
		if (preg_match('#FFmpeg version(.*?), Copyright#', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}
		elseif (preg_match('#ffmpeg version(.*?) Copyright#i', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}

		// Get original size
		if (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tbr/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}
		elseif (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tb/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}

		// Get original bitrate
		// Outdated pcre (perl-compatible regular expressions) libraries case error:
		// Compilation failed: unrecognized character
		// Therefore, surpress error and offer alternative
		if (@preg_match('/bitrate:\s(?<bitrate>\d+)\skb\/s/', implode("\n", $output), $matches)) {
			$input_bitrate = $matches[1];
		}
		elseif (preg_match('/bitrate:\s(.*?)\skb\/s/', implode("\n", $output), $matches)) {
			$input_bitrate = $matches[1];
		}

		if ($input_width == 0 || $input_height == 0 || $input_bitrate == 0) {
			MiwoVideos::log('0 Width or height or bitrate');
			return false;
		}

		$bitrate = $input_bitrate;

		if (($input_height < $size) and $size != '240') {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_ORIGINAL_SMALLER_THAN_DEST'));
			return false;
		}

		MFolder::create(MIWOVIDEOS_UPLOAD_DIR."/videos/" . $item->id . "/" . $size);
		$new_source   = hash('haval256,5', $item->title) . "." . $fileType;
		$new_location = MIWOVIDEOS_UPLOAD_DIR."/videos/" . $item->id . "/" . $size . "/" . $new_source;

		// Calculate input aspect
		$input_aspect  = $input_width / $input_height;
		$output_aspect = ($input_aspect > 0 ? $input_aspect : 1.333);

		// Calculate output sizes
		$output_width = intval($size * $output_aspect);
		$output_width % 2 == 1 ? $output_width += 1 : false;
		$output_height = $size;

		// Calculate padding (for black bar letterboxing/pillarboxing)
		$input_aspect = $input_width / $input_height;
		$conv_height  = intval(($output_width / $input_aspect));
		$conv_height % 2 == 1 ? $conv_height -= 1 : false;
		$conv_pad = intval((($output_height - $conv_height) / 2.0));
		$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

		if ($input_aspect < 1.33333333333333) {
			$aspect_mode = 'pillarboxing';
		}
		else {
			$aspect_mode = 'letterboxing';
		}

		if ($conv_pad < 0) {
			$input_aspect = $input_width / $input_height;
			$conv_width   = intval(($output_height * $input_aspect));
			$conv_width % 2 == 1 ? $conv_width -= 1 : false;
			$conv_pad = intval((($output_width - $conv_width) / 2.0));
			$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

			$conv_pad = abs($conv_pad);
			$pad      = " -vf pad=$output_width:$output_height:$conv_pad:0 ";

			$wxh = $conv_width . 'x' . $output_height;
		}
		else {
			$wxh = $output_width . 'x' . $conv_height;
			$pad = " -vf pad=$output_width:$output_height:0:$conv_pad ";
		}

		if (version_compare($ffmpeg_version, '0.7.0', '<') || $conv_pad == 0) {
			$pad = '';
		}

		// First attempt (@alduccino commands - CRF with PRESET)
		try {
			// Set parameter values
			switch ($size) {
				case '1080':
				case '720':
					$vbit = 2000;
					$min  = 1550;
					$max  = 2000;
					$buff = 1550;
					$crf  = 18;
					break;
				case '480':
				case '360':
				default:
					$vbit = 1000;
					$min  = 800;
					$max  = 1000;
					$buff = 800;
					$crf  = 18;
					break;
			}
			if (substr(PHP_OS, 0, 3) == "WIN") {
				$input = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -y -i $location -strict experimental -acodec aac -ac 2 -ab 192k -s $wxh -aspect 16:9 -r 24000/1001 -vcodec libx264 -b:v " . $vbit . "k -minrate " . $min . "k -maxrate " . $max . "k -bufsize " . $buff . "K -crf $crf -preset fast -f mp4 -threads 0 $new_location 2>&1";
				exec($input, $output);
			}
			else {
				$input = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -y -i $location -strict experimental -acodec aac -ac 2 -ab 192k -s $wxh -aspect 16:9 -r 24000/1001 -vcodec libx264 -b:v " . $vbit . "k -minrate " . $min . "k -maxrate " . $max . "k -bufsize " . $buff . "K -crf $crf -preset fast -f mp4 -threads 0 $new_location 2>&1";
				exec($input, $output);
			}

			MiwoVideos::log('FFmpeg : ' . $input);
			MiwoVideos::log($output);

			if (file_exists($new_location) && filesize($new_location) == 0) {
				mimport('framework.filesystem.file');
				MFile::delete($new_location);
			}
		} catch (Exception $e) {
			MiwoVideos::log($e->getMessage());
		}

		// Second attempt
		if (!file_exists($new_location)) {
			try {
				if (substr(PHP_OS, 0, 3) == "WIN") {
					$input = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -y -i $location -strict experimental -acodec aac -ac 2 -ab 160k -s $wxh $pad -vcodec libx264 -vpre ipod640 -b:v " . $bitrate . "k -f mp4 -threads 0 $new_location 2>&1";
					exec($input, $output);
				}
				else {
					$input = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -y -i $location -strict experimental -acodec aac -ac 2 -ab 160k -s $wxh $pad -vcodec libx264 -vpre ipod640 -b:v " . $bitrate . "k -f mp4 -threads 0 $new_location 2>&1";
					exec($input, $output);
				}

				MiwoVideos::log('FFmpeg : ' . $input);
				MiwoVideos::log($output);

				if (file_exists($new_location) && filesize($new_location) == 0) {
					mimport('framework.filesystem.file');
					MFile::delete($new_location);
				}
			} catch (Exception $e) {
				MiwoVideos::log($e->getMessage());
			}
		}


		// Third attempt
		if (!file_exists($new_location)) {
			try {
				$ffpreset_libx264_slow    = " -coder 1 -flags +loop -cmp +chroma -partitions +parti8x8+parti4x4+partp8x8+partb8x8 -me_method umh -subq 8 -me_range 16 -g 250 -keyint_min 25 -sc_threshold 40 -i_qfactor 0.71 -b_strategy 2 -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -bf 3 -refs 5 -directpred 3 -trellis 1 -flags2 +bpyramid+mixed_refs+wpred+dct8x8+fastpskip -wpredp 2 -rc_lookahead 50 ";
				$ffpreset_libx264_ipod640 = " -coder 0 -bf 0 -refs 1 -flags2 -wpred-dct8x8 -level 30 -maxrate 10000000 -bufsize 10000000 -wpredp 0 ";
				if (substr(PHP_OS, 0, 3) == "WIN") {
					$input = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -y -i $location -strict experimental -acodec aac -ac 2 -ab 160k -s $wxh $pad -vcodec libx264 $ffpreset_libx264_slow $ffpreset_libx264_ipod640 -b:v " . $bitrate . "k -f mp4 -threads 0 $new_location 2>&1";
					exec($input, $output);
				}
				else {
					$input = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -y -i $location -strict experimental -acodec aac -ac 2 -ab 160k -s $wxh $pad -vcodec libx264 $ffpreset_libx264_slow $ffpreset_libx264_ipod640 -b:v " . $bitrate . "k -f mp4 -threads 0 $new_location 2>&1";
					exec($input, $output);
				}

				MiwoVideos::log('FFmpeg : ' . $input);
				MiwoVideos::log($output);

				if (file_exists($new_location) && filesize($new_location) == 0) {
					mimport('framework.filesystem.file');
					MFile::delete($new_location);
				}
			} catch (Exception $e) {
				MiwoVideos::log($e->getMessage());
			}
		}

		if (file_exists($new_location) && filesize($new_location) > 0) {
			// Add watermark

			$this->processWatermark($process, $fileType, $new_location);
			if (isset($process->process_type)) {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size, $process->process_type);
			}
			else {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size);
			}

			return true;
		}

		MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_DESTINATION_VIDEO_NOT_EXIST'));
		return false;
	}

	public function processWebm($process, $fileType, $size) {
		$config = $this->config;

		$item = MiwoVideos::getTable('MiwovideosVideos');
		$item->load($process->video_id);

		$location = MIWOVIDEOS_UPLOAD_DIR.'/videos/' . $item->id . '/orig/' . $item->source;

		if (!file_exists($location)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_SOURCE_VIDEO_NOT_EXIST'));
			return false;
		}

		// Get information on original
		if (substr(PHP_OS, 0, 3) == "WIN") {
			$command = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -i $location 2>&1";
			exec($command, $output);
		}
		else {
			$command = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -i $location 2>&1";
			exec($command, $output);
		}

		MiwoVideos::log('FFmpeg : ' . $command);
		MiwoVideos::log($output);

		$flatoutput = is_array($output) ? implode("\n", $output) : $output;
		if (empty($flatoutput)) {
			MiwoVideos::log('Flatoutput is empty');
			return false;
		}
		else {
			$pos = strpos($flatoutput, "No such file or directory");
			if ($pos !== false) {
				MiwoVideos::log('No such file or directory');
				return false;
			}

			$pos = strpos($flatoutput, "not found");
			if ($pos !== false) {
				MiwoVideos::log('Not found');
				return false;
			}

			$pos = strpos($flatoutput, "Permission denied");
			if ($pos !== false) {
				MiwoVideos::log('Permission denied');
				return false;
			}
		}

		$ffmpeg_version = 0;
		$input_width    = 0;
		$input_height   = 0;
		$input_bitrate  = 0;

		// Get ffmpeg version
		if (preg_match('#FFmpeg version(.*?), Copyright#', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}
		elseif (preg_match('#ffmpeg version(.*?) Copyright#i', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}

		// Get original size
		if (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tbr/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}
		elseif (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tb/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}

		// Get original bitrate
		// Outdated pcre (perl-compatible regular expressions) libraries case error:
		// Compilation failed: unrecognized character
		// Therefore, surpress error and offer alternative
		if (@preg_match('/bitrate:\s(?<bitrate>\d+)\skb\/s/', implode("\n", $output), $matches)) {
			$input_bitrate = $matches[1];
		}
		elseif (preg_match('/bitrate:\s(.*?)\skb\/s/', implode("\n", $output), $matches)) {
			$input_bitrate = $matches[1];
		}

		if ($input_width == 0 || $input_height == 0 || $input_bitrate == 0) {
			MiwoVideos::log('0 Width or height or bitrate');
			return false;
		}

		$bitrate = $input_bitrate; //min($input_bitrate, $this->getVideoBitrate($size));

		if (($input_height < $size) and $size != '240') {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_ORIGINAL_SMALLER_THAN_DEST'));
			return false;
		}

		MFolder::create(MIWOVIDEOS_UPLOAD_DIR."/videos/" . $item->id . "/" . $size);
		$new_source   = hash('haval256,5', $item->title) . "." . $fileType;
		$new_location = MIWOVIDEOS_UPLOAD_DIR."/videos/" . $item->id . "/" . $size . "/" . $new_source;

		// Calculate input aspect
		$input_aspect  = $input_width / $input_height;
		$output_aspect = ($input_aspect > 0 ? $input_aspect : 1.333);

		// Calculate output sizes
		$output_width = intval($size * $output_aspect);
		$output_width % 2 == 1 ? $output_width += 1 : false;
		$output_height = $size;

		// Calculate padding (for black bar letterboxing/pillarboxing)
		$input_aspect = $input_width / $input_height;
		$conv_height  = intval(($output_width / $input_aspect));
		$conv_height % 2 == 1 ? $conv_height -= 1 : false;
		$conv_pad = intval((($output_height - $conv_height) / 2.0));
		$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

		if ($input_aspect < 1.33333333333333) {
			$aspect_mode = 'pillarboxing';
		}
		else {
			$aspect_mode = 'letterboxing';
		}

		if ($conv_pad < 0) {
			$input_aspect = $input_width / $input_height;
			$conv_width   = intval(($output_height * $input_aspect));
			$conv_width % 2 == 1 ? $conv_width -= 1 : false;
			$conv_pad = intval((($output_width - $conv_width) / 2.0));
			$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

			$conv_pad = abs($conv_pad);
			$pad      = " -vf pad=$output_width:$output_height:$conv_pad:0 ";

			$wxh = $conv_width . 'x' . $output_height;
		}
		else {
			$wxh = $output_width . 'x' . $conv_height;
			$pad = " -vf pad=$output_width:$output_height:0:$conv_pad ";
		}

		$opt_quality = '-quality good';
		$opt_speed   = '-quality good';
		$opt_slices  = '-slices 4';
		$opt_arnr    = '-arnr_max_frames 7 -arnr_strength 5 -arnr_type 3';
		if (version_compare($ffmpeg_version, '0.7.0', '<') || $conv_pad == 0) {
			$pad         = '';
			$opt_quality = '';
			$opt_speed   = '';
			$opt_slices  = '';
			$opt_arnr    = '';
		}
		if (version_compare($ffmpeg_version, '0.7.0', '<')) {
			$opt_quality = '';
			$opt_speed   = '';
			$opt_slices  = '';
			$opt_arnr    = '';
		}

		try {
			$ffpreset_libvpx_720p_pass1 = " -vcodec libvpx -g 120 -rc_lookahead 16 $opt_quality $opt_speed -profile:v 0 -qmax 51 -qmin 11 $opt_slices -vb 2M ";
			$ffpreset_libvpx_720p_pass2 = " -vcodec libvpx -g 120 -rc_lookahead 16 $opt_quality $opt_speed -profile:v 0 -qmax 51 -qmin 11 $opt_slices -vb 2M -maxrate 24M -minrate 100k $opt_arnr ";
			if (substr(PHP_OS, 0, 3) == "WIN") {
				$command1 = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -y -i $location -s $wxh $pad $ffpreset_libvpx_720p_pass1 -b:v " . $bitrate . "k -pass 1 -an -f webm $new_location 2>&1";
				exec($command1, $output1);
				$command2 = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -y -i $location -s $wxh $pad $ffpreset_libvpx_720p_pass2 -b:v " . $bitrate . "k -pass 2 -acodec libvorbis -ab 90k -f webm $new_location 2>&1";
				exec($command2, $output2);
			}
			else {
				$command1 = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -y -i $location -s $wxh $pad $ffpreset_libvpx_720p_pass1 -b:v " . $bitrate . "k -pass 1 -an -f webm $new_location 2>&1";
				exec($command1, $output1);
				$command2 = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -y -i $location -s $wxh $pad $ffpreset_libvpx_720p_pass2 -b:v " . $bitrate . "k -pass 2 -acodec libvorbis -ab 90k -f webm $new_location 2>&1";
				exec($command2, $output2);
			}

			MiwoVideos::log('FFmpeg : ' . $command1);
			MiwoVideos::log($output1);
			MiwoVideos::log('FFmpeg : ' . $command2);
			MiwoVideos::log($output2);

			if (file_exists($new_location) && filesize($new_location) == 0) {
				mimport('framework.filesystem.file');
				MFile::delete($new_location);
			}
		} catch (Exception $e) {
			MiwoVideos::log($e->getMessage());
		}

		if (file_exists($new_location) && filesize($new_location) > 0) {
			$this->processWatermark($process, $fileType, $new_location);
			if (isset($process->process_type)) {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size, $process->process_type);
			}
			else {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size);
			}

			return true;
		}

		MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_DESTINATION_VIDEO_NOT_EXIST'));

	}

	public function processOgg($process, $fileType, $size) {
		$config = $this->config;

		$item = MiwoVideos::getTable('MiwovideosVideos');
		$item->load($process->video_id);

		$location = MIWOVIDEOS_UPLOAD_DIR.'/videos/' . $item->id . '/orig/' . $item->source;

		if (!file_exists($location)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_SOURCE_VIDEO_NOT_EXIST'));
			return false;
		}

		// Get information on original
		if (substr(PHP_OS, 0, 3) == "WIN") {
			$command = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -i $location 2>&1";
			exec($command, $output);
		}
		else {
			$command = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -i $location 2>&1";
			exec($command, $output);
		}

		MiwoVideos::log('FFmpeg : ' . $command);
		MiwoVideos::log($output);

		$flatoutput = is_array($output) ? implode("\n", $output) : $output;
		if (empty($flatoutput)) {
			MiwoVideos::log('Flatoutput is empty');
			return false;
		}
		else {
			$pos = strpos($flatoutput, "No such file or directory");
			if ($pos !== false) {
				MiwoVideos::log('No such file or directory');
				return false;
			}

			$pos = strpos($flatoutput, "not found");
			if ($pos !== false) {
				MiwoVideos::log('Not found');
				return false;
			}

			$pos = strpos($flatoutput, "Permission denied");
			if ($pos !== false) {
				MiwoVideos::log('Permission denied');
				return false;
			}
		}

		$ffmpeg_version = 0;
		$input_width    = 0;
		$input_height   = 0;
		$input_bitrate  = 0;

		// Get ffmpeg version
		if (preg_match('#FFmpeg version(.*?), Copyright#', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}
		elseif (preg_match('#ffmpeg version(.*?) Copyright#i', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}

		// Get original size
		if (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tbr/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}
		elseif (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tb/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}

		// Get original bitrate
		// Outdated pcre (perl-compatible regular expressions) libraries case error:
		// Compilation failed: unrecognized character
		// Therefore, surpress error and offer alternative
		if (@preg_match('/bitrate:\s(?<bitrate>\d+)\skb\/s/', implode("\n", $output), $matches)) {
			$input_bitrate = $matches[1];
		}
		elseif (preg_match('/bitrate:\s(.*?)\skb\/s/', implode("\n", $output), $matches)) {
			$input_bitrate = $matches[1];
		}

		if ($input_width == 0 || $input_height == 0 || $input_bitrate == 0) {
			MiwoVideos::log('0 Width or height or bitrate');
			return false;
		}

		$bitrate = $input_bitrate; //min($input_bitrate, $this->getVideoBitrate($size));

		if (($input_height < $size) and $size != '240') {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_ORIGINAL_SMALLER_THAN_DEST'));
			return false;
		}

		MFolder::create(MIWOVIDEOS_UPLOAD_DIR."/videos/" . $item->id . "/" . $size);
		$new_source   = hash('haval256,5', $item->title) . "." . $fileType;
		$new_location = MIWOVIDEOS_UPLOAD_DIR."/videos/" . $item->id . "/" . $size . "/" . $new_source;

		// Calculate input aspect
		$input_aspect  = $input_width / $input_height;
		$output_aspect = ($input_aspect > 0 ? $input_aspect : 1.333);

		// Calculate output sizes
		$output_width = intval($size * $output_aspect);
		$output_width % 2 == 1 ? $output_width += 1 : false;
		$output_height = $size;

		// Calculate padding (for black bar letterboxing/pillarboxing)
		$input_aspect = $input_width / $input_height;
		$conv_height  = intval(($output_width / $input_aspect));
		$conv_height % 2 == 1 ? $conv_height -= 1 : false;
		$conv_pad = intval((($output_height - $conv_height) / 2.0));
		$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

		if ($input_aspect < 1.33333333333333) {
			$aspect_mode = 'pillarboxing';
		}
		else {
			$aspect_mode = 'letterboxing';
		}

		if ($conv_pad < 0) {
			$input_aspect = $input_width / $input_height;
			$conv_width   = intval(($output_height * $input_aspect));
			$conv_width % 2 == 1 ? $conv_width -= 1 : false;
			$conv_pad = intval((($output_width - $conv_width) / 2.0));
			$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

			$conv_pad = abs($conv_pad);
			$pad      = " -vf pad=$output_width:$output_height:$conv_pad:0 ";

			$wxh = $conv_width . 'x' . $output_height;
		}
		else {
			$wxh = $output_width . 'x' . $conv_height;
			$pad = " -vf pad=$output_width:$output_height:0:$conv_pad ";
		}

		if (version_compare($ffmpeg_version, '0.7.0', '<') || $conv_pad == 0) {
			$pad = '';
		}

		try {
			$ffpreset_libx264_slow    = " -coder 1 -flags +loop -cmp +chroma -partitions +parti8x8+parti4x4+partp8x8+partb8x8 -me_method umh -subq 8 -me_range 16 -g 250 -keyint_min 25 -sc_threshold 40 -i_qfactor 0.71 -b_strategy 2 -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -bf 3 -refs 5 -directpred 3 -trellis 1 -flags2 +bpyramid+mixed_refs+wpred+dct8x8+fastpskip -wpredp 2 -rc_lookahead 50 ";
			$ffpreset_libx264_ipod640 = " -coder 0 -bf 0 -refs 1 -flags2 -wpred-dct8x8 -level 30 -maxrate 10000000 -bufsize 10000000 -wpredp 0 ";
			if (substr(PHP_OS, 0, 3) == "WIN") {
				$command = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -y -i $location -s $wxh $pad -vcodec libtheora -b:v " . $bitrate . "k -acodec libvorbis $new_location 2>&1";
				exec($command, $output);
			}
			else {
				$command = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -y -i $location -s $wxh $pad -vcodec libtheora -b:v " . $bitrate . "k -acodec libvorbis $new_location 2>&1";
				exec($command, $output);
			}

			MiwoVideos::log('FFmpeg : ' . $command);
			MiwoVideos::log($output);

			if (file_exists($new_location) && filesize($new_location) == 0) {
				mimport('framework.filesystem.file');
				MFile::delete($new_location);
			}
		} catch (Exception $e) {
			MiwoVideos::log($e->getMessage());
		}

		if (file_exists($new_location) && filesize($new_location) > 0) {
			// Add watermark
			$this->processWatermark($process, $fileType, $new_location);
			if (isset($process->process_type)) {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size, $process->process_type);
			}
			else {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size);
			}

			return true;
		}
		MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_DESTINATION_VIDEO_NOT_EXIST'));
	}

	public function processFlv($process, $fileType, $size) {
		$item = MiwoVideos::getTable('MiwovideosVideos');
		$item->load($process->video_id);
		$location = MIWOVIDEOS_UPLOAD_DIR.'/videos/'.$item->id.'/orig/'.$item->source;

		if (!file_exists($location)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_SOURCE_VIDEO_NOT_EXIST'));
			return false;
		}

		// Get information on original
		if (substr(PHP_OS, 0, 3) == "WIN") {
			$command = "\"".$this->config->get('ffmpeg_path', '/usr/local/bin/ffmpeg')."\" -i $location 2>&1";
			exec($command, $output);
		}
		else {
			$command = $this->config->get('ffmpeg_path', '/usr/local/bin/ffmpeg')." -i $location 2>&1";
			exec($command, $output);
		}

		$flatoutput = is_array($output) ? implode("\n", $output) : $output;
		if (empty($flatoutput)) {
			MiwoVideos::log('Flatoutput is empty');
			return false;
		}
		else {
			$pos = strpos($flatoutput, "No such file or directory");
			if ($pos !== false) {
				MiwoVideos::log('No such file or directory');
				return false;
			}

			$pos = strpos($flatoutput, "not found");
			if ($pos !== false) {
				MiwoVideos::log('Not found');
				return false;
			}

			$pos = strpos($flatoutput, "Permission denied");
			if ($pos !== false) {
				MiwoVideos::log('Permission denied');
				return false;
			}
		}

		$ffmpeg_version = 0;
		$input_width    = 0;
		$input_height   = 0;
		$input_bitrate  = 0;

		// Get ffmpeg version
		if (preg_match('#FFmpeg version(.*?), Copyright#', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}
		elseif (preg_match('#ffmpeg version(.*?) Copyright#i', implode("\n", $output), $matches)) {
			$ffmpeg_version = trim($matches[1]);
		}

		// Get original size
		if (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tbr/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}
		elseif (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tb/', implode("\n", $output), $matches)) {
			$input_width  = $matches[1];
			$input_height = $matches[2];
		}

		// Get original bitrate
		// Outdated pcre (perl-compatible regular expressions) libraries case error:
		// Compilation failed: unrecognized character
		// Therefore, surpress error and offer alternative
		if (@preg_match('/bitrate:\s(?<bitrate>\d+)\skb\/s/', implode("\n", $output), $matches)) {
			$input_bitrate = $matches[1];
		}
		elseif (preg_match('/bitrate:\s(.*?)\skb\/s/', implode("\n", $output), $matches)) {
			$input_bitrate = $matches[1];
		}

		if ($input_width == 0 || $input_height == 0 || $input_bitrate == 0) {
			MiwoVideos::log('0 Width or height or bitrate');
			return false;
		}

		$bitrate = $input_bitrate; //min($input_bitrate, $this->getVideoBitrate($size));

		if (($input_height < $size) and $size != '240') {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_ORIGINAL_SMALLER_THAN_DEST'));
			return false;
		}

		MFolder::create(MIWOVIDEOS_UPLOAD_DIR."/videos/".$item->id."/".$size);
		$new_source   = hash('haval256,5', $item->title).".".$fileType;
		$new_location = MIWOVIDEOS_UPLOAD_DIR."/videos/".$item->id."/".$size."/".$new_source;

		// Calculate input aspect
		$input_aspect  = $input_width / $input_height;
		$output_aspect = ($input_aspect > 0 ? $input_aspect : 1.333);

		// Calculate output sizes
		$output_width = intval($size * $output_aspect);
		$output_width % 2 == 1 ? $output_width += 1 : false;
		$output_height = $size;

		// Calculate padding (for black bar letterboxing/pillarboxing)
		$input_aspect = $input_width / $input_height;
		$conv_height  = intval(($output_width / $input_aspect));
		$conv_height % 2 == 1 ? $conv_height -= 1 : false;
		$conv_pad = intval((($output_height - $conv_height) / 2.0));
		$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

		if ($input_aspect < 1.33333333333333) {
			$aspect_mode = 'pillarboxing';
		}
		else {
			$aspect_mode = 'letterboxing';
		}

		if ($conv_pad < 0) {
			$input_aspect = $input_width / $input_height;
			$conv_width   = intval(($output_height * $input_aspect));
			$conv_width % 2 == 1 ? $conv_width -= 1 : false;
			$conv_pad = intval((($output_width - $conv_width) / 2.0));
			$conv_pad % 2 == 1 ? $conv_pad -= 1 : false;

			$conv_pad = abs($conv_pad);
			$pad      = " -vf pad=$output_width:$output_height:$conv_pad:0 ";

			$wxh = $conv_width.'x'.$output_height;
		}
		else {
			$wxh = $output_width.'x'.$conv_height;
			$pad = " -vf pad=$output_width:$output_height:0:$conv_pad ";
		}

		if (version_compare($ffmpeg_version, '0.7.0', '<') || $conv_pad == 0) {
			$pad = '';
		}

		try {
			if (substr(PHP_OS, 0, 3) == "WIN") {
				$command = "\"".$this->config->get('ffmpeg_path', '/usr/local/bin/ffmpeg')."\" -y -i $location -ab 128 -ar 22050 -b:v ".$bitrate."k -s $wxh $pad -g 25 -keyint_min 25 $new_location 2>&1";
				exec($command, $output);
			}
			else {
				$command = $this->config->get('ffmpeg_path', '/usr/local/bin/ffmpeg')." -y -i $location -ab 128 -ar 22050 -b:v ".$bitrate."k -s $wxh $pad -g 25 -keyint_min 25 $new_location 2>&1";
				exec($command, $output);
			}

			MiwoVideos::log('FFmpeg : '.$command);
			MiwoVideos::log($output);

			if (file_exists($new_location) && filesize($new_location) == 0) {
				mimport('framework.filesystem.file');
				MFile::delete($new_location);
			}
		} catch (Exception $e) {
			MiwoVideos::log($e->getMessage());
		}

		if (file_exists($new_location) && filesize($new_location) > 0) {
			// Add watermark
			$this->processWatermark($process, $fileType, $new_location);
			if (isset($process->process_type)) {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size, $process->process_type);
			}
			else {
				MiwoVideos::get('files')->add($item, $fileType, $new_source, $size);
			}

			return true;
		}
		MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_DESTINATION_VIDEO_NOT_EXIST'));
	}

	public function injectMetaData($process) {
		$item = MiwoVideos::getTable('MiwovideosVideos');
		$item->load($process->video_id);
		$files = MiwoVideos::get('files')->getVideoFiles($item->id);

		if (empty($process->size)) {
			$size = MiwoVideos::get('utility')->getVideoSize(MIWOVIDEOS_UPLOAD_DIR.'/videos/'.$item->id.'/orig/'.$item->source);
		}
		else {
			$size = $process->size;
		}

		if (empty($files) or empty($size)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_EMPTY_FILE_OR_SIZE'));
			return false;
		}

		$ret = true;
		foreach ($files as $file) {
			if ($file->ext != "flv") {
				continue;
			}

			$location = MiwoVideos::get('utility')->getVideoFilePath($file->video_id, $size, $file->source, 'path');

			if (!file_exists($location)) {
				MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_SOURCE_MEDIA_NOT_EXIST'));
				continue;
			}

			switch ($this->config->get('metadata_injector')) {
				case "flvtool2":
					if (substr(PHP_OS, 0, 3) == "WIN") {
						$command = "\"".$this->config->get('flvtool2_path', '/usr/bin/flvtool2')."\" -U $location 2>&1";
					}
					else {
						$command = $this->config->get('flvtool2_path', '/usr/bin/flvtool2')." -U $location 2>&1";
					}
					break;
				case "yamdi":
					if (substr(PHP_OS, 0, 3) == "WIN") {
						$command = "\"".$this->config->get('yamdi_path', '/usr/bin/yamdi')."\" -i $location -s -k -w -o tempfile 2>&1";
					}
					else {
						$command = $this->config->get('yamdi_path', '/usr/bin/yamdi')." -i $location -s -k -w -o tempfile 2>&1";
					}
					break;
				default:
					$command = '';
					break;
			}
			exec($command, $output);
			if (empty($output)) {
				$ret = true and $ret;
			}
			else {
				$ret = false and $ret;
			}
		}

		if ($ret) {
			return true;
		}
		else {
			return false;
		}
	}

	public function checkMoovAtoms($process) {
		$config = $this->config;
		$item   = MiwoVideos::getTable('MiwovideosVideos');
		$item->load($process->video_id);
		$files = MiwoVideos::get('files')->getVideoFiles($item->id);
		if (empty($process->size)) {
			$size = MiwoVideos::get('utility')->getVideoSize(MIWOVIDEOS_UPLOAD_DIR.'/videos/' . $item->id . '/orig/' . $item->source);
		}
		else {
			$size = $process->size;
		}

		if (empty($files) or empty($size)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_EMPTY_FILE_OR_SIZE'));
			return false;
		}

		$ret = false;
		foreach ($files as $file) {
			if ($file->ext != "mp4") {
				continue;
			}

			$location     = MiwoVideos::get('utility')->getVideoFilePath($file->video_id, $size, $file->source, 'path');
			$new_location = $location . '.tmp';

			if (file_exists($location)) {
				if (substr(PHP_OS, 0, 3) == "WIN") {
					$command = "\"" . $config->get('qt_faststart_path', '/usr/local/bin/qt-faststart') . "\" $location $new_location 2>&1";
				}
				else {
					$command = $config->get('qt_faststart_path', '/usr/local/bin/qt-faststart') . " $location $new_location 2>&1";
				}
				exec($command, $output);

				MiwoVideos::log('FFmpeg : ' . $command);
				MiwoVideos::log($output);

			}
			else {
				MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_SOURCE_VIDEO_NOT_EXIST'));
			}

			if (!file_exists($new_location)) {
				continue;
			}

			mimport('framework.filesystem.file');

			// Remove original MP4 file
			MFile::delete($location);

			// Copy temp file
			if (MFile::copy($new_location, $location)) {
				MFile::delete($new_location);
				$ret = true;
			}
		}

		if ($ret) {
			return true;
		}
		else {
			return false;
		}
	}

	public function getTitle($process) {
		$config = $this->config;

		$row = MiwoVideos::getTable('MiwovideosVideos');
		$row->load($process->video_id);

		$location = MIWOVIDEOS_UPLOAD_DIR.'/videos/' . $row->id . '/orig/' . $row->source;

		if (!file_exists($location)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_DESTINATION_VIDEO_NOT_EXIST'));
			return false;
		}
		// Get information on original
		if (substr(PHP_OS, 0, 3) == "WIN") {
			$command = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -i $location -f ffmetadata " . MPATH_CACHE . "/metadata" . $row->id . ".ini 2>&1";
			exec($command, $output);
		}
		else {
			$command = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -i $location -f ffmetadata " . MPATH_CACHE . "/metadata" . $row->id . ".ini 2>&1";
			exec($command, $output);
		}

		MiwoVideos::log('FFmpeg : ' . $command);
		MiwoVideos::log($output);

		// Load data
		mimport('framework.filesystem.file');
		$ini = MPATH_CACHE . '/metadata' . $row->id . '.ini';

		if (!file_exists($ini)) {
			MiwoVideos::log('metadata.ini file does not exist');
			return false;
		}

		$data = MFile::read($ini);

		$registry = new MRegistry;
		$registry->loadString($data);
		$meta = $registry->toArray();

		if (!empty($meta['title'])) {
			// Create an object to bind to the database
			$data          = array();
			$data['title'] = $meta['title'];

			if (!$row->bind($data)) {
				$this->setError($row->getError());
				return false;
			}

			if (!$row->store()) {
				$this->setError($row->getError());
				return false;
			}

			return true;
		}
		else {
			return true;
		}
	}

	public function processWatermark($process, $fileType, $location) {
		$config = $this->config;

		if ($config->get('watermark') == 0 || $config->get('watermark_path') == '') {
			MiwoVideos::log('Watermark disabled');
			return false;
		}

		$item = MiwoVideos::getTable('MiwovideosVideos');
		$item->load($process->video_id);

		if (file_exists($location)) {
			$logo = MPATH_SITE . '/' . $config->get('watermark_path');

			$new_location = MIWOVIDEOS_UPLOAD_DIR."/videos/" . $item->id . "/" . hash('haval256,5', $logo) . ".tmp";

			switch ($fileType) {
				case 'mp4':
					$vcodec = 'libx264';
					break;
				case 'webm':
					$vcodec = 'libvpx';
					break;
				case 'ogg':
					$vcodec = 'libtheora';
					break;
				case 'ogv':
					$vcodec = 'libtheora';
					break;
				default:
					return false;
			}

			switch ($config->get('watermark_position')) {
				case 1:
					// Top left
					$overlay = '10:10';
					break;
				case 2:
					// Top right
					$overlay = 'main_w-overlay_w-10:10';
					break;
				case 4:
					// Bottom left
					$overlay = '10:main_h-overlay_h-10';
					break;
				default:
					// Bottom right
					$overlay = 'main_w-overlay_w-10:main_h-overlay_h-10';
					break;
			}

			// Get information on original
			if (substr(PHP_OS, 0, 3) == "WIN") {
				$command = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -i $location 2>&1";
				exec($command, $output);
			}
			else {
				$command = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -i $location 2>&1";
				exec($command, $output);
			}

			MiwoVideos::log('FFmpeg : ' . $command);
			MiwoVideos::log($output);

			$flatoutput = is_array($output) ? implode("\n", $output) : $output;
			if (empty($flatoutput)) {
				MiwoVideos::log('Flatoutput is empty');
				return false;
			}
			else {
				$pos = strpos($flatoutput, "No such file or directory");
				if ($pos !== false) {
					MiwoVideos::log('No such file or directory');
					return false;
				}

				$pos = strpos($flatoutput, "not found");
				if ($pos !== false) {
					MiwoVideos::log('Not found');
					return false;
				}

				$pos = strpos($flatoutput, "Permission denied");
				if ($pos !== false) {
					MiwoVideos::log('Permission denied');
					return false;
				}
			}

			// Get original size
			if (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tbr/', implode("\n", $output), $matches)) {
				$width  = $matches[1]/2;
			}
			elseif (preg_match('/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+|\d+) tb/', implode("\n", $output), $matches)) {
				$width  = $matches[1]/2;
			}

			try {
				if (isset($width)) {
					if (substr(PHP_OS, 0, 3) == "WIN") {
						$input = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -y -i $location -i $logo -filter_complex \"[1:v]scale=".$width.":-1 [ovrl], [0:v][ovrl]overlay=$overlay\" -vcodec $vcodec -acodec copy -f $fileType $new_location 2>&1";
						exec($input, $output);
					}
					else {
						$input = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -y -i $location -i $logo -filter_complex \"[1:v]scale=".$width.":-1 [ovrl], [0:v][ovrl]overlay=$overlay\" -vcodec $vcodec -acodec copy -f $fileType $new_location 2>&1";
						exec($input, $output);
					}
				}
				else {
					if (substr(PHP_OS, 0, 3) == "WIN") {
						$logo = preg_replace('|^([a-z]{1}):|i', '', $logo); //Strip out windows drive letter if it's there.
						$logo = str_replace('\\', '/', $logo); //Windows path sanitisation
						$input = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -y -i $location -vf \"movie=" . $logo . " [logo];[in][logo] overlay=" . $overlay . " [out]\" -vcodec $vcodec -acodec copy -f $fileType $new_location 2>&1";
						exec($input, $output);
					}
					else {
						$input = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -y -i $location -vf \"movie=" . $logo . " [logo];[in][logo] overlay=" . $overlay . " [out]\" -vcodec $vcodec -acodec copy -f $fileType $new_location 2>&1";
						exec($input, $output);
					}
				}
				MiwoVideos::log('FFmpeg : ' . $input);
				MiwoVideos::log($output);

				mimport('framework.filesystem.file');
				if (file_exists($new_location) && filesize($new_location) > 0) {
					if (MFile::copy($new_location, $location)) {
						MFile::delete($new_location);
					}
				}
				else if (file_exists($new_location) && filesize($new_location) == 0) {
					MFile::delete($new_location);
				}
			} catch (Exception $e) {
				MiwoVideos::log($e->getMessage());
			}
		}

		return true;
	}

	public function processFrames($process, $location) {
		$config = $this->config;

		if ($config->get('frames') == 0) {
			MiwoVideos::log('Frames disabled');
			return false;
		}

		$item = MiwoVideos::getTable('MiwovideosVideos');
		$item->load($process->video_id);

		if (!file_exists($location)) {
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_DESTINATION_VIDEO_NOT_EXIST'));
			return false;
		}

		MFolder::create(MIWOVIDEOS_UPLOAD_DIR."/images/videos/" . $item->id . "/frames/");
		$frames_location = MIWOVIDEOS_UPLOAD_DIR."/images/videos/" . $item->id . "/frames/";

		for ($i = 0; $i <= $item->duration; $i++) {
			try {
				if (substr(PHP_OS, 0, 3) == "WIN") {
					$input = "\"" . $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . "\" -itsoffset -$i -y -i $location -vcodec mjpeg -vframes 1 -an -f rawvideo -s 100x100 " . $frames_location . "out" . $i . ".jpg 2>&1";
					exec($input, $output);
				}
				else {
					$input = $config->get('ffmpeg_path', '/usr/local/bin/ffmpeg') . " -itsoffset -$i -y -i $location -vcodec mjpeg -vframes 1 -an -f rawvideo -s 100x100 " . $frames_location . "out" . $i . ".jpg 2>&1";
					exec($input, $output);
				}

				MiwoVideos::log('FFmpeg : ' . $input);
				MiwoVideos::log($output);

			} catch (Exception $e) {
				MiwoVideos::log($e->getMessage());
			}
		}

		return true;
	}

	public function convertToHtml5($video_id = null, $filename = null) {
		$json = array();
		if (!MiwoVideos::get('utility')->getFfmpegVersion()) {
			$json = array(
				'success' => 1,
				'href'    => MiwoVideos::get('utility')->route('index.php?option=com_miwovideos&view=videos&task=edit&cid[]=1')
			);
			echo json_encode($json);
		}

		empty($video_id) ? $video_id = MRequest::getInt('video_id') : null;
		empty($filename) ? $filename = MRequest::getString('filename') : null;

		if (!file_exists(MIWOVIDEOS_UPLOAD_DIR."/videos/".$video_id."/orig/".$filename)) {
			$json['error'] = MText::_('COM_MIWOVIDEOS_ERROR_SOURCE_VIDEO_NOT_EXIST');
			MiwoVideos::log(MText::_('COM_MIWOVIDEOS_ERROR_SOURCE_VIDEO_NOT_EXIST'));
			echo json_encode($json);
			return false;
		}

		$size       = MiwoVideos::get('utility')->getVideoSize(MIWOVIDEOS_UPLOAD_DIR."/videos/".$video_id."/orig/".$filename);
		$thumb_size = MiwoVideos::get('utility')->getThumbSize($this->config->get('thumb_size'));

		// Convert video to HTML5 mp4/ogg/webm
		$process           = new stdClass();
		$process->video_id = $video_id;

		if (!$this->_runProcesses('processMp4', $process, 'mp4', $size)) return false;
		if (!$this->_runProcesses('processWebm', $process, 'webm', $size)) return false;
		if (!$this->_runProcesses('processOgg', $process, 'ogg', $size)) return false;
		$this->_runProcesses('processThumb', $process, 'jpg', $thumb_size);
		if (!$this->_runProcesses('getDuration', $process)) return false;
		if (!$this->_runProcesses('getTitle', $process)) return false;
		if (!$this->_runProcesses('checkMoovAtoms', $process)) return false;

		if ($this->config->get('frames')) {
			if (!$this->processFrames($process, MIWOVIDEOS_UPLOAD_DIR."/videos/".$video_id."/orig/".$filename)) {
				$json['error'] = MText::sprintf('COM_MIWOVIDEOS_ERROR_X_PROCESSING', 'frames');
				MiwoVideos::log(MText::sprintf('COM_MIWOVIDEOS_ERROR_X_PROCESSING', 'frames'));
				if($this->config->get('upload_script') == 'dropzone') {
					echo json_encode($json);
				}
				return false;
			}
		}

		if($this->config->get('upload_script') == 'dropzone') {
			$json = array(
				'success' => 1,
				'href'    => MiwoVideos::get('utility')->route('index.php?option=com_miwovideos&view=videos&task=edit&cid[]='.$video_id)
			);
			echo json_encode($json);
		}

		return true;
	}

	protected function _runProcesses($method, $process, $type = null, $size = null) {
		if (empty($type) and empty($size)) {
			$ret = $this->$method($process);
		} else {
			$ret = $this->$method($process, $type, $size, 1);
		}

		if (!$ret) {
			$json['error'] = MText::sprintf('COM_MIWOVIDEOS_ERROR_X_PROCESSING', $type);
			MiwoVideos::log(MText::sprintf('COM_MIWOVIDEOS_ERROR_X_PROCESSING', $type));
			if ($this->config->get('upload_script') == 'dropzone') {
				echo json_encode($json);
			}
			return false;
		}
		return true;
	}

}