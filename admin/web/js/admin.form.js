
define(function(require, exports, module) {

	$.fn.ajaxSubmit = function(url, callback, errorCallback, beforeSend, options) {
		var form = this.serialize();
		var ajax = require('admin.ajax');

		ajax.post(url, form, function(data) {
			callback(data);
		},errorCallback,beforeSend, options);
	};

    /**
     * ajax提交表单
     * @param {String} url 提交地址，如果为空，则默认为当前表单action
     * @param {Function} callback ajax提交成功回调
     * @param {Function} errorCallback ajax提交失败回调
     * @param {Function} beforeSend ajax提交前回调
     * @param {Object} options 其它选项
     * 	beforeValidate 表单校验前回调
     * 	afterValidate 表单校验后回调
     */
    $.fn.ajaxSubmitForm = function(url, callback, errorCallback, beforeSend, options) {
	    options = options || {};

	    var form = $(this).closest('form')[0];
	    if (!form) {
		    return;
	    }
	    var $form = $(form);
	    url = url||form.action;

	    if ($form.data('yiiActiveForm')) {
		    $form.off('beforeSubmit');
	        $form.off('beforeValidate');
			$form.off('afterValidate');

		    $form.on('beforeSubmit', function(event) {
			    $form.ajaxSubmit(url, callback, errorCallback, beforeSend, options);
			    return false;
	        });
	        $form.on('beforeValidate', function(event, messages, deferreds) {
		        if (options.beforeValidate) {
			        return options.beforeValidate(event, messages, deferreds);
		        }
		        return true;
	        });
	        $form.on('afterValidate', function(event, messages) {
		        if (options.afterValidate) {
			        var hasError = false;
			        for (var attribute in messages) {
				        if (messages[attribute].length > 0) {
					        hasError = true;
					        break;
				        }
			        }
			        return options.afterValidate(event, messages, hasError);
		        }

		        return true;
	        });

		    $form.yiiActiveForm('submitForm');
	    }
	    else {
		    // AJAX buttons do not trigger the browser's native submit event. When
		    // ActiveForm is unavailable or has not initialized, explicitly enforce
		    // the HTML constraints generated from the Yii validation rules.
		    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
			    $form.addClass('was-validated');
			    if (typeof form.reportValidity === 'function') {
				    form.reportValidity();
			    }
			    if (options.afterValidate) {
				    options.afterValidate(null, {}, true);
			    }
			    return;
		    }
		    $form.ajaxSubmit(url, callback, errorCallback, beforeSend, options);
	    }
    };

    /**
     * ajax提交表单
     * @param {Object} $btn 提交按钮
     * @param {Function} callback ajax提交成功回调
     * @param {Object} options 其它选项
     *  showLoading 是否显示loading遮罩，默认为true
     * 	url 提交地址
     * 	errorCallback ajax提交失败回调
     * 	beforeSend ajax提交前回调
     */
    exports.btnAjaxSubmit = function($btn, callback, options) {
	    options = $.extend({showLoading:true}, options || {});
	    options.afterValidate = function(event, messages, hasError) {
		    if (hasError) {
			    $btn.enable();
		    }

		    return true;
	    };

	    ui = require('admin.ui');
	    $btn.disable();
	    $btn.ajaxSubmitForm(options.url, function(data) {
		    options.showLoading && ui.closeLoading();
		    if (callback) {
			    callback(data);
		    }
		    $btn.enable();
	    }, function(data) {
		    options.showLoading && ui.closeLoading();
		    ui.messageBox(data.message());
		    if (options.errorCallback) {
			    options.errorCallback();
		    }
		    $btn.enable();
	    }, function() {
		    options.showLoading && ui.showLoading();
	        if (options.beforeSend) {
		        options.beforeSend();
	        }
	    }, options);
    };
});
