define(function(require, exports, module) {
	function isObject(obj) {
		return (typeof obj == 'object' && null !== obj && obj.constructor==Object);
	}

	function AjaxResult(jsondata) {
		var init = function(data) {
			if (!isObject(data)) {
				var pattern=/\{"code".*/g;
				if (pattern.test(data)) {
					return new Function("return" + data)();
				}
				
				var content = {};				
				// 返回的是PHP语法错误页面
				if (data.indexOf('PHP Fatal Error') > -1) {
					content.code = 4000;
					content.message = '系统异常';
				}
				else {
					content.code = 2000;
					content.message = data;
				}
				content.data = data;
				return content;
			}
			else {
				return data;
			}
		};

		var data = init(jsondata);

		var success = function() {
			return (data.code == 1000);
		};

		var getCode = function() {
			return data.code;
		};

		var getMessage = function() {
			return data.message;
		};

		var getData = function() {
			return data.data;
		};

		return {
			success : success,
			code : getCode,
			message : getMessage,
			data : getData
		};
	}

	function ajax(options, successCallback, errorCallback, beforeSend) {
		var defaultOptions = {async:true,cache:false,loginSuccess:undefined};
		options = $.extend(defaultOptions, options || {});

		options.dataType = 'json';

		options.beforeSend = function() {
			if (beforeSend && 'function' == typeof(beforeSend)) {
                beforeSend();
            }
		};

		options.success = function(data) {
			if (successCallback && 'function' == typeof(successCallback)) {
				var result = new AjaxResult(data);
				successCallback(result);
			}
			return true;
		};

		options.error = function(jqXHR, textStatus, errorThrown) {
			if (errorCallback && 'function' == typeof(errorCallback)) {
				var response = new AjaxResult(jqXHR.responseText);
				errorCallback(response, jqXHR, textStatus, errorThrown);
			}
		};

		jQuery.ajax(options);
	}

	function post(url, data, successCallback, errorCallback, beforeSend, options) {
		options = $.extend({}, options || {});

		options.type = 'POST';
		options.data = data;
		options.url = url;

		ajax(options, successCallback, errorCallback, beforeSend);
	}

	function get(url, successCallback, errorCallback, beforeSend, options) {
		options = $.extend({}, options || {});

		options.type = 'GET';
		options.url = url;

		ajax(options, successCallback, errorCallback, beforeSend);
	}


	function loginRequire(data) {
		var ui = require('admin.ui');
		ui.closeLoading();
		ui.messageBox(data.message(), function() {
			var cookieName = data.data().cookieName;
			document.cookie = [cookieName, '=', encodeURIComponent(window.location.href), ';path=/'].join('');
			ui.showLoading();
			top.window.location.href=data.data().url;
		});
	}

	exports.post = function(url, data, successCallback, errorCallback, beforeSend, options) {
		post(url, data, function(data) {
			if (data.code()==5000) {
				loginRequire(data);
			}
			else if (successCallback && 'function' == typeof(successCallback)) {
				successCallback(data);
			}
			return true;
		}, errorCallback, beforeSend, options);
	};

	exports.get = function(url, successCallback, errorCallback, beforeSend, options) {
		get(url, function(data) {
			if (data.code()==5000) {
				loginRequire(data);
			}
			else if (successCallback && 'function' == typeof(successCallback)) {
				successCallback(data);
			}
			return true;
		}, errorCallback, beforeSend, options);
	};
});