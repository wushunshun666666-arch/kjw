/**
 * 分页列表
 */
define(function (require, exports, module) {
	var ui = require('admin.ui');
	$.fn.dataTable = function (options) {
		return this.each(function () {
			var dataTable = new TataTable(this, options);
			$(this).data('dataTable', dataTable);
		});
	};

	$.fn.dataTable.defaults = {
		url: undefined,			// 请求数据的URL地址
		post: undefined,		// 需要Post到URL地址的表单元素选择器
		load: false,			// 初始化完成后是否立即加载列表数据
		afterLoad: undefined,	// 加载列表数据完成后的回调
		pagerId: 'pager',		// 分页部件的ID
		pager: 'link'			// 分页部件名称
	};

	PagerFactory = {
		map: {},
		register: function (name, create) {
			this.map[name] = create;
		},
		create: function (name, container, list) {
			if (this.map[name]) {
				return this.map[name](container, list);
			}
			else {
				return null;
			}
		}
	};

	function TataTable(container, options) {
		options = $.extend({}, $.fn.dataTable.defaults, options || {});
		var $container = $(container);
		var $table = $container.find('#tableContent').find('table');
		var requestParams = {};
		var pager = null;
		var $thead = $table.find('thead');
		var $tbody = $table.find('tbody');

		var setLoadingState = function(loading) {
			$container.attr('data-table-loading', loading ? '1' : '0');
		};

		var notifyLoadComplete = function() {
			setLoadingState(false);
			if (typeof window.CustomEvent === 'function') {
				window.dispatchEvent(new CustomEvent('admin:data-table-ready', {
					detail: {id: $container.attr('id')}
				}));
			}
		};

		var init = function () {
			pager = createPager();

			if (options.load) {
				$(function () {
					load();
				});
			}
			if (options.fixedHead) {
				var $fixedHead = $container.find('.table-fixed-head');
				var left = $table.offset().left;
				$fixedHead.css('padding-left', left + 'px');
				$fixedHead.css('padding-right', '9px');

				$(window).on("scroll", function () {
					var top = $table.offset().top;
					var sTop = $(document).scrollTop();

					if (sTop > top) {
						$fixedHead.show();
					} else {
						$fixedHead.hide();
					}
				});
			}
		};

		var createPager = function () {
			var pagerContainer = $container.find('#' + options.pagerId)[0];

			return PagerFactory.create(options.pager, pagerContainer, obj);
		};

		var setRequestParam = function (name, value) {
			requestParams[name] = value;
		};

		var resetRequestParam = function () {
			requestParams = {};
		};

		var load = function () {
			var postData = getPostData();
			var url = getUrl();
			var afterLoad = options.afterLoad || function (result) { };

			setLoadingState(true);
			showLoading();

			require.async('admin.ajax', function (ajax) {
				ajax.post(url, postData, function (data) {
					closeLoading();
					try {
						if (data.success()) {
							data = data.data();
							if (data.empty) {
								showEmpty(data);
							}
							else {
								showTable(data);
							}
							afterLoad(true, data);
						}
						// 其它错误
						else {
							showError(data.message());
							afterLoad(false);
						}
					}
					finally {
						notifyLoadComplete();
					}
				}, function () {
					closeLoading();
					try {
						showError('加载失败');
						afterLoad(false);
					}
					finally {
						notifyLoadComplete();
					}
				}, null, {
					loginSuccess: function () {
						load();
					}
				});
			});
		};

		var query = function () {
			resetRequestParam();
			load();
		};

		var getUrl = function () {
			var url = options.url;
			var parms = [];
			$.each(requestParams, function (name, value) {
				parms.push(name + '=' + value);
			});
			if (parms.length > 0) {
				var c = (url.lastIndexOf('?') >= 0) ? '&' : '?';
				var parmsStr = parms.join('&');
				url += c + parmsStr;
			}

			return url;
		};

		var getPostData = function () {
			var postData = null;
			var selectors = options.post;
			if (selectors) {
				//saved the post json object
				$.postData = $(selectors).serializeArray();
				postData = $(selectors).serialize();
			}

			return postData;
		};

		var showTable = function (data) {
			// 替换表格内容
			$tbody.html(data.bodyHtml);
			ui.initBootstrapComponents();

			// 表头的宽度和对齐属性赋给body中对应的列
			setBodyAttrs();

			// 绑定鼠标掠过效果等事件
			setBodyEvent();

			// 为checkbox绑定事件
			setCheckbox();

			// 设置分页
			pager.set(data.pager);
			pager.show();
		};

		var setBodyAttrs = function () {
			var tdAttrs = new Array();
			$thead.find('th').each(function (i) {
				var $td = $(this);
				var attrs = ['width', 'align'];
				tdAttrs[i] = {};
				$.each(attrs, function (key, value) {
					var val = $td.attr(value);
					if (val) {
						tdAttrs[i][value] = val;
					}
				});
			});

			$tbody.children('tr').each(function () {
				$(this).children('td').each(function (i) {
					$td = $(this);
					$.each(tdAttrs[i], function (key, value) {
						$td.attr(key, value);
					});
				});
			});
		};

		var setBodyEvent = function () {
			$tbody.children('tr').on('mouseenter', function () {
				if ($(this).attr('class') != 'selected') {
					$(this).addClass('hover');
				}
			}).on('mouseleave', function () {
				$(this).removeClass('hover');
			});
		};

		var setCheckbox = function () {
			$checkboxAll = $thead.find('#checkbox_all');
			if ($checkboxAll.length > 0) {
				$checkboxAll.attr('checked', false);

				var $checkboxes = $tbody.find('tr > td > input:checkbox:not(:disabled)');
				$checkboxAll.on('click', function () {
					$checkboxes.prop('checked', $(this).is(':checked'));
					if ($checkboxes.is(':checked')) {
						$tbody.find('tr').addClass('selected');
					}
					else {
						$tbody.find('tr').removeClass('selected');
					}
				});

				$checkboxes.on('click', function () {
					var checkedNum = $tbody.find('tr > td > input:checked').length;
					if (checkedNum >= $checkboxes.length) {
						$checkboxAll.prop('checked', true);
					}
					else {
						$checkboxAll.prop('checked', false);
					}

					if ($(this).is(':checked')) {
						$(this).parents('tr').addClass('selected');
					}
					else {
						$(this).parents('tr').removeClass('selected');
					}
				});
			}
		};

		var showEmpty = function (data) {
			var numCols = $thead.find('th').length;
			var html = '<tr><td colspan="' + numCols + '">' + data.emptyHtml + '</td></tr>';
			$tbody.html(html);

			// 设置分页
			pager.set(data.pager);
			pager.show();
		};

		var showLoading = function () {
			$container.find('#tableContent').showLoading();
		};

		var closeLoading = function () {
			$container.find('#tableContent').closeLoading();
		};

		var showError = function (message) {
			var numCols = $thead.find('th').length;
			var html = '<tr><td colspan="' + numCols + '">' + message + '</td></tr>';
			$tbody.html(html);


			pager.setError();
		};

		var obj = {
			load: load,
			query: query,
			setRequestParam: setRequestParam,
			resetRequestParam: resetRequestParam
		};

		init();

		return obj;
	};

	function PagerLink(container, list) {
		var $container = $(container);
		var $prev = $container.find('[data-pager-action="prev"]');
		var $next = $container.find('[data-pager-action="next"]');
		var $first = $container.find('[data-pager-action="first"]');
		var $last = $container.find('[data-pager-action="last"]');

		var currentPage = 0;
		var pageCount = 0;
		var pageVar = '';

		var init = function () {
			$prev.on('click', function () { goPre(); });
			$next.on('click', function () { goNext(); });
			$first.on('click', function () { goFirst(); });
			$last.on('click', function () { goLast(); });
		};

		var getPageNo = function () {
			var pageNo = String($pageNo.val() || '').trim();
			if (pageNo == '' || /[^0-9]/.test(pageNo) || pageNo < 1) {
				pageNo = 1;
			}
			else if (pageNo > pageCount) {
				pageNo = pageCount;
			}

			return pageNo - 1;
		};

		var setLink = function (data) {
			var hasPrevious = currentPage > 0;
			var hasNext = currentPage < pageCount - 1;
			$first.add($prev).toggleClass('enabled', hasPrevious).prop('disabled', !hasPrevious);
			$last.add($next).toggleClass('enabled', hasNext).prop('disabled', !hasNext);
		};

		var set = function (data) {
			currentPage = data.currentPage;
			pageCount = data.pageCount;
			pageVar = data.pageVar;

			$container.find('[data-pager-current-page]').text(currentPage + 1);
			$container.find('[data-pager-page-count]').text(pageCount);
			$container.find('[data-pager-page-size]').text(data.pageSize);
			$container.find('[data-pager-item-count]').text(data.itemCount);

			setLink();
		};

		var setError = function () {
			currentPage = 0;
			pageCount = 0;

			$container.find('[data-pager-current-page]').text(currentPage);
			$container.find('[data-pager-page-count]').text(pageCount);
			$container.find('[data-pager-page-size]').text(0);
			$container.find('[data-pager-item-count]').text(0);

			setLink();
		};

		var show = function () {
			$container.show();
		};

		var goFirst = function () {
			if (currentPage > 0) {
				go(0);
			}
		};

		var goPre = function () {
			if (currentPage > 0) {
				go(currentPage - 1);
			}
		};

		var goNext = function () {
			if (currentPage < pageCount - 1) {
				go(currentPage + 1);
			}
		};

		var goLast = function () {
			if (currentPage < pageCount - 1) {
				go(pageCount - 1);
			}
		};

		var go = function (no) {
			no = parseInt(no);
			list.setRequestParam(pageVar, no + 1);
			list.load();
		};

		init();

		return {
			set: set,
			setError: setError,
			show: show,
			go: go,
			goFirst: goFirst,
			goPre: goPre,
			goNext: goNext,
			goLast: goLast
		};
	}

	PagerFactory.register('link', function (container, list) { return new PagerLink(container, list); });
});
