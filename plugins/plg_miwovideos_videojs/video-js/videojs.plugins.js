vjs.plugin('resolutions', function(options) {
    player = this;
    // 'reduce' utility method
    // @param {Array} array to iterate over
    // @param {Function} iterator function for collector
    // @param {Array|Object|Number|String} initial collector
    // @return collector
    vjs.reduce = function(arr, fn, init, n) {
        if (!arr || arr.length === 0) { return; }
        for (var i=0,j=arr.length; i<j; i++) {
            init = fn.call(arr, init, arr[i], i);
        }
        return init;
    };

    this.resolutions_ = {
        options_: {},

        // it is necessary to remove the sources from the DOM after
        // parsing them because otherwise the native player may be
        // inclined to stream both sources
        removeSources: function(el){
            var videoEl = player.el_.getElementsByTagName("video")[0];

            if (player.techName !== "Html5" || !videoEl) return;

            var srcs = videoEl.getElementsByTagName("source");
            for(var i=0;i<srcs.length;i++){
                videoEl.removeChild(srcs[i]);
            }
        },


        bucketByTypes: function(sources){
            return vjs.reduce(sources, function(init, val, i){
                (init[val.type] = init[val.type] || []).push(val);
                return init;
            }, {}, player);
        },


        selectSource: function(sources){
            this.removeSources();

            var sourcesByType = this.bucketByTypes(sources);
            var typeAndTech   = this.selectTypeAndTech(sources);

            if (!typeAndTech) return false;

            // even though we choose the best resolution for the user here, we
            // should remember the resolutions so that we can potentially
            // change resolution later
            this.options_['sourceResolutions'] = sourcesByType[typeAndTech.type];

            return this.selectResolution(this.options_['sourceResolutions']);
        },


        selectTypeAndTech: function(sources) {
            var techName;
            var tech;

            for (var i=0,j=player.options_['techOrder'];i<j.length;i++) {
                techName = videojs.capitalize(j[i]);
                tech     = window['videojs'][techName];

                // Check if the browser supports this technology
                if (tech.isSupported()) {
                    // Loop through each source object
                    for (var a=0,b=sources;a<b.length;a++) {
                        var source = b[a];
                        // Check if source can be played with this technology
                        if (tech['canPlaySource'](source)) {
                            return { type: source.type, tech: techName };
                        }
                    }
                }
            }
        },


        selectResolution: function(typeSources) {
            var defaultRes = 0;
            var supportsLocalStorage = !!window.localStorage;

            // check to see if any sources are marked as default
            videojs.obj.each(typeSources, function(i, s){
                // add the index here so we can reference it later
                s.index = parseInt(i, 10);

                if (s['data-default']) defaultRes = s.index;
            }, player);

            // if the user has previously selected a preference, check if
            // that preference is available. if not, use the source marked
            // default
            var preferredRes = defaultRes;

            // trying to follow the videojs code conventions of if statements
            if (supportsLocalStorage){
                var storedRes = parseInt(window.localStorage.getItem('videojs_preferred_res'), 10);

                if (!isNaN(storedRes))
                    preferredRes = storedRes;
            }

            var maxRes    = (typeSources.length - 1);
            var actualRes = preferredRes > maxRes ? maxRes : preferredRes;

            return typeSources[actualRes];
        }
    };

    // convenience method
    // @return {String} cached resolution label:
    // "SD"
    player.resolution = function(){
        return this.cache_.src.res;
    };

    player.changeResolution = function(new_source){
        // has the exact same source been chosen?
        if (this.cache_.src === new_source.src){
            this.trigger('resolutionchange');
            return this; // basically a no-op
        }

        // remember our position and playback state
        var curTime      = this.currentTime();
        var remainPaused = this.paused();

        // pause playback
        this.pause();

        // attempts to stop the download of the existing video
        //this.resolutions_.stopStream();

        // HTML5 tends to not recover from reloading the tech but it can
        // generally handle changing src.  Flash generally cannot handle
        // changing src but can reload its tech.
        if (this.techName === "Html5"){
            this.src(new_source.src);
        } else {
            this.loadTech(this.techName, {src: new_source.src});
        }

        // when the technology is re-started, kick off the new stream
        this.ready(function() {
            this.one('loadeddata', vjs.bind(this, function() {
                this.currentTime(curTime);
            }));

            this.trigger('resolutionchange');

            if (!remainPaused) {
                this.load();
                this.play();
            }

            // remember this selection
            vjs.setLocalStorage('videojs_preferred_res', parseInt(new_source.index, 10));
        });
    };

    /* Resolution Menu Items
     ================================================================================ */
    var ResolutionMenuItem = videojs.MenuItem.extend({
        init: function(player, options){
            // Modify options for parent MenuItem class's init.
            options['label'] = options.source['data-res'];
            videojs.MenuItem.call(this, player, options);

            this.source = options.source;
            this.resolution = options.source['data-res'];

            this.player_.one('loadstart', vjs.bind(this, this.update));
            this.player_.on('resolutionchange', vjs.bind(this, this.update));
        }
    });

    ResolutionMenuItem.prototype.onClick = function(){
        videojs.MenuItem.prototype.onClick.call(this);
        this.player_.changeResolution(this.source);
    };

    ResolutionMenuItem.prototype.update = function(){
        var player = this.player_;
        if ((player.cache_['src'] === this.source.src)) {
            this.selected(true);
        } else {
            this.selected(false);
        }
    };

    /* Resolutions Button
     ================================================================================ */
    var ResolutionButton = videojs.MenuButton.extend({
        init: function(player, options) {
            videojs.MenuButton.call(this, player, options);

            if (this.items.length <= 1) {
                this.hide();
            }
        }
    });

    ResolutionButton.prototype.sourceResolutions_;

    ResolutionButton.prototype.sourceResolutions = function() {
        return this.sourceResolutions_;
    };

    ResolutionButton.prototype.onClick = function(e){
        // Only proceed if the target of the click was a DIV (just the button and its inner div, not the menu)
        // This prevents the menu from opening and closing when one of the menu items is clicked.
        if (e.target.className.match(/vjs-control-content/)) {

            // Toggle the 'touched' class
            this[this.el_.className.match(/touched/) ? "removeClass" : "addClass"]("touched");
        } else {

            // Remove the 'touched' class from all control bar buttons with menus to hide any already visible...
            var buttons = document.getElementsByClassName('vjs-menu-button');
            for(var i=0;i<buttons.length;i++){
                videojs.removeClass(buttons[i], 'touched');
            }

            this.removeClass('touched');
        }
    };

    ResolutionButton.prototype.createItems = function(){
        var resolutions = this.sourceResolutions_ = this.player_.resolutions_.options_['sourceResolutions'];
        var items = [];
        for (var i = 0; i < resolutions.length; i++) {
            items.push(new ResolutionMenuItem(this.player_, {
                'source': this.sourceResolutions_[i]
            }));
        }
        return items;
    };

    /**
     * @constructor
     */
    ResolutionsButton = ResolutionButton.extend({
        /** @constructor */
        init: function(player, options, ready){
            ResolutionButton.call(this, player, options, ready);
            this.el_.setAttribute('aria-label','Resolutions Menu');
            this.el_.setAttribute('id',"vjs-resolutions-button");
        }
    });

    ResolutionsButton.prototype.kind_ = 'resolutions';
    ResolutionsButton.prototype.buttonText = 'Resolutions';
    ResolutionsButton.prototype.className = 'vjs-resolutions-button';

    // Add Button to controlBar
    videojs.obj.merge(player.controlBar.options_['children'], {
        'resolutionsButton': {}
    });

    // let's get the party started!
    // we have to grab the parsed sources and select the source with our
    // resolution-aware source selector
    var source = player.resolutions_.selectSource(player.options_['sources']);

    // when the player is ready, add the resolution button to the control bar
    player.ready(function(){
        player.changeResolution(source);
        var button = new ResolutionsButton(player);
        player.controlBar.addChild(button);
    });
});

vjs.plugin('watchlater', function(options) {
    var player = this;
    var option = options;
    /**
     * Toggle watchlater video
     * @param {videojs.Player|Object} player
     * @param {Object=} options
     * @class
     * @extends videojs.Button
     */
    videojs.WatchlaterToggle = videojs.MenuButton.extend({
        /**
         * @constructor
         * @memberof videojs.WatchlaterToggle
         * @instance
         */
        init: function(player, options){
            videojs.MenuButton.call(this, player, options);
            this.el_.setAttribute('aria-label','Watch Later');
            this.el_.setAttribute('aria-haspopup','true');
        }
    });

    videojs.WatchlaterToggle.prototype.kind_ = 'watchlater';
    videojs.WatchlaterToggle.prototype.buttonText = 'Watch Later';

    videojs.WatchlaterToggle.prototype.buildCSSClass = function(){
        if (option == 'already_added') {
            return videojs.Button.prototype.buildCSSClass.call(this)+'vjs-watchlater-control vjs-menu-button watchlater-success';
        } else {
            return videojs.Button.prototype.buildCSSClass.call(this)+'vjs-watchlater-control vjs-menu-button';
        }
    };

    var WatchlaterPopup = videojs.MenuItem.extend({
        init: function(player, options){
            // Modify options for parent MenuItem class's init.
            videojs.MenuItem.call(this, player, options);
        }
    });

    videojs.WatchlaterToggle.prototype.createItems = function(){
        var items = [];
        items[0] = new WatchlaterPopup(this.player_, {label: 'Watch Later'});
        return items;
    };

    videojs.obj.merge(player.controlBar.options_['children'], {
        'watchlaterToggle': {}
    });

    // when the player is ready, add the watchlater toggle to the control bar
    player.ready(function(){
        var button = new videojs.WatchlaterToggle(player);
        player.controlBar.addChild(button);
    });
});

(function() {
  var defaults = {
      0: {
        src: 'example-thumbnail.png'
      }
    },
    extend = function() {
      var args, target, i, object, property;
      args = Array.prototype.slice.call(arguments);
      target = args.shift() || {};
      for (i in args) {
        object = args[i];
        for (property in object) {
          if (object.hasOwnProperty(property)) {
            if (typeof object[property] === 'object') {
              target[property] = extend(target[property], object[property]);
            } else {
              target[property] = object[property];
            }
          }
        }
      }
      return target;
    };

  /**
   * register the thubmnails plugin
   */
  videojs.plugin('thumbnails', function(options) {

    var div, divtip, settings, img, player, progressControl, duration;
    settings = extend({}, defaults, options)
    player = this;

    // create the thumbnail
    div = document.createElement('div');
    divtip = document.createElement('div');
    div.className = 'vjs-thumbnail-holder';
    divtip.id = 'vjs-tip-inner';
    img = document.createElement('img');
    div.appendChild(img);
    div.appendChild(divtip);
    img.src = settings['0'].src;
    img.className = 'vjs-thumbnail';
    extend(img.style, settings['0'].style);

    // center the thumbnail over the cursor if an offset wasn't provided
    if (!img.style.left && !img.style.right) {
      img.onload = function() {
        img.style.left = -(img.naturalWidth / 2) + 'px';
      }
    };

    // keep track of the duration to calculate correct thumbnail to display
    duration = player.duration();
    player.on('durationchange', function(event) {
      duration = player.duration();
    });

    // add the thumbnail to the player
    progressControl = player.controlBar.progressControl;
    progressControl.el().appendChild(div);

    // update the thumbnail while hovering
    progressControl.el().addEventListener('mousemove', function(event) {
      var mouseTime, time, active, left, setting;
      active = 0;

        //Timestamp
        var minutes, seconds, seekBar, timeInSeconds;
        seekBar = player.controlBar.progressControl.seekBar;
        timeInSeconds = seekBar.calculateDistance(event) * seekBar.player_.duration();
        if (timeInSeconds === seekBar.player_.duration()) {
            timeInSeconds = timeInSeconds - 0.1;
        }
        minutes = Math.floor(timeInSeconds / 60);
        seconds = Math.floor(timeInSeconds - minutes * 60);
        if (seconds < 10) {
            seconds = "0" + seconds;
        }
        jQuery('#vjs-tip-inner').html("" + minutes + ":" + seconds);
        jQuery("#vjs-tip-inner").css("visibility", "visible");

      // find the page offset of the mouse
      left = event.pageX || (event.clientX + document.body.scrollLeft + document.documentElement.scrollLeft);
      // subtract the page offset of the progress control
      left -= progressControl.el().getBoundingClientRect().left + window.pageXOffset;
      if(left > 0) {
          div.style.left = left + 'px';
      }
        if (!event) event = window.event;
        var x = event.offsetX == undefined ? event.layerX : event.offsetX;

      // apply updated styles to the thumbnail if necessary
      mouseTime = Math.floor(x / progressControl.width() * duration);
      for (time in settings) {
        if (mouseTime > time) {
          active = Math.max(active, time);
        }
      }
      setting = settings[active];
      if (setting.src && img.src != setting.src) {
        img.src = setting.src;
      }
      if (setting.style && img.style != setting.style) {
        extend(img.style, setting.style);
      }
    }, false);
    jQuery(".vjs-progress-control").on("mouseout", function() {
        jQuery("#vjs-tip-inner").css("visibility", "hidden");
    });
  });
})();

(function(vjs) {
    var extend = function(obj) {
            var arg, i, k;
            for (i = 1; i < arguments.length; i++) {
                arg = arguments[i];
                for (k in arg) {
                    if (arg.hasOwnProperty(k)) {
                        obj[k] = arg[k];
                    }
                }
            }
            return obj;
        },
        defaults = [
            {
                imageSrc: '',
                title:   '',
                url:     ''
            }
        ];

    vjs.plugin('relatedCarousel', function(options) {
        var player = this,
            settings = extend({}, defaults, options || {});

        var holderDiv = document.createElement('div');
        holderDiv.className = 'vjs-related-carousel-holder';

        var title = document.createElement('h5');
        title.innerHTML = 'Related Videos';
        holderDiv.appendChild(title);

        player.el().appendChild(holderDiv);

        for (var i in settings) {
            var img = document.createElement('img');
            img.src = settings[i].imageSrc;
            img.className = 'vjs-carousel-thumbnail';
            img.alt = settings[i].title;

            var anchor = document.createElement('a');
            anchor.href = settings[i].url;
            anchor.appendChild(img);
            anchor.title = settings[i].title;

            holderDiv.appendChild(anchor);
        }


        /* Menu Button */
        var RelatedCarouselButton = vjs.Button.extend({
            init: function(player, options) {
                vjs.Button.call(this, player, options);
            }
        });

        RelatedCarouselButton.prototype.buttonText = 'Related Videos';

        RelatedCarouselButton.prototype.buildCSSClass = function(){
            return 'vjs-related-carousel-button ' + vjs.Button.prototype.buildCSSClass.call(this);
        };

        RelatedCarouselButton.prototype.onClick = function(e){
            holderDiv.classList.toggle('active');
        };

        player.ready(function(){
            var button = new RelatedCarouselButton(player);
            player.controlBar.addChild(button);
        });
    });
}(window.videojs));
