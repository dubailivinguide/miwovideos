<?php
/**
 * @package        MiwoVideos
 * @copyright      Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license        GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die;

$utility = MiwoVideos::get('utility');
?>
	<!-- categories -->
<?php if (($this->params->get('show_page_heading', '0') == '1')) { ?>
	<?php $page_title = $this->params->get('page_title', ''); ?>

	<?php if (!empty($this->category->title)) { ?>
		<h1><?php echo $this->category->title; ?></h1>
	<?php
	}
	else if (!empty($page_title)) {
		?>
		<h1><?php echo $page_title; ?></h1>
	<?php } ?>
<?php } ?>

<?php if (!empty($this->category->id)) { ?>
	<div class="miwovideos_cat">
		<img class="category-item-thumb80" src="<?php echo $utility->getThumbPath($this->category->id, 'categories', $this->category->thumb); ?>" title="<?php echo $this->category->title; ?>" alt="<?php echo $this->category->title; ?>"/>
		<?php if (!empty($this->category->introtext) or !empty($this->category->fulltext)) { ?>
			<div class="miwi_description"><?php echo $this->category->introtext.$this->category->fulltext; ?></div>
		<?php } ?>
	</div>
	<div class="clr"></div>
<?php
}

if (!empty($this->categories)) {
	?>

	<div id="miwovideos_cats">
		<?php if (!empty($this->category->id)) {
			; ?>
			<h2 class="miwovideos_title"><?php echo MText::_('COM_MIWOVIDEOS_SUB_CATEGORIES'); ?></h2>
		<?php } ?>

		<?php
		foreach ($this->categories as $category) {

			$link = MRoute::_('index.php?option=com_miwovideos&view=category&category_id='.$category->id.$this->Itemid);
			?>

			<div class="miwovideos_box">
				<div class="miwovideos_box_heading">
					<h3 class="miwovideos_box_h3">
						<a href="<?php echo $link; ?>" title="<?php echo $category->title; ?>">
							<img class="category-item-thumb16" src="<?php echo $utility->getThumbPath($category->id, 'categories', $category->thumb); ?>" title="<?php echo $category->title; ?>" alt="<?php echo $category->title; ?>"/>
							<?php echo $category->title; ?>
							<?php if ($this->config->get('show_number_videos')) { ?>
								<small>
									( <?php echo $category->total_videos; ?> <?php echo $category->total_videos > 1 ? MText::_('COM_MIWOVIDEOS_VIDEOS') : MText::_('COM_MIWOVIDEOS_VIDEO'); ?>
									)
								</small>
							<?php } ?>
						</a>
					</h3>
				</div>
				<?php if (!empty($category->introtext)) { ?>
					<div class="miwovideos_box_content">
						<?php echo $this->escape(MHtmlString::truncate($category->introtext, $this->config->get('desc_truncation'), false, false)); ?>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
	<?php if ($this->category->id == 0) { ?>
		<?php if ($this->pagination->total > $this->pagination->limit) { ?>
			<tfoot>
			<tr>
				<td colspan="5">
					<div class="pagination">
						<?php echo $this->pagination->getListFooter(); ?>
					</div>
				</td>
			</tr>
			</tfoot>
		<?php } ?>
	<?php } ?>
	<div class="clr"></div>
<?php
}
?>
	<!-- categories -->

	<!-- category -->
<?php if ($this->category->id != 0) { ?>
	<form method="post" name="adminForm" id="adminForm" action="<?php echo MRoute::_('index.php?option=com_miwovideos&view=category&category_id='.$this->category->id.$this->Itemid); ?>">
		<!-- Videos List -->
		<?php if (count($this->items)) { ?>
			<div id="miwovideos_docs">
				<h2 class="miwovideos_title"><?php echo MText::_('COM_MIWOVIDEOS_VIDEOS'); ?></h2>
				<?php
				foreach ($this->items as $item) {
					$url       = MRoute::_('index.php?option=com_miwovideos&view=video&video_id='.$item->id.$this->Itemid);
					$template  = MFactory::getApplication()->getTemplate();
					$ovrr_path = MPATH_WP_CNT.'/themes/'.$template.'/html/com_miwovideos/video/common.php';

					if (file_exists($ovrr_path)) {
						include $ovrr_path;
					}
					else {
						include MPATH_MIWOVIDEOS.'/views/video/tmpl/common.php';
					}
				}
				?>
			</div>
			<?php if ($this->pagination->total > $this->pagination->limit) { ?>
				<tfoot>
				<tr>
					<td colspan="5">
						<div class="pagination">
							<?php echo $this->pagination->getListFooter(); ?>
						</div>
					</td>
				</tr>
				</tfoot>
			<?php } ?>
		<?php
		}
		else {
			?>
			<div class="miwovideos_box">
				<div class="miwovideos_box_heading"><h3 class="miwovideos_box_h3"></h3></div>
				<div class="miwovideos_box_content">
					<div id="miwovideos_docs">
						<i><?php echo MText::_('COM_MIWOVIDEOS_NO_VIDEOS'); ?></i>
					</div>
				</div>
			</div>
		<?php } ?>

		<input type="hidden" name="option" value="com_miwovideos"/>
		<input type="hidden" name="view" value="category"/>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="Itemid" value="<?php echo $this->Itemid; ?>"/>
		<input type="hidden" name="id" value="0"/>
	</form>
<?php } ?>