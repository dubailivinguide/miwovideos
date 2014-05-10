<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright  ( C ) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die ;

if (count($this->items)) {
    $utility = MiwoVideos::get('utility');
    $thumb_size = $utility->getThumbSize($this->config->get('thumb_size'));
    $k = 0;
	foreach ($this->items as $item) {
        $this->Itemid = MiwoVideos::get('router')->getItemid(array('view' => 'playlist', 'playlist_id' => $item->id), null, true);
        $playlist_url = MRoute::_('index.php?option=com_miwovideos&view=playlist&playlist_id='.$item->id.$this->Itemid);
        $channel_url = MRoute::_('index.php?option=com_miwovideos&view=channel&channel_id='.$item->channel_id.$this->Itemid);
        $video_id = empty($item->videos) ? '' : $item->videos[0]->video_id;
        if(!empty($item->thumb)) {
            $thumb = $utility->getThumbPath($item->id, 'playlists', $item->thumb);
        } else {
            $thumb = $utility->getThumbPath($video_id, 'videos', $item->videos[0]->thumb);
        }
		$url = MRoute::_('index.php?option=com_miwovideos&view=video&video_id='.$video_id.'&playlist_id='.$item->id.$this->Itemid); ?>
        <div class="videos-items-list-box">
            <div class="playlists-list-item" style="width: <?php echo $thumb_size; ?>px">
                <div class="videos-aspect<?php echo $this->config->get('thumb_aspect'); ?>"></div>
                <a href="<?php echo $url; ?>">
                    <img class="videos-items-grid-thumb" src="<?php echo $thumb; ?>" alt="<?php echo $item->thumb; ?>"/>
                </a>
            </div>
            <div class="playlists-items-list-box-content">
                <h3 class="miwovideos_box_h3">
                    <a href="<?php echo $url; ?>" title="<?php echo $item->title; ?>">
                        <?php echo $this->escape(MHtmlString::truncate($item->title, $this->config->get('title_truncation'), false, false)); ?>
                    </a>
                </h3>
                <div class="playlists-meta">
                    <div class="miwovideos-meta-info">
                        <div class="created_by">
							<span class="key"><?php echo MText::_('COM_MIWOVIDEOS_CREATED_BY'); ?></span>
							<span class="value">
                                <a href="<?php echo $channel_url; ?>" title="<?php echo $item->channel_title; ?>">
                                    <?php echo $this->escape(MHtmlString::truncate($item->channel_title, $this->config->get('title_truncation'), false, false)); ?>
                                </a>
                            </span>
                        </div>
                        <div class="date-created">
                            <span class="key"><?php echo MText::_('COM_MIWOVIDEOS_DATE_CREATED'); ?></span>
                            <span class="value"><?php echo MHtml::_('date', $item->created, MText::_('DATE_FORMAT_LC4')); ?></span>
                        </div>
                    </div>
                </div>
                <div class="playlists-items">
                    <?php $j = 0;
                    foreach($item->videos as $video) { $j++; ?>
                           <div class="playlists-item">
                               <a href="<?php echo MRoute::_('index.php?option=com_miwovideos&view=video&video_id='.$video->video_id.$this->Itemid); ?>">
                                   <?php echo $this->escape(MHtmlString::truncate($video->title, $this->config->get('title_truncation'), false, false)); ?>
                               </a>
                               <span class="miwovideos-duration"><?php echo $utility->secondsToTime($video->duration); ?></span>
                               <?php if ($j == 2) break; ?>
                           </div>
                    <?php } ?>
                </div>
                <div class="playlists-meta">
                    <div class="miwovideos-meta-info">
                        <a class="date-created" href="<?php echo $playlist_url; ?>"><?php echo MText::_('COM_MIWOVIDEOS_VIEW_PLAYLIST'); ?>&nbsp;&nbsp;(<?php echo isset($item->total) ? $item->total : '0' ; ?>&nbsp;<?php echo MText::_('COM_MIWOVIDEOS_VIDEOS')?>)</a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
<?php } ?>
<script type="text/javascript">
    jQuery(document).ready(function() {
        var box_width = document.getElementById("adminForm").offsetWidth;
        var thumb_size = <?php echo $thumb_size; ?>;
        var thumb_percent = Math.round((thumb_size/box_width)*100);
        var desc_percent = 100 - thumb_percent - 3;
        jQuery('div[class^="playlists-items-list-box-content"]').css('width', desc_percent+'%');
        jQuery('div[class^="playlists-list-item"]').css('width', thumb_percent+'%');
    });
</script>