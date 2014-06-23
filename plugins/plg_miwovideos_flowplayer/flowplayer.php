<?php
/**
 * @package        MiwoVideos
 * @copyright      Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license        GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die ('Restricted access');

mimport('framework.plugin.plugin');
require_once(MPATH_WP_PLG.'/miwovideos/admin/library/miwovideos.php');

class plgMiwovideosFlowPlayer extends MPlugin {

	public    $width;
	public    $height;
	public    $pluginParams;
	public    $output;
	protected $item = null;

	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
		$this->config = MiwoVideos::getConfig();
	}

	public function getPlayer(&$output, $pluginParams, $item) {
		?>
		<div class="miwi_paid">
			<strong><?php echo MText::sprintf('MLIB_X_PRO_MEMBERS', 'Flowplayer'); ?></strong><br /><br />
			<?php echo MText::sprintf('MLIB_PRO_MEMBERS_DESC', 'http://miwisoft.com/wordpress-plugins/miwovideos-share-your-videos#pricing', 'MiwoVideos'); ?>
		</div>
		<?php
		ob_start();
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}
