<?php
/**
 * @package        MiwoVideos
 * @copyright      2009-2014 Miwisoft LLC, miwisoft.com
 * @license        GNU General Public License version 2 or later
 */
# No Permission
defined('_MEXEC') or die;

class MiwovideosModelConfig extends MiwovideosModel {

    public function __construct() {
        parent::__construct('config');
    }

    // Save configuration
    function save() {
        $config = MiwoVideos::getConfig();

        // General
        $config->set('version_checker', MRequest::getVar('version_checker', 1, 'post', 'int'));
        $config->set('pid', MRequest::getVar('pid', '', 'post', 'string'));
        $config->set('jusersync', MRequest::getVar('jusersync', $config->get('jusersync', 0), 'post', 'int'));
        $config->set('log', MRequest::getVar('log', 0, 'post', 'int'));
        $config->set('categories', MRequest::getVar('categories', 1, 'post', 'int'));
        $config->set('playlists', MRequest::getVar('playlists', 1, 'post', 'int'));
        $config->set('tags', MRequest::getVar('tags', 1, 'post', 'int'));
        $config->set('subscriptions', MRequest::getVar('subscriptions', 1, 'post', 'int'));
        $config->set('likes_dislikes', MRequest::getVar('likes_dislikes', 1, 'post', 'int'));
        $config->set('custom_fields', MRequest::getVar('custom_fields', 1, 'post', 'int'));
        $config->set('reports', MRequest::getVar('reports', 1, 'post', 'int'));
        $config->set('comments', MRequest::getVar('comments', 0, 'post', 'string'));
        $config->set('list', MRequest::getVar('list', 0, 'post', 'string'));

        // Frontend
        $config->set('button_class', MRequest::getVar('button_class', 'miwovideos_button', 'post', 'string'));
        $config->set('override_color', MRequest::getVar('override_color', '#dc2f2c', 'post', 'string'));
        $config->set('videos_per_page', MRequest::getVar('videos_per_page', 6, 'post', 'int'));
        $config->set('load_plugins', MRequest::getVar('load_plugins', 0, 'post', 'int'));
        $config->set('show_empty_cat', MRequest::getVar('show_empty_cat', 0, 'post', 'int'));
        $config->set('show_number_videos', MRequest::getVar('show_number_videos', 1, 'post', 'int'));
        $config->set('order_videos', MRequest::getVar('order_videos', 2, 'post', 'int'));
        $config->set('listing_style', MRequest::getVar('listing_style', 'grid', 'post', 'string'));
        $config->set('title_truncation', MRequest::getVar('title_truncation', 20, 'post', 'int'));
        $config->set('desc_truncation', MRequest::getVar('desc_truncation', 150, 'post', 'imt'));
        $config->set('thumb_size', MRequest::getVar('thumb_size', 3, 'post', 'int'));
        $config->set('thumb_aspect', MRequest::getVar('thumb_aspect', 43, 'post', 'int'));
        $config->set('items_per_column', MRequest::getVar('items_per_column', 3, 'post', 'int'));

        // Player
        $config->set('video_player', MRequest::getVar('video_player', 'videojs', 'post', 'string'));
        $config->set('video_quality', MRequest::getVar('video_quality', 480, 'post', 'int'));
        $config->set('autoplay', MRequest::getVar('autoplay', 1, 'post', 'int'));

        // Upload
        $config->set('video_upload', MRequest::getVar('video_upload', 1, 'post', 'int'));
        $config->set('perl_upload', MRequest::getVar('perl_upload', 1, 'post', 'int'));
        $config->set('remote_upload', MRequest::getVar('remote_upload', 1, 'post', 'int'));
        $config->set('upload_script', MRequest::getVar('upload_script', 'dropzone', 'post', 'string'));
        $config->set('allow_file_types', MRequest::getVar('allow_file_types', 'mov|mpeg|divx|flv|mpg|avi|mp4|mkv', 'post', 'string'));
        $config->set('upload_max_filesize', MRequest::getVar('upload_max_filesize', 128, 'post', 'int'));

        // Processing
        $config->set('process_videos', MRequest::getVar('search_char', 1, 'post', 'int'));
        $config->set('auto_process_videos', MRequest::getVar('auto_process_videos', 1, 'post', 'int'));
        $config->set('frames', MRequest::getVar('frames', 1, 'post', 'int'));
        $config->set('watermark', MRequest::getVar('watermark', 1, 'post', 'int'));
        $config->set('watermark_position', MRequest::getVar('watermark_position', 4, 'post', 'int'));
        $config->set('watermark_path', MRequest::getVar('watermark_path', '', 'post', 'string'));
        $config->set('jpeg_75', MRequest::getVar('jpeg_75', 1, 'post', 'int'));
        $config->set('jpeg_100', MRequest::getVar('jpeg_100', 1, 'post', 'int'));
        $config->set('jpeg_240', MRequest::getVar('jpeg_240', 1, 'post', 'int'));
        $config->set('jpeg_500', MRequest::getVar('jpeg_500', 1, 'post', 'int'));
        $config->set('jpeg_640', MRequest::getVar('jpeg_640', 1, 'post', 'int'));
        $config->set('jpeg_1024', MRequest::getVar('jpeg_1024', 1, 'post', 'int'));
        $config->set('mp4_240p', MRequest::getVar('mp4_240p', 1, 'post', 'int'));
        $config->set('mp4_360p', MRequest::getVar('mp4_360p', 1, 'post', 'int'));
        $config->set('mp4_480p', MRequest::getVar('mp4_480p', 1, 'post', 'int'));
        $config->set('mp4_720p', MRequest::getVar('mp4_720p', 1, 'post', 'int'));
        $config->set('mp4_1080p', MRequest::getVar('mp4_1080p', 1, 'post', 'int'));
        $config->set('webm_240p', MRequest::getVar('webm_240p', 1, 'post', 'int'));
        $config->set('webm_360p', MRequest::getVar('webm_360p', 1, 'post', 'int'));
        $config->set('webm_480p', MRequest::getVar('webm_480p', 1, 'post', 'int'));
        $config->set('webm_720p', MRequest::getVar('webm_720p', 1, 'post', 'int'));
        $config->set('webm_1080p', MRequest::getVar('webm_1080p', 1, 'post', 'int'));
        $config->set('ogg_240p', MRequest::getVar('ogg_240p', 1, 'post', 'int'));
        $config->set('ogg_360p', MRequest::getVar('ogg_360p', 1, 'post', 'int'));
        $config->set('ogg_480p', MRequest::getVar('ogg_480p', 1, 'post', 'int'));
        $config->set('ogg_720p', MRequest::getVar('ogg_720p', 1, 'post', 'int'));
        $config->set('ogg_1080p', MRequest::getVar('ogg_1080p', 1, 'post', 'int'));

        // Server
	    $config->set('php_path', MRequest::getVar('php_path', '/usr/local/php', 'post', 'string'));
        $config->set('ffmpeg_path', MRequest::getVar('ffmpeg_path', '/usr/local/bin/ffmpeg', 'post', 'string'));
        $config->set('qt_faststart_path', MRequest::getVar('qt_faststart_path', '/usr/local/bin/qt-faststart', 'post', 'string'));
        $config->set('uber_upload_perl_url', MRequest::getVar('uber_upload_perl_url', '', 'post', 'string'));
        $config->set('uber_upload_tmp_path', MRequest::getVar('uber_upload_tmp_path', '/tmp/ubr_temp/', 'post', 'string'));

        Miwovideos::get('utility')->storeConfig($config);

        $this->cleanCache('_system');
    }
}