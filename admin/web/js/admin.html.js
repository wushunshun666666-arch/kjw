/**
 * UI
 */
define(function(require, exports, module) {
	function navigateA(url, verify) {
		verify = (verify == undefined)? true:verify;
		if (verify) {
			var ui = require('admin.ui');
			url = ui.verifyPermissions(url);
			if (url == false) {
				return false;
			}
		}

		window.location.href=url;
	}

	function confirmA(message, url, successCallback, postData, errorCallback) {
		var ui = require('admin.ui');
		if (!ui.verifyPermissions(url)) {
                return false;
        }
		ui.confirmBox(message, function() {
			ui.showLoading();
			var ajax = require('admin.ajax');
			ajax.post(url[1], postData, function(data) {
				ui.closeLoading();
				if (data.success()) {
					ui.messageBox(data.message(), successCallback, 'SUCCESS');
				} else {
					ui.messageBox(data.message(), errorCallback);
				}
			}, function() {
				ui.closeLoading();
				ui.messageBox('提交请求失败', errorCallback);
			});
		});
	}

	function modalA(url, title, width, height, options) {
		var ui = require('admin.ui');
		url = ui.verifyPermissions(url);
		if (!url) {
			return false;
        }
        if (options.post && typeof(options.post) == 'string') {
        	options.post = $(options.post).serializeArray();
        }

		ui.showModal(url, title, width, height, options);
	}

	$.fn.queryInput = function(url, options) {
		return this.each(function() {
			var queryInput = new QueryInput(this, url, options);
			$(this).data('queryInput', queryInput);
		});
	};

	function QueryInput(container, url, options) {
		var ui = require('admin.ui');
		var $container = $(container);
		var $input = $container.find('input[type="hidden"]');
		var $text = $container.find('input[type="text"]');
		var $i = $container.find('i');

		var disable = function() {
			$text.attr('disabled', true);
			$text.attr('readonly', false);
		};

		var enable = function() {
			$text.attr('disabled', false);
			$text.attr('readonly', true);
		};

		var setQueryIcon = function () {
			$i.removeClass('fa-times');
			$i.addClass('fa-search');
			$i.css('cursor', 'default');
			$i.attr('title', '');
			$i.off('click');
		};

		var setClearIcon = function() {
			$i.removeClass('fa-search');
			$i.addClass('fa-times');
			$i.css('cursor', 'pointer');
			$i.attr('title', '点击删除');
			$i.on('click', function() {
				clear();
			});
		};

		var clear = function() {
			$input.val('');
			$text.val('');
			setQueryIcon();
		};

		var modalOptions = {};
		modalOptions.dataTable = true;
		modalOptions.csrf = options.csrf;
		modalOptions.callback = function(value, text) {
			$input.val(value);
			$text.val(text);
			setClearIcon();
			ui.closeModal();
			options.onSelect && options.onSelect(value, text);
		};
		$text.on('click', function() {
			modalOptions.post = options.modalPost;
			modalA(url, options.modalTitle, options.modalWidth, options.modalHeight, modalOptions);
		});
		if ($text.val() != '') {
			setClearIcon();
		}

		return {
			disable:disable,
			enable:enable,
			clear:clear
		};
	}

	function ajaxUpdateDropDownList(source, url, target, options) {
		var $this = $(source);
		var $target = $(target);

		var postData = {};
		if (options.name) {
			postData[options.name] = $this.val();
		}
		else {
			postData = $this.serialize();
		}

		var ajax = require('admin.ajax');
		ajax.post(url, postData, function(data) {
			var html = [];
			html.push('<option value="'+options.emptyValue+'">'+options.emptyName+'</option>');
			if (data.success()) {
				data = data.data();

				$.each(data, function(index, value) {
					html.push('<option value="'+value.id+'">'+value.name+'</option>');
				});
			}
			$target.html(html.join(''));
		}, function() {

		});
	}

	/**
	 * 导出接口
	 */
	exports.confirmA = confirmA;
	exports.modalA = modalA;
	exports.navigateA = navigateA;
	exports.ajaxUpdateDropDownList = ajaxUpdateDropDownList;
});
