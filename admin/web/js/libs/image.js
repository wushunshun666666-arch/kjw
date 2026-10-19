/**
 * 图像工具
 *
 */

define(function(require, exports, module) {
	'use strict';
	$.support.filereader = !!(window.File && window.FileReader && window.FileList && window.Blob);

	var compress = function(file, options, success) {
		require('libs/megapix-image.js');

        var defaults = {
            width: 0,
            height: 0,
            background: '#fff',
            type: 'image/jpeg',
            success: null,
            error: null
        };

	    options = $.extend({}, defaults, options || {});

        if (!$.support.filereader) {
	        if (typeof options.error === 'function') {
				options.error('不支持FileReader接口');
			}
	        return false;
        }

		var $canvas = $('<canvas></canvas>');
		var canvas = $canvas[0];
		var context = canvas.getContext('2d');

		canvas.width = options.width;
		canvas.height = options.height;

		if (options.background) {
			context.fillStyle = options.background;
			context.fillRect(0, 0, options.width, options.height);
		}

		var mpImg = new MegaPixImage(file);
		mpImg.onrender = function() {
			var dataURL = canvas.toDataURL(options.type);
			success(dataURL);

			$canvas.remove();
		};
		mpImg.render(canvas, {
			maxWidth : options.width,
			maxHeight : options.height
		});
	};

	$.fn.compress = function(options) {
		if (this.length < 1) {
			return;
		}
	    var self = this[0];
        var files = self.files;
        if (!files.length) {
	        return;
        }

        var file = files[0];
        if (!/image.*/.test(file.type)) {
	        if (typeof options.error === 'function') {
				options.error('不是图像文件');
			}
	        return;
        }

        compress(file, options, function(dataURL) {
            if (typeof options.success === 'function') {
                options.success(dataURL, file);
            }
        });
	};

	function preview(file, container, options) {
		var $container = $(container);
		var $file = $(file);
		file = $file[0];

		var defaults = {
			width:$container.outerWidth(),
			height:$container.outerHeight()
		};
		options = $.extend({}, defaults, options || {});

		if (file.files && file.files[0]) {
			$container.html('<img>');
			var $img = $container.find('img');
			var img = $img[0];

			img.onload = function() {
				var rect = clacImgZoomParam(options.width, options.height, img.offsetWidth, img.offsetHeight);
				img.width = rect.width;
				img.height = rect.height;
				//img.style.marginLeft = rect.left+'px';
				//img.style.marginTop = rect.top+'px';
			}

			var reader = new FileReader();
			reader.onload = function(evt) {
				$img[0].src = evt.target.result;
			}
			reader.readAsDataURL(file.files[0]);
		}
		else {
			file.select();
			$container.trigger('focus');
			var src = document.selection.createRange().text;

			$container.html('<img style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=image);">');
			var $img = $container.find('img');
			var img = $img[0];
			img.filters.item('DXImageTransform.Microsoft.AlphaImageLoader').src = src;

			var rect = clacImgZoomParam(options.width, options.height, img.offsetWidth, img.offsetHeight);

			var sFilter = 'filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="';
			$container.html("<div id=divhead style='width:" + rect.width
				+ "px;height:" + rect.height + "px;margin-top:"
				+ rect.top + "px;margin-left:" + rect.left + "px;"
				+ sFilter + src + "\");'></div>");
		}
	}

	function clacImgZoomParam(maxWidth, maxHeight, width, height) {
		var param = {
			top: 0,
			left: 0,
			width: width,
			height: height
		};
		var rateMax = maxWidth/maxHeight;
		var rate = width/height;
		if (rateMax > rate) {
			param.height = maxHeight;
			param.width = Math.round(maxHeight * rate);
		}
		else {
			param.width = maxWidth;
			param.height = Math.round(maxWidth/rate);
		}
		param.left = Math.round((maxWidth - param.width) / 2);
		param.top = Math.round((maxHeight - param.height) / 2);
		return param;
	}

	exports.compress = compress;
	exports.preview = preview;
});




