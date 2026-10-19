/**
 * 区域选择器
 */
define(function(require, exports, module) {
	$.fn.upload = function(options) {
		return this.each(function() {
			var upload = new Upload(this, options);
			$(this).data('upload', upload);
		});
	};

	$.fn.upload.defaults = {
		'buttonTag':'button',
		'action':'',
		'fileInputName':'upload',
		'hiddenInputs':[],
		'extensions':[],
		'maxSize':0,
		'extensionsMessage':null,
		'maxSizeMessage':null,
		'before':null,
		'success':null,
		'error':null
	};

	function Upload(container, options) {
		options = $.extend({}, $.fn.upload.defaults, options || {});
		var $container 	= $(container);
		var id 			= $container.attr('id');
		var $button		= $container.find(options.buttonTag);
		var $file 		= null;
		var $form 		= null;
		var $iframe 	= null;

		function init() {
			$.each(options.extensions, function(key, value) {
				options.extensions[key] = value.toUpperCase();
			});

			if (options.extensions.length > 0 && options.extensionsMessage == null) {
				var str = options.extensions.join('、', options.extensions);
				options.extensionsMessage = '必须是'+str+'格式的文件。';
			}
			if (options.maxSize && options.maxSizeMessage == null) {
				var maxSize = (options.maxSize/1024);
				if (maxSize >= 1024) {
					maxSize = (maxSize/1024)+'MB';
				}
				else {
					maxSize = maxSize+'KB';
				}
				options.maxSizeMessage = '文件大小不能超过'+maxSize+'。';
			}

			render();
			initEvent();
		}

		function render() {
			var iframeId = id+'-iframe';
			var buttonId = id+'-button';

			var html = [];
			html.push('<div>');
			html.push('<form action="'+options.action+'" method="POST" target="'+iframeId+'" enctype="multipart/form-data">');
			html.push('<input type="file" id="'+buttonId+'" name="'+options.fileInputName+'" />');
			$.each(options.hiddenInputs, function(key, value) {
				html.push('<input type="hidden" name="'+key+'" value="'+value+'" />');
			});
			html.push('</form>');
			html.push('<iframe style="display:none;" id="'+iframeId+'" name="'+iframeId+'" src=""></iframe>');
			html.push('</div>');

			var $view = $(html.join(''));
			$file 	= $view.find('input[type="file"]');
			$form 	= $view.find('form');
			$iframe = $view.find('iframe');

			$button.attr('for', buttonId);

			$('body').append($view);

			resize();
		}

		function resize() {
			$file.css('position', 'absolute');
			$file.css('opacity', 0);
			$file.css('z-index', -1);
			var pos = $button.offset();
			var width = $button.outerWidth();
			var height = $button.outerHeight();
			$file.css('top', pos.top);
			$file.css('left', pos.left);
			$file.css('width', width);
			$file.css('height', height);
		}

		function initEvent() {
			$file.on('change', function() {
				if (!checkFile()) {
					return false;
				}

				if (!before()) {
					return false;
				}

				showLoadding();
				$form[0].submit();
				clearFileInput();
			});

			$iframe.on('load',function() {
				closeLoadding();
				var data = $iframe.contents().find('body').text();
				if (data == '') {return ;}
				if (data.indexOf('PHP Fatal Error') > -1) {
					return error('上传失败：系统错误。');
				}

				try {
					data = JSON.parse(data);
				}
				catch (e) {
					return error('上传失败：返回格式错误。');
				}
				if (data.code != 1000) {
					return error(data.message);
				}

				success(data.data);
	    	});
		}

		function showLoadding() {
			$button.next().html('<i class="fa fa-refresh fa-spin"></i>&nbsp;正在上传...');
		}

		function closeLoadding() {
			$button.next().html('');
		}

		function showError(message) {
			$button.next().html('<span class="invalid-feedback d-inline">'+message+'</span>');
		}

		function error(message) {
			showError(message);
			if (options.error) {
				options.error(message);
			}
		}

		function success(data) {
			if (options.success) {
				options.success(data);
			}
		}

		function before() {
			if (options.before) {
				return options.before();
			}

			return true;
		}
		
		function clearFileInput() {
			var file = $file[0];
		    var form = document.createElement('form');
		    document.body.appendChild(form);
		    var pos = file.nextSibling;
		    form.appendChild(file);
		    form.reset();
		    pos.parentNode.insertBefore(file, pos);
		    document.body.removeChild(form);
		}

		function checkExtensions() {
			if (options.extensions && options.extensions.length > 0) {
				var fileName = $file.val();
				var arr = fileName.split(".");
				if (arr.length < 2) {
					error(options.extensionsMessage);
					return false;
				}
				var suffix = arr[arr.length-1].toUpperCase();

				if ($.inArray(suffix, options.extensions) < 0) {
					error(options.extensionsMessage);
					return false;
				}
			}

			return true;
		}

		function checkMaxSize() {
			if (options.maxSize && window.FileList) {
				var file = $file[0];
				if (file.files.length > 0) {
					if (file.files[0].size > options.maxSize) {
						error(options.maxSizeMessage);
						return false;
					}
				}

			}

			return true;
		}

		function checkFile() {
			var fileName = $file.val();
			if(fileName == '') {
				error('请选择文件。');
				return false;
			}

			if (!checkExtensions()) {
				return false;
			}
			if (!checkMaxSize()) {
				return false;
			}
			return true;
		}

		init();
	};
});
