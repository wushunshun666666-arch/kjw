/** 门户广告编辑，复用现有表单、上传和提示组件。 */
define(function (require, exports) {
    'use strict';
    var form = require('admin.form');
    var ui = require('admin.ui');
    require('wgt.upload');

    exports.init = function (uploadOptions, imageUrl) {
        var $rows = $('#portal-ad-rows');
        var $json = $('#settingsform-ads_json');
        var serial = 0;
        var pendingUploads = 0;
        var $save = $('#portal-settings-submit');
        $('#portal-settings-form').off('.portalUploads')
            .on('portalUploadStart.portalUploads', function () { pendingUploads++; $save.prop('disabled', true); })
            .on('portalUploadEnd.portalUploads', function () { pendingUploads = Math.max(0, pendingUploads - 1); $save.prop('disabled', pendingUploads > 0); });

        function sync() {
            var ads = [];
            $rows.children('.portal-ad-row').each(function () {
                var $row = $(this);
                ads.push({cover: $row.find('.ad-cover').val(), link: $row.find('.ad-link').val().trim(), enabled: $row.find('.ad-enabled').prop('checked') ? 1 : 0});
            });
            $json.val(JSON.stringify(ads)).trigger('change');
            $rows.children().each(function (i) { $(this).find('.ad-number').text('广告 ' + (i + 1)); });
            $('#portal-ad-add').prop('disabled', ads.length >= 20);
        }

        function append(ad) {
            if ($rows.children().length >= 20) { return; }
            var id = 'portal-ad-upload-' + (++serial) + '-' + Date.now();
            var $row = $('<div class="portal-ad-row border rounded p-3 mb-3"><div class="d-flex gap-2 align-items-center mb-2"><strong class="ad-number me-auto"></strong><button type="button" class="btn btn-sm btn-light ad-up">上移</button><button type="button" class="btn btn-sm btn-light ad-down">下移</button><button type="button" class="btn btn-sm btn-outline-danger ad-remove">移除</button></div><input type="hidden" class="ad-cover"><img class="ad-preview mb-2" alt="广告图片预览" style="max-width:100%;max-height:130px;display:none"><div class="ad-upload mb-2"><label class="btn btn-light">上传广告图片</label><span class="form-text"></span></div><label class="d-block mb-2">广告跳转链接<input class="form-control ad-link" type="url" maxlength="2048" placeholder="https://" required></label><label class="form-check"><input class="form-check-input ad-enabled" type="checkbox">在前台显示</label></div>');
            $row.find('.ad-cover').val(ad.cover || '');
            $row.find('.ad-link').val(ad.link || '');
            $row.find('.ad-enabled').prop('checked', ad.enabled !== 0);
            if (ad.cover) { $row.find('.ad-preview').attr('src', imageUrl + (imageUrl.indexOf('?') >= 0 ? '&' : '?') + 'reference=' + encodeURIComponent(ad.cover)).show(); }
            $rows.append($row);
            $row.find('.ad-upload').attr('id', id).upload($.extend(true, {}, uploadOptions, {
                buttonTag: 'label',
                before: function () { $('#portal-settings-form').trigger('portalUploadStart'); return true; },
                error: function () { $('#portal-settings-form').trigger('portalUploadEnd'); },
                success: function (data) { $('#portal-settings-form').trigger('portalUploadEnd'); $row.find('.ad-cover').val(data.reference); $row.find('.ad-preview').attr('src', data.url).show(); sync(); }
            }));
            $row.on('input change', '.ad-link,.ad-enabled', sync);
            $row.find('.ad-remove').on('click', function () { $row.remove(); $('#' + id + '-iframe').parent().remove(); sync(); });
            $row.find('.ad-up').on('click', function () { var $prev = $row.prev(); if ($prev.length) { $row.insertBefore($prev); sync(); } });
            $row.find('.ad-down').on('click', function () { var $next = $row.next(); if ($next.length) { $row.insertAfter($next); sync(); } });
            sync();
        }

        var ads = JSON.parse($json.val() || '[]');
        ads.forEach(append);
        $('#portal-ad-add').off('click.portal').on('click.portal', function () { append({enabled: 1}); });
        $('#portal-clear-contact-icon').off('click.portal').on('click.portal', function () { $('#settingsform-ad_icon').val(''); $('#settingsform-ad_icon-preview').hide().attr('src', ''); });
        $('#portal-settings-submit').off('click.portal').on('click.portal', function () {
            if (pendingUploads > 0) { ui.errorBox('请等待图片上传完成。'); return; }
            sync();
            form.btnAjaxSubmit($(this), function (result) {
                if (!result.success()) { ui.errorBox(result.message()); return; }
                ui.successBox(result.message(), function () { ui.navigate(result.data().url); });
            });
        });
    };
});
