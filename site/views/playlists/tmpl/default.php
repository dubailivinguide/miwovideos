<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die ;

$page_title = $this->params->get('page_title', '');
if (($this->params->get('show_page_heading', '0') == '1') && !empty($page_title)) { $page_title; } ?>

<div class="miwovideos_box">
	<div class="miwovideos_box_heading">
		<h1 class="miwovideos_box_h1"><?php echo $page_title; ?></h1>
	</div>

	<div class="miwovideos_box_content">
	<!-- content -->
		<form method="post" name="adminForm" id="adminForm" action="<?php echo MRoute::_('index.php?option=com_miwovideos&view=playlists'.$this->Itemid); ?>">
		    <div class="miwovideos_subheader">
                <div class="miwovideos_searchbox">
                    <input type="text" name="miwovideos_search" id="miwovideos_search" placeholder="Search..." value="<?php echo empty($this->lists['search']) ? "" : $this->lists['search']; ?>" onchange="document.adminForm.submit();" />
                </div>
                <div class="miwovideos_flow_select">
                    <?php  $grid = $list = '';
                    if(strpos($this->display, 'grid') !== false) {
                        $grid = 'active';
                    } else {
                        $list = 'active';
                    } ?>
                    <a class="<?php echo MiwoVideos::getButtonClass(); ?> <?php echo $grid; ?>" href="<?php echo MRoute::_('index.php?option=com_miwovideos&view=playlists&display=grid'.$this->Itemid); ?>" title="<?php echo MText::_('COM_MIWOVIDEOS_GRID'); ?>"><?php echo MText::_('COM_MIWOVIDEOS_GRID'); ?></a>
                    <a class="<?php echo MiwoVideos::getButtonClass(); ?> <?php echo $list; ?>" href="<?php echo MRoute::_('index.php?option=com_miwovideos&view=playlists&display=list'.$this->Itemid); ?>" title="<?php echo MText::_('COM_MIWOVIDEOS_LIST'); ?>"><?php echo MText::_('COM_MIWOVIDEOS_LIST'); ?></a>
                </div>
            </div>
		    <input type="hidden" name="option" value="com_miwovideos" />
		    <input type="hidden" name="view" value="playlists" />
		    <input type="hidden" name="task" value="" />
		    <input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
		    <input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
		    <?php echo MHtml::_('form.token'); ?>
			<div class="clr"></div>
			<?php echo $this->loadTemplate($this->display); ?>
			<?php
			if ($this->pagination->total > $this->pagination->limit) {
				?>
				<tfoot>
				<tr>
					<td colspan="5">
						<div class="pagination">
							<?php echo $this->pagination->getListFooter(); ?>
						</div>
					</td>
				</tr>
				</tfoot>
			<?php
			}
			?>
		</form>
	<!-- content // -->
	</div>
</div>