<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die('Restricted access');

if (count($rows)) {
?>
	<table class="eb_video_list" width="100%">
		<?php
			$tabs = array('sectiontableentry1' , 'sectiontableentry2');
			$k = 0 ;
			foreach ($rows as  $row) {
				$tab = $tabs[$k];
				$k = 1 - $k ;

                $Itemid = MiwoVideos::get('router')->getItemid(array('view' => 'video', 'video_id' => $row->id), null, true);
			?>	
				<tr class="<?php echo $tab; ?>">
					<td class="eb_video">
						<a href="<?php echo MRoute::_('index.php?option=com_miwovideos&view=video&video_id='.$row->id . $Itemid); ?>" class="miwovideos_video_link">
                            <?php echo htmlspecialchars(MHtmlString::truncate($row->title, $config->get('title_truncation'), false, false)); ?>
                        </a>
						<br />
						<span class="created"><?php echo MHtml::_('date', $row->created, $config->get('date_format'), null); ?></span>
						<?php
							if ($showCategory) {
							?>
								<br />		
								<span><?php echo MText::_('COM_MIWOVIDEOS_CATEGORY'); ?>:&nbsp;&nbsp;<?php echo $row->categories; ?></span>
							<?php	
							}

							if ($showChannel and strlen($row->channel_title)) {
							?>
								<br />
                                <?php $Itemid = MiwoVideos::get('router')->getItemid(array('view' => 'channel', 'channel_id' => $row->channel_id), null, true); ?>
                                <span><?php echo MText::_('COM_MIWOVIDEOS_SEF_CHANNEL'); ?>:&nbsp;&nbsp;
								    <a href="<?php echo MRoute::_('index.php?option=com_miwovideos&view=channel&channel_id='.$row->channel_id . $Itemid); ?>" title="<?php echo $row->channel_title; ?>" class="channel_link">
                                        <strong>
                                            <?php echo htmlspecialchars(MHtmlString::truncate($row->channel_title, $config->get('title_truncation'), false, false)); ?>
                                        </strong>
                                    </a>
                                </span>
							<?php	 
							}
						?>											
					</td>
				</tr>
			<?php
			}
		?>
	</table>
<?php	
} else {
?>
	<div class="eb_empty"><?php echo MText::_('COM_MIWOVIDEOS_NO_VIDEOS') ?></div>
<?php	
}