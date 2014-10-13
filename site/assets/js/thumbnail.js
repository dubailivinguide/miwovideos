/*
 * @package		MiwoVideos
 * @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
jQuery(window).load(function() {
    var galleryImgs = jQuery('.videos-items-grid-thumb');
    if (galleryImgs.length > 0) {
        galleryImgs.each(function(index) {
            var parent = this.getParent(".videos-grid-item, .videos-list-item, .playlists-list-item");
            var container = parent.getSize().y/2;
            var margin = (container - (this.height/2));
            this.setStyle('margin-top', margin + 'px');
        });
    }
	
	jQuery('.miwovideos_iframe_youtube').height(jQuery('.miwovideos_iframe_youtube').width()*0.5625);
});

