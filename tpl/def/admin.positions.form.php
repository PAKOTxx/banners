<?php
    $aData = HTML::escape($aData, 'html', array('title','keyword','width','height'));
    $edit = ! empty($id);
?>
<form name="BannersPositionsForm" id="BannersPositionsForm" action="<?= $this->adminLink(null) ?>" method="get" onsubmit="return false;">
<input type="hidden" name="act" value="<?= ($edit ? 'edit' : 'add') ?>" />
<input type="hidden" name="save" value="1" />
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr class="required">
    <td class="row1 field-title" width="100"><?= _t('', 'Title') ?><span class="required-mark">*</span>:</td>
    <td class="row2">
        <input class="stretch" type="text" id="position-title" name="title" value="<?= $title ?>" />
    </td>
</tr>
<tr class="required">
    <td class="row1 field-title">Keyword<span class="required-mark">*</span>:</td>
    <td class="row2">
        <input class="text-field" type="text" id="position-keyword" name="keyword" value="<?= $keyword ?>" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Ширина:</td>
    <td class="row2">
        <input class="short" type="text" id="position-width" name="width" value="<?= $width ?>" maxlength="25" />
        <div class="help-inline">px</div>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Высота:</td>
    <td class="row2">
        <input class="short" type="text" id="position-height" name="height" value="<?= $height ?>" maxlength="25" />
        <div class="help-inline">px</div>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Ротация:</td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-rotation" name="rotation"<? if($rotation){ ?> checked="checked"<? } ?> /></label>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Фильтры:</td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-filter_sitemap" name="filter_sitemap"<? if($filter_sitemap){ ?> checked="checked"<? } ?> />раздел сайта</label>
        <label class="checkbox" style="display:none;"><input type="checkbox" id="position-filter_region" name="filter_region"<? if($filter_region){ ?> checked="checked"<? } ?> />регион</label>
        <label class="checkbox"><input type="checkbox" id="position-filter_specialization" name="filter_specialization"<? if($filter_specialization){ ?> checked="checked"<? } ?> />специализация</label>
    </td>
</tr>
<tr<? if( ! Banners::FILTER_AUTH_USERS ) { ?> class="hidden"<? } ?>>
    <td class="row1 field-title">Пользователи:</td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-filter_auth_users" name="filter_auth_users"<? if($filter_auth_users){ ?> checked="checked"<? } ?> />скрывать для авторизованных пользователей</label>
    </td>
</tr>
<tr>
    <td class="row1 field-title"><?= _t('', 'Enabled') ?>:</td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-enabled" name="enabled"<? if($enabled){ ?> checked="checked"<? } ?> /></label>
    </td>
</tr>
<tr class="footer">
    <td colspan="2">
        <input type="submit" class="btn btn-success button submit" value="<?= _t('', 'Save') ?>" onclick="jBannersPositionsForm.save(false);" />
        <? if($edit) { ?><input type="button" class="btn btn-success button submit" value="<?= _t('', 'Save and back') ?>" onclick="jBannersPositionsForm.save(true);" /><? } ?>
        <? if($edit && FORDEV) { ?><input type="button" onclick="jBannersPositionsForm.del(); return false;" class="btn btn-danger button delete" value="<?= _t('', 'Delete') ?>" /><? } ?>
        <input type="button" class="btn button cancel" value="<?= _t('', 'Cancel') ?>" onclick="jBannersPositionsFormManager.action('cancel');" />
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
var jBannersPositionsForm =
(function(){
    var $progress, $form, formChk, id = <?= $id ?>;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $progress = $('#BannersPositionsFormProgress');
        $form = $('#BannersPositionsForm');
        
    });
    return {
        del: function()
        {
            if( id > 0 ) {
                bff.redirect('<?= $this->adminLink('position_delete&id=') ?>'+id);
            }
        },
        save: function(returnToList)
        {
            if( ! formChk.check(true) ) return;
            bff.ajax(ajaxUrl, $form.serialize(), function(data){
                if(data && data.success) {
                    bff.success('Данные успешно сохранены');
                    if(returnToList || ! id) {
                        jBannersPositionsFormManager.action('cancel');
                        jBannersPositionsList.refresh( ! id);
                    }
                }
            }, $progress);
        },
        onShow: function ()
        {
            formChk = new bff.formChecker( $form );
        }
    };
}());
</script>