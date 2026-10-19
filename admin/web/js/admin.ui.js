/**
 * UI
 */
define(function(require, exports, module) {
	var _top = top;

	/**
     * 按钮设定为不可点击状态
     */
    $.fn.disable = function() {
        return this.each(function() {
            $(this).attr('disabled','disabled');
            $(this).addClass('disabled');
        });
    };

    /**
     * 按钮恢复可点击状态
     */
    $.fn.enable = function() {
        return this.each(function() {
            $(this).removeAttr('disabled');
            $(this).removeClass('disabled');
        });
    };

    /**
     * 显示loading效果
     */
    $.fn.showLoading = function(){
		return this.each(function() {
			var $target = $(this);
			$target.children('.overlay').remove();
			$target.addClass('loading-container is-loading').append(
				'<div class="overlay" role="status" aria-label="数据加载中">' +
				'<i class="fa fa-spinner fa-spin" aria-hidden="true"></i></div>'
			);
		});
    };

    /**
     * 关闭loading效果
     */
    $.fn.closeLoading = function(){
		return this.each(function() {
			$(this).removeClass('loading-container is-loading').children('.overlay').remove();
		});
    };

	function showModal(url, title, width, height, options) {
		url = verifyPermissions(url);
		if (url == false) {
			return false;
		}

		var dialog = require('admin.dialog');
		dialog.showModal(url, title, width, height, options);
	}

	function closeModal() {
		var dialog = require('admin.dialog');
		dialog.closeModal();
	}

	function getModal(index) {
		var dialog = require('admin.dialog');
		return dialog.getModal(index);
	}

	function confirmBox(message, callback, options) {
		var dialog = require('admin.dialog');
		dialog.confirmBox(message, callback, options);
	}

	function messageBox(message, callback, type, options) {
		var dialog = require('admin.dialog');
		dialog.messageBox(message, callback, type, options);
	}

	function successBox(message, callback, options) {
		messageBox(message, callback, 'SUCCESS', options)
	}

	function errorBox(message, callback, options) {
		messageBox(message, callback, null, options)
	}

	function showLoading() {
		var html = '';
		html += '<div id="page-overlay" class="page-overlay">'+
				'	<div id="overlay" class="overlay"></div>'+
				'	<span><i class="fa fa-spinner fa-spin"></i>'+
				'	页面加载中，请稍候...</span>'+
				'</div>';

		topWindow().find('body').append(html);
	}

	function closeLoading() {
		topWindow().find('.page-overlay').remove();
	}

	function alertSuccess(message, title, delay) {
		alertShow('fa-check', 'alert-success', message, title, delay);
	}

	function alertError(message, title, delay) {
		alertShow('fa-ban', 'alert-danger', message, title, delay);
	}

	function alertWarning(message, title, delay) {
		alertShow('fa-warning', 'alert-warning', message, title, delay);
	}

	function alertShow(icon, cls, message, title, delay) {
		title = arguments[3] || '提示';
		delay = arguments[4] || 2000;

		var html = '';
		html += '<div class="alert '+ cls +' alert-dismissible alert-overlay">'+
				'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>'+
				'<h4><i class="icon fa '+ icon +' "></i>'+ title +'</h4>'+
				'<p>'+ message +'</p></div>';

		topWindow().find('body').append(html);

		var $alert = topWindow().find('.alert-overlay');
		if(delay > 0) {
			$alert.fadeIn('slow').delay(delay).fadeOut('slow', function () {
				$(this).remove();
			});
		}
		else {
			$alert.fadeIn('slow');
		}
	}

	function alertClose(eager) {
		eager = arguments[0] || false;
		var $alert = topWindow().find('.alert-overlay');

		if (eager == true) {
			$alert.remove()
		}

		$alert.fadeOut('slow', function () {
			$(this).remove();
		});
	}

	function redirect(url, istop) {
		istop = (istop == undefined)? false:istop;
		if (istop) {
			showLoading();
			_top.window.location.href=url;
		}
		else {
			window.location.href=url;
		}
	}

	function refresh() {
		window.location.href=window.location.href.replace(/(#*$)/g, "");
	}

	function Authorization(modules, isAdmin) {
		var has = function(url) {
			return (isAdmin || ($.inArray(url, modules) >= 0));
		};

		return {
			has:has
		};
	}

	function verifyPermissions(url) {
		if (Array.isArray(url)) {
			var route = url[0];
			url = url[1];
			if (_top.__authorization && !_top.__authorization.has(route)) {
				messageBox('您没有权限操作该项功能！');
				return false;
			}
		}

		return url;
	}

	function topWindow() {
		var iframe = _top.window;
		// firefox使用contentWindow和contentDocument，IE则不使用
		var myJquery = (iframe.contentWindow)? iframe.contentWindow.jQuery:iframe.jQuery;
		return myJquery(iframe.contentDocument||iframe.document);
	}

	function contentWindow() {
		var iframe = _top.window.frames['__content'];
		// firefox使用contentWindow和contentDocument，IE则不使用
		var myJquery = (iframe.contentWindow)? iframe.contentWindow.jQuery:iframe.jQuery;
		return myJquery(iframe.contentDocument||iframe.document);
	}

	function contentFrame() {
		var iframe = _top.window.frames['__content'];
		return (iframe.contentWindow)? iframe.contentWindow:iframe;
	}

	function navigateSystemContent(url) {
	    _top.document.getElementById('__content').contentWindow.location.href = url;
	}

	function navigate(url, verify) {
		verify = (verify == undefined)? true:verify;
		if (verify) {
			url = verifyPermissions(url);
			if (url == false) {
				return false;
			}
		}

		navigateSystemContent(url);
	}

    function initMenu(sidebar, threeLevelMenu, items) {
    	var menu = require('admin.sysmenu');
		if (!_top.__sysMenu) {
			var options = {navigate:navigateSystemContent};
			_top.__sysMenu = new menu.SysMenu(sidebar, threeLevelMenu, items, options);
		}
    }

	function initLayout() {
		var $content = $('#__content');
    	//var height = 169;

    	//$content.css('height', ($(window).height()-height)+'px');
		$content.attr('height', '100%');//修正chrome iframe高度问题

		/*$(window).resize(function() {
			$content.css('height', ($(window).height()-height)+'px');
		});*/

		require('admin.dialog');
		initBootstrapComponents();
    }

	function initBootstrapComponents() {
		if (typeof window.initBootstrapComponents === 'function') {
			window.initBootstrapComponents(document);
		}
	}

	function initAuthorization(modules, isAdmin) {
		if (!_top.__authorization) {
			_top.__authorization = new Authorization(modules, isAdmin);
		}
	}

    function locate(id) {
    	if (_top.__sysMenu) {
    		_top.__sysMenu.locate(id);
    	}
    }

    function collapseMenu() {
    	if (_top.__sysMenu) {
    		_top.__sysMenu.collapse();
    	}
    }

	/**
	 * 导出接口
	 */
	exports.confirmBox = confirmBox;
	exports.messageBox = messageBox;
	exports.successBox = successBox;
	exports.errorBox = errorBox;
	exports.showModal = showModal;
	exports.closeModal = closeModal;
	exports.getModal = getModal;

	exports.showLoading = showLoading;
	exports.closeLoading = closeLoading;

	exports.alertSuccess = alertSuccess;
	exports.alertError = alertError;
	exports.alertWarning = alertWarning;
	exports.alertClose = alertClose;

	exports.redirect = redirect;
	exports.refresh = refresh;
	exports.navigate = navigate;

	exports.topWindow = topWindow;
	exports.contentWindow = contentWindow;
	exports.contentFrame = contentFrame;

	exports.verifyPermissions = verifyPermissions;

	exports.initMenu = initMenu;
	exports.initLayout = initLayout;
	exports.initBootstrapComponents = initBootstrapComponents;
	exports.initAuthorization = initAuthorization;

    exports.locate = locate;
    exports.collapseMenu = collapseMenu;
});
