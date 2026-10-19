define(function(require, exports, module) {
	$.escapeHTML = function(b) {
	    var a = $('<div />');
	    a.text(b);
	    return a.html();
    };

	function Dialog(title, content, width, height, options) {
		var defaults = {
			style : 'modal',
			hideHeader : false,//是否隐藏标题栏
			beforeClose : null,
			afterClose : null,
			modeless : false,
			buttons : [],
			deferReveal : false
		};

		options = $.extend({}, defaults, options || {});

		title = title || '';
		width = normalizeSize(width);
		height = normalizeSize(height);
		var buttons = options.buttons;

		var id = options.id || 'dialog_0';
		var ownerWindow = top.window;
		var $view = null;
		var $dialog = null;
		var $header = null;
		var $body = null;
		var $footer = null;
		var $close = null;
		var modal = null;
		var closing = false;
		var revealed = !options.deferReveal;
		var startViewPos = null;
		var startMousePos = null;

		var init = function() {
			// 创建对话框
			create();
			// 绑定事件
			initEvent();
		};

		var create = function() {
			var html = new Array();
			var useWhiteClose = /(?:^|\s)modal-(?:success|danger|info)(?:\s|$)/.test(options.style);
			var closeClass = 'btn-close dialog-close' + (useWhiteClose ? ' btn-close-white' : '');
			html.push('<div class="modal-dialog modal-dialog-centered">');
			html.push('<div class="modal-content">');

			// header
			if (!options.hideHeader) {
				html.push('<div class="modal-header">');
				html.push('<h4 class="modal-title">');
				html.push($.escapeHTML(title));
				html.push('</h4>');
				html.push('<button class="'+closeClass+'" aria-label="关闭" title="关闭" type="button"></button>');
				html.push('</div>');
			}

			//body
			html.push('<div class="modal-body">');
			html.push(content);
			html.push('</div>');

			//footer
			if (buttons.length > 0) {
				html.push('<div class="modal-footer">');
				$.each(buttons, function(id, button) {
					button.style = button.style || 'btn btn-outline-light';
					html.push('<button id="btn_'+id+'" class="'+button.style+'" type="button">'+button.name+'</button>');
				});
				html.push('</div>');
			}

			html.push('</div>');
			html.push('</div>');

			var view = ownerWindow.document.createElement('div');
			$view = $(view);
			$view.attr('id', id);
			$view.attr('class', options.style + ' fade');
			$view.attr('tabindex', '-1');
			$view.attr('aria-hidden', 'true');

			$(ownerWindow.document.body).append(view);
			$view.html(html.join(''));

			$dialog = $view.find('.modal-dialog');
			$header = $view.find('.modal-header');
			$body = $view.find('.modal-body');
			$footer = $view.find('.modal-footer');
			$close = $header.find('.btn-close');
			if (!revealed) {
				$dialog.css('visibility', 'hidden');
			}

			setSize(width, height);

			modal = new ownerWindow.bootstrap.Modal(view, {
				backdrop: options.modeless ? false : 'static',
				keyboard: !options.modeless,
				focus: !options.modeless
			});
			$view.on('shown.bs.modal', function() {
				center();
				if (options.afterShown) {
					options.afterShown(api);
				}
			});
			$view.on('hidden.bs.modal', function() {
				modal.dispose();
				$view.remove();
				if (options.afterClose) {
					options.afterClose();
				}
			});
			modal.show();
		};

		function normalizeSize(size) {
			if (size === null || typeof size === 'undefined' || size === '' || size === false) {
				return null;
			}

			return size;
		}

		function cssSize(size) {
			return typeof size === 'number' || /^\d+(?:\.\d+)?$/.test(size) ? size + 'px' : size;
		}

		var initEvent = function() {
			$close.on('click', function() {
				close();
				return false;
			});
			$.each(buttons, function(id, button) {
				var $btn = $footer.find('#btn_'+id);
				$btn.on('click', function() {
					close();
					if (button.callback) {
						button.callback();
					}
				});
				if (button.focus) {
					$btn.trigger('focus');
				}
			});

			$header.on('mousedown', function(e) {
				var pos = $dialog.offset();
				startViewPos = [pos.left, pos.top];
	            startMousePos = [e.pageX, e.pageY];

	            $dialog.find('iframe').css('visibility', 'hidden');

				// 最小化时不可拖动

				var onmousemove=function(e) {
	                var pos = getPosition(e);
	                $dialog.css('left', pos[0] + 'px');
	                $dialog.css('top', pos[1] + 'px');
	            };

	            $(ownerWindow.document).mousemove(onmousemove);

			    $(ownerWindow.document).on('mouseup', function(e) {
				    $(ownerWindow.document).off('mousemove', onmousemove);
				    $dialog.find('iframe').css('visibility', 'visible');
			    });

	            e.preventDefault();
	            e.stopPropagation();
			});
		};

		var getBodyElement = function() {
			var element;
			if (ownerWindow.document.compatMode != 'CSS1Compat') {
				element = ownerWindow.document.body;
			}
			else {
				element = ownerWindow.document.documentElement;
			}
			return element;
		};

		var getDefaultPosition = function(dialogWidth, dialogHeight) {
			var bodyElement = getBodyElement();
			var left = Math.max(parseInt((bodyElement.clientWidth - dialogWidth) / 2), 0) || 0;
			var top = Math.max(parseInt((bodyElement.clientHeight - dialogHeight) / 2), 0) || 0;
			return [ left + 'px', top + 'px' ];
		};

		var center = function() {
			var current = getDefaultPosition($dialog.outerWidth(), $dialog.outerHeight());
			$dialog.css({position: 'fixed', left: current[0], top: current[1], margin: 0, transform: 'none'});
		};

		var getPosition = function(e) {
			var bodyElement=getBodyElement();
	        var dialog = $dialog.get(0);

	        // 弹出窗口位置 = 鼠标移动的距离(可以为负)
			var left = e.pageX - startMousePos[0] + startViewPos[0];
			var top = e.pageY - startMousePos[1] + startViewPos[1];

			if (left < 0) {
				left = 0;
			}
			if (top < 0) {
				top = 0;
			}
			if (left + dialog.offsetWidth > bodyElement.clientWidth) {
				left = bodyElement.clientWidth - dialog.offsetWidth;
			}
			if (top + dialog.offsetHeight > bodyElement.clientHeight) {
				top = bodyElement.clientHeight - dialog.offsetHeight;
			}

			return [left, top];
		};

		var setSize = function(width, height) {
			if (width != null) {
				$dialog.css({width: cssSize(width), maxWidth: 'none'});
			} else {
				$dialog.addClass('dialog-auto-width');
			}
			if (height != null) {
				$dialog.addClass('dialog-fixed-height');
				$dialog.find('.modal-content').css('height', cssSize(height));
			} else {
				$dialog.addClass('dialog-auto-height');
			}
		};

		var setContentSize = function(width, height) {
			if (width != null) {
				$dialog.removeClass('dialog-auto-width').css({width: cssSize(width), maxWidth: 'none'});
			}
			if (height != null) {
				$dialog.removeClass('dialog-auto-height').addClass('dialog-fixed-height');
				$dialog.find('.modal-content').css('height', cssSize(height));
			}
			center();
		};

		var window = function() {
			return $view;
		};

		var reveal = function() {
			if (!revealed) {
				revealed = true;
				$dialog.css('visibility', 'visible');
			}
			center();
		};

		var close = function() {
			if (!closing && (!options.beforeClose || options.beforeClose())) {
				closing = true;
				modal.hide();
			}
		};

		var api = {
			id:id,
			window:window,
			close:close,
			center:center,
			reveal:reveal,
			setContentSize:setContentSize
		};

		init();

		return api;
	}

	function ConfirmBox(message, callback, options) {
		var defaults = {
			title : '提示',
			width: 500,
			height: 182,
			style: 'modal modal-info modal-message',
		};
		options = $.extend({}, defaults, options || {});

		options.buttons = [
			{type:'ok',name:'确定',style:'btn btn-outline-light',callback:callback},
			{type:'cancel',name:'取消',style:'btn btn-outline-light',focus:true}
		];
		var content = '<p>'+message+'</p>';

		return new Dialog(options.title, content, options.width, options.height, options);
	}

	function MessageBox(message, callback, type, options) {
		var defaults = {
			title : '提示',
			width: 500,
			height: 182,
			buttons : []
		};
		options = $.extend({}, defaults, options || {});

		switch(type) {
			case 'SUCCESS': options.style = 'modal modal-success modal-message';break;
			case 'ERROR': options.style = 'modal modal-danger modal-message';break;
			default: options.style = 'modal modal-danger modal-message';break;
		}

		var okBtn = {type:'ok',name:'确定',style:'btn btn-outline-light',focus:true,callback:callback};
		options.buttons.push(okBtn);

		var content = '<p>'+message+'</p>';

		return new Dialog(options.title, content, options.width, options.height, options);
	}

	function FrameDialog(url, title, width, height, options) {
		options.style = 'modal modal-frame';
		var ownerWindow = top.window;
		var autoWidth = normalizeFrameSize(width) === null;
		var autoHeight = normalizeFrameSize(height) === null;
		var dataTableFrame = !!options.dataTable;
		options.deferReveal = (autoWidth || autoHeight) && !dataTableFrame;
		var fittedWidth = null;
		var fitTimer = null;
		var revealOnNextFit = false;
		var revealTimer = null;
		var resizeObserver = null;
		var mutationObserver = null;
		var frameWindow = null;
		var dataReadyHandler = null;
		var id = options.id || 'dialog_0';
		var id = id + '-iframe';
		var afterClose = options.afterClose;
		options.afterClose = function() {
			disconnectAutoSizeObservers();
			if (afterClose) {
				afterClose();
			}
		};

		var content = '<iframe name="'+id+'" id="'+id+'" allowtransparency="true" style="margin: 0px; width: 100%; height: 100%;" src="" frameborder="0"></iframe>';
		var dialog = new Dialog(title, content, width, height, options);

		function normalizeFrameSize(size) {
			return size === null || typeof size === 'undefined' || size === '' || size === false ? null : size;
		}

		function fitToFrame() {
			if (!autoWidth && !autoHeight) {
				dialog.reveal();
				return;
			}

			var iframe = dialog.window().find('iframe')[0];
			try {
				var doc = iframe.contentDocument || iframe.contentWindow.document;
				if (!doc.body) {
					ownerWindow.setTimeout(fitToFrame, 20);
					return;
				}
				var $frameBody = $(doc.body);
				if (autoHeight) {
					$frameBody.addClass('modal-auto-size');
				}
				disconnectAutoSizeObservers();
				frameWindow = iframe.contentWindow;
				dataReadyHandler = function() {
					scheduleFit(doc);
				};
				frameWindow.addEventListener('admin:data-table-ready', dataReadyHandler);
				dataTableFrame = dataTableFrame || !!doc.querySelector('[data-table-loading]');
				scheduleFit(doc, true);
				if (iframe.contentWindow.ResizeObserver) {
					resizeObserver = new iframe.contentWindow.ResizeObserver(function() { scheduleFit(doc); });
					resizeObserver.observe(doc.documentElement);
					resizeObserver.observe(doc.body);
				}
				if (iframe.contentWindow.MutationObserver) {
					mutationObserver = new iframe.contentWindow.MutationObserver(function() { scheduleFit(doc); });
					mutationObserver.observe(doc.body, {
						childList: true,
						subtree: true,
						characterData: true,
						attributes: true,
						attributeFilter: ['class', 'style', 'hidden']
					});
				}
			} catch (e) {
				// 跨域页面无法读取内容时，保留响应式默认尺寸。
				dialog.reveal();
			}
		}

		function disconnectAutoSizeObservers() {
			if (resizeObserver) {
				resizeObserver.disconnect();
				resizeObserver = null;
			}
			if (mutationObserver) {
				mutationObserver.disconnect();
				mutationObserver = null;
			}
			if (frameWindow && dataReadyHandler) {
				frameWindow.removeEventListener('admin:data-table-ready', dataReadyHandler);
				frameWindow = null;
				dataReadyHandler = null;
			}
			if (fitTimer) {
				ownerWindow.clearTimeout(fitTimer);
				fitTimer = null;
			}
			revealOnNextFit = false;
			if (revealTimer) {
				ownerWindow.clearTimeout(revealTimer);
				revealTimer = null;
			}
		}

		function scheduleFit(doc, revealAfterFit) {
			if (revealAfterFit && revealTimer) {
				ownerWindow.clearTimeout(revealTimer);
				revealTimer = null;
			}
			revealOnNextFit = revealOnNextFit || !!revealAfterFit;
			if (fitTimer) {
				ownerWindow.clearTimeout(fitTimer);
			}
			fitTimer = ownerWindow.setTimeout(function() {
				fitTimer = null;
				var shouldReveal = revealOnNextFit;
				revealOnNextFit = false;
				ownerFit(doc);
				if (shouldReveal) {
					dialog.reveal();
				}
			}, 20);
		}

		function ownerFit(doc) {
			var viewportWidth = ownerWindow.document.documentElement.clientWidth;
			var viewportHeight = ownerWindow.document.documentElement.clientHeight;
			var frameWidth = Math.max(doc.documentElement.scrollWidth, doc.body.scrollWidth, doc.body.offsetWidth);
			var frameHeight = Math.max(doc.documentElement.scrollHeight, doc.body.scrollHeight, doc.body.offsetHeight);
			var chromeHeight = dialog.window().find('.modal-header').outerHeight() || 0;
			var targetWidth = null;
			if (autoWidth) {
				targetWidth = Math.min(Math.max(frameWidth, fittedWidth || 320), viewportWidth - 32);
				fittedWidth = targetWidth;
			}
			var targetHeight = null;
			if (autoHeight) {
				targetHeight = dataTableFrame
					? Math.floor(viewportHeight * 0.5)
					: Math.min(frameHeight + chromeHeight, Math.floor(viewportHeight * 0.5));
			}
			dialog.setContentSize(targetWidth, targetHeight);
		}

		var $iframe = dialog.window().find('iframe');
		$iframe.on('load.dialogAutoSize', fitToFrame);
		if (options.deferReveal) {
			revealTimer = ownerWindow.setTimeout(function() {
				dialog.reveal();
			}, 5000);
		}
		if (options.callback) {
			$iframe.on('load.dialogCallback', function() {
				this.contentWindow.dialog = {};
				this.contentWindow.dialog.callback = options.callback;
			});
		}

		if (options.post && options.post.length > 0) {
			var html = [];
			$.each(options.post, function(i, data) {
				html.push('<input type="hidden" name="'+data.name+'" value="'+data.value+'" />');
			});
			if (options.csrf) {
				var csrf = options.csrf;
				html.push('<input type="hidden" name="'+csrf.param+'" value="'+csrf.token+'" />');
			}

			var form = top.window.document.createElement('form');
			var $form = $(form);
			$form.attr('action', url);
			$form.attr('method', 'post');
			$form.attr('target', id);
			$(top.window.document.body).append(form);
			$form.html(html.join(''));

			$form[0].submit();
			$form.remove();
		}
		else {
	        var win = $iframe[0].contentWindow;
			win.location.replace(url);
		}

		return dialog;
	}

	function DialogManager() {
		var modalDialogs = new Array();
		var modelessDialogs = {};
		var dialogId = 0;

		var confirmBox = function(message, callback, options) {
			options = setModalOptions(options);
			var dialog = new ConfirmBox(message, callback, options);
			modalDialogs.push(dialog);
		};

		var messageBox = function(message, callback, type, options) {
			options = setModalOptions(options);
			var dialog = new MessageBox(message, callback, type, options);
			modalDialogs.push(dialog);
		};

		var showModal = function(url, title, width, height, options) {
			options = setModalOptions(options);
			var dialog = new FrameDialog(url, title, width, height, options);
			modalDialogs.push(dialog);
		};

		var closeModal = function() {
			if (modalDialogs.length > 0) {
				var dialog = modalDialogs[modalDialogs.length-1];
				dialog.close();
			}
		};

		var getModal = function(index) {
			if (index >= 0 && index < modalDialogs.length) {
				return modalDialogs[index];
			}
			else if (index < 0 && (modalDialogs.length+index) >= 0) {
				return modalDialogs[modalDialogs.length+index];
			}

			return null;
		};

		var showModeless = function(url, title, width, height, options) {
			options = setModelessOptions(options);
			var dialog = new FrameDialog(url, title, width, height, options);
			modelessDialogs[dialog.id] = dialog;
		};

		var closeModeless = function(id) {
			if (id.window === id.window.window) {
				id = getIdByWindow(id);
			}

			var dialog = modelessDialogs[id];
			if (dialog) {
				dialog.close();
			}
		};

		var getIdByWindow = function(window) {
			return $(window.frameElement).parents('.pop-content').parent().attr('id');
		};

		var newId = function() {
			return 'dialog_' + dialogId++;
		};

		var setModalOptions = function(options) {
			options = options && typeof options === 'object' ? options : {};
			if (!options.id) {
				options.id = newId();
			}
			options.afterClose = function() {
				modalDialogs.pop();
			};

			return options;
		};

		var setModelessOptions = function(options) {
			options = options && typeof options === 'object' ? options : {};
			if (!options.id) {
				options.id = newId();
			}
			options.afterClose = function() {
				delete modelessDialogs[options.id];
			};
			options.modeless = true;

			return options;
		};

		return {
			showModal:showModal,
			showModeless:showModeless,
			confirmBox:confirmBox,
			messageBox:messageBox,
			closeModal:closeModal,
			closeModeless:closeModeless,
			getModal:getModal
		};
	}

	/**
	 * 全局单例对象
	 */
	var dialogManager = null;

	function initGlobal() {
		if (!top.__dialogManager) {
			top.__dialogManager = new DialogManager();
		}

		dialogManager = top.__dialogManager;
	}

	initGlobal();

	/**
	 * 导出接口
	 */
	exports.showModal = function(url, title, width, height, options) {
		dialogManager.showModal(url, title, width, height, options);
	};

	exports.showModeless = function(url, title, width, height, options) {
		dialogManager.showModeless(url, title, width, height, options);
	};

	exports.confirmBox = function(message, callback, options) {
		dialogManager.confirmBox(message, callback, options);
	};

	exports.messageBox = function(message, callback, type, options) {
		dialogManager.messageBox(message, callback, type, options);
	};

	exports.closeModal = function() {
		dialogManager.closeModal();
	};

	exports.closeModeless = function(id) {
		dialogManager.closeModeless(id);
	};

	exports.getModal = function(index) {
		return dialogManager.getModal(index);
	};
});
