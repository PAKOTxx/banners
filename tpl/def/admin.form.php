<?php
    /**
     * @var $this Banners
     */
    tpl::includeJS(array('datepicker','autocomplete'), true);
    $aData = HTML::escape($aData, 'html', array('click_url','link','title','alt','description'));
    $edit = ! empty($id);

    $aTypes = array(
        Banners::TYPE_IMAGE => array('t'=>'Изображение', 'key'=>'image', 'image'=>true),
        Banners::TYPE_FLASH => array('t'=>'Flash', 'key'=>'flash', 'image'=>true),
        Banners::TYPE_CODE  => array('t'=>'Код', 'key'=>'code', 'image'=>false),
        //Banners::TYPE_TEASER=> array('t'=>'Тизер', 'key'=>'teaser', 'image'=>true),
    );
    if( ! isset($aTypes[$type]) ) {
        $type = key($aTypes);
    }

    $sitemap = ( ! empty($sitemap_id) ? explode(',', $sitemap_id) : array());
    $sitemap = $this->getSitemap($sitemap, 'checkbox', 'sitemap_id');

    $specialization = ( ! empty($specialization_id) ? explode(',', $specialization_id) : array());
    $specialization = $this->getSpecializations($specialization, 'checkbox', 'specialization_id');

    $flash = $this->flashData( (isset($type_data) ? $type_data : '') );
    $region_title = HTML::escape( Geo::regionTitle($region_id) );
    $countrySelect = Geo::countrySelect();
    if (!isset($country_id)) {
        $country_id = 0;
    }
?>
<form method="post" action="" enctype="multipart/form-data" id="j-banner-form" class="hidden">
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr>
    <td class="row1 field-title" width="120">Позиция баннера:</td>
    <td class="row2">
        <select name="pos" onchange="jBanners.onPosition();" id="j-banner-position" style="width: 260px;">
            <? foreach($positions as $v): ?>
                <option value="<?= $v['id'] ?>" data="{sitemap:<?= $v['filter_sitemap'] ?>,region:<?= $v['filter_region'] ?>,specialization:<?= $v['filter_specialization'] ?>}"<? if($pos == $v['id']){ ?> selected="selected"<? } ?>><?= $v['title'] ?> (<?= $v['sizes'] ?>)</option>
            <? endforeach; ?>
        </select>
    </td>
</tr>
<tr class="j-banner-filter hidden" id="j-banner-filter-sitemap">
    <td class="row1 field-title">Раздел:</td>
    <td class="row2"><div style="overflow-y:scroll; overflow-x:hidden; height: 250px; width: 240px; border: 1px solid #DDD9D8; padding:10px; background-color: #fff;"><?= $sitemap ?></div></td>
</tr>
<tr class="j-banner-filter hidden" id="j-banner-filter-specialization">
    <td class="row1 field-title">Специализация:</td>
    <td class="row2"><div style="overflow-y:scroll; overflow-x:hidden; height: 300px; width: 240px; border: 1px solid #DDD9D8; padding:10px; background-color: #fff;"><?= $specialization ?></div></td>
</tr>
<tr class="j-banner-filter hidden" id="j-banner-filter-region">
    <td class="row1 field-title">Регион:</td>
    <td class="row2">
        <? if($countrySelect): ?><select name="country_id" id="j-banner-country-id"><?= HTML::selectOptions(Geo::countryList(), $country_id, 'Во всех странах', 'id', 'title') ?></select><br /><? endif; ?>
        <input type="hidden" name="region_id" value="<?= $region_id ?>" id="j-banner-region-id" />
        <input type="text" name="region_title" value="<?= $region_title ?>" id="j-banner-region-title" placeholder="Во всех регионах" class="autocomplete" style="width: 212px;" />
    </td>
</tr>
<? $locales = bff::locale()->getLanguages(false); $locale = (empty($locale) ? array(Banners::LOCALE_ALL) : explode(',', $locale)); ?>
<tr id="j-banner-filter-locale" <? if(sizeof($locales) == 1 || ! Banners::FILTER_LOCALE) { ?> style="display: none;"<? } ?>>
    <td class="row1 field-title">Локализация:</td>
    <td class="row2">
        <label class="checkbox inline"><input type="checkbox" class="j-locale-filter j-all" name="locale[]" value="<?= Banners::LOCALE_ALL ?>" <? if(in_array(Banners::LOCALE_ALL,$locale)){ ?> checked="checked"<? } ?> />Все</label>
        <? foreach($locales as $k=>$v) { ?>
            <label class="checkbox inline"><input type="checkbox" class="j-locale-filter" name="locale[]" value="<?= $k ?>" <? if(in_array($k,$locale)){ ?> checked="checked"<? } ?> /><?= $v['title'] ?></label>
        <? } ?>
    </td>
</tr>
<tr class="required">
    <td class="row1 field-title">Дата начала показа:</td>
    <td class="row2">
        <input type="text" name="show_start" id="j-banner-show-start" value="<?= tpl::dateFormat( (!empty($show_start) ? $show_start : time()) , '%d-%m-%Y') ?>" class="input-small" />
    </td>
</tr>
<tr class="required">
    <td class="row1 field-title">Дата окончания показа:</td>
    <td class="row2">
        <input type="text" name="show_finish" id="j-banner-show-finish" value="<?= tpl::dateFormat( (!empty($show_finish) ? $show_finish : time() + 604800) , '%d-%m-%Y') ?>" class="input-small" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Лимит показов:<br /><span class="desc">(число)</span></td>
    <td class="row2">
        <input type="text" name="show_limit" placeholder="нет лимита" value="<?= ($show_limit == 0 ? '' : $show_limit) ?>" class="input-small" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Тип баннера:</td>
    <td class="row2">
        <? foreach($aTypes as $k=>$v) { ?>
            <label class="radio"><input type="radio" name="type" value="<?= $k ?>" <? if($k == $type){ ?> checked="checked"<? } ?> onclick="jBanners.onType(<?= $k ?>);" /><?= $v['t'] ?></label>
        <? } ?>
    </td>
</tr>
<tr id="j-banner-type-data-image" class="j-banner-image hidden">
    <td class="row1 field-title">Изображение:</td>
    <td class="row2">
        <? if($edit && !empty($img)){ ?>
            <a href="<?= $this->buildUrl($id, $img, Banners::szView); ?>" id="j-banner-preview" target="_blank"><img src="<?= $this->buildUrl($id, $img, Banners::szThumbnail); ?>" alt="" title="оригинальный размер" /></a><br /><br />
        <? } ?>
        <label class="inline"><input type="file" name="img" /></label><br />
        <label class="checkbox inline"><input type="checkbox" value="1" checked="checked" name="img_resize" />уменьшать изображение (до требуемых размеров позиции)</label>
    </td>
</tr>
<tr id="j-banner-type-data-flash" class="hidden">
    <td class="row1 field-title">Flash:</td>
    <td class="row2">
        <table style="margin-left: -3px;">  
            <tr>
                <td class="row1">
                    <? if($edit && ! empty($flash['file']))
                    {
                        tpl::includeJS('swfobject', true);
                        ?>
                        <div id="flash_preview" style="display: none;"></div>
                        <script type="text/javascript">
                            swfobject.embedSWF("<?= $this->buildUrl($id, $flash['file'], Banners::szFlash) ?>", "flash_preview", "<?= ($flash['width'] > 0 ? $flash['width']*0.5 : '100%') ?>", "<?= $flash['height']*0.5 ?>", "9.0.0", "<?= SITEURL.'/js/bff/swfobject/' ?>expressInstall.swf", false, {wmode:'opaque'});
                        </script>
                        <br /><br />
                    <? } ?>
                    <input type="file" size="30" name="flash_file" />
                </td>
            </tr>
            <tr>
                <td class="row1 required">
                    <input type="text" name="flash_width" value="<?= floatval($flash['width']) ?>" class="input-mini" /><span class="help-inline">Ширина, px</span>
                </td>
            </tr>
            <tr>
                <td class="row2 required">
                   <input type="text" name="flash_height" value="<?= floatval($flash['height']) ?>" class="input-mini" /><span class="help-inline">Высота, px</span>
                </td>
            </tr> 
            <tr>
                <td class="row2">
                    <input type="text" name="flash_key" value="<?= HTML::escape($flash['key']) ?>" class="input-mini" /><span class="help-inline">Ключ, для передачи ссылки подсчета переходов (flashvars)</span>
                </td>
            </tr>
        </table>
    </td>    
</tr>
<tr id="j-banner-type-data-code" class="hidden">
	<td class="row1 field-title">Код:</td>
	<td class="row2"><textarea name="code" rows="5" class="stretch"><? if($type == Banners::TYPE_CODE){ echo HTML::escape($type_data); } ?></textarea></td>
</tr>
<tr id="j-banner-type-data-teaser" class="hidden">
    <td class="row1 field-title">Текст тизера:</td>
    <td class="row2"><input type="text" name="teaser" value="<?= ($type == Banners::TYPE_TEASER ? HTML::escape($type_data) : '') ?>" class="stretch" /></td>
</tr>
<tr class="required">
    <td class="row1 field-title">Ссылка:</td>
    <td class="row2">
        <input type="text" name="click_url" value="<?= $click_url ?>" class="stretch" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Ссылка подсчета<br/>переходов:</td>
    <td class="row2">
        <input type="text" name="link" value="<?= $link ?>" readonly="readonly" class="stretch" />
    </td>
</tr>
<tbody<? if( ! Banners::FILTER_URL_MATCH ) { ?> class="hidden"<? } ?>>
<tr>
    <td class="row1 field-title">URL размещения:<br />
        <span class="desc small">(относительный URL)</span>
    </td>
    <td class="row2">
        <input type="text" name="url_match" value="<?= $url_match ?>" class="stretch" />
        <span class="desc">Баннер будет отображаться только на странице с указанным адресом и вложенные.<br />
            <label class="inline checkbox"><input type="checkbox" name="url_match_exact"<? if($url_match_exact){ ?> checked="checked"<? } ?> />Не учитывать вложенные страницы (относительно данной адреса)</label></span>
    </td>
</tr>
</tbody>
<tr>
    <td class="row1 field-title">Title:</td>
    <td class="row2">
        <input type="text" name="title" value="<?= $title ?>" class="stretch" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Alt:</td>
    <td class="row2">
        <input type="text" name="alt" value="<?= $alt ?>" class="stretch" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Заметка:</td>
    <td class="row2"><textarea name="description" rows="3"><?= $description ?></textarea></td>
</tr>
<tr >
    <td class="row1 field-title"><?= _t('', 'Enabled') ?>:</td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" name="enabled" value="1" <? if($enabled){ ?> checked="checked"<? } ?> /></label>
    </td>
</tr>
<tr class="footer">
    <td colspan="2">
        <input class="btn btn-success button submit" type="submit" value="<?= _t('', 'Save') ?>" />
        <input class="btn button cancel" type="button" value="<?= _t('', 'Cancel') ?>" onclick="history.back();" />
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
var jBanners = (function(){
    var $form, types = <?= func::php2js($aTypes) ?>;

    $(function(){
        $form = $('#j-banner-form');
        new bff.formChecker($form);

        jBanners.onType(intval(<?= $type ?>));
        jBanners.onPosition();
        $form.removeClass('hidden');

        var bannersShowDateMin = new Date(<?= date('Y,n,d', mktime(0,0,0,date('n')-1, date('d'), date('y'))); ?>);
        bff.datepicker($('#j-banner-show-start', $form), {minDate: bannersShowDateMin, yearRange: '-2:+2'});
        bff.datepicker($('#j-banner-show-finish', $form), {minDate: bannersShowDateMin, yearRange: '-2:+2'});
        $('#j-banner-preview', $form).fancybox.open();

        var sitemapChecks = $('#j-banner-filter-sitemap .j-check', $form).click(function(){
            var $c = $(this);
            if( $c.is(':checked') ) {
                if($c.hasClass('j-all')) {
                    sitemapChecks.filter(':not(.j-all)').prop('checked', false);
                } else {
                    sitemapChecks.filter('.j-all').prop('checked', false);
                }
            }
        });

        var $specializationBlock = $('#j-banner-filter-specialization', $form);
        var $specializationChecks = $('.j-check', $specializationBlock).click(function(){
            var $c = $(this);
            if ($c.hasClass('j-all')) {
                $specializationChecks.not($c).prop({disabled:$c.is(':checked')});
            }
        });

        var regionAC = false;
        $('#j-banner-region-title', $form).autocomplete( '<?= tpl::adminLink('regionSuggest', 'geo') ?>',
            {valueInput: $('#j-banner-region-id', $form), params:{reg:1, country:<?= $country_id ?>}, suggest: <?=  $countrySelect ? ($country_id ? Geo::regionPreSuggest($country_id) : '""') : Geo::regionPreSuggest() ?>}, function(){ regionAC = this; });
        <? if($countrySelect): ?>
        var countryCache = {};
        $('#j-banner-country-id', $form).change(function(){
            var v = intval($(this).val());
            regionAC.setParam('country', v);
            if (countryCache.hasOwnProperty(v)) {
                regionAC.setSuggest(countryCache[v], true);
            } else {
                bff.ajax('<?= $this->adminLink('ajax&act=country-presuggest', 'geo') ?>', {country:v}, function(data){
                    countryCache[v] = data;
                    regionAC.setSuggest(data, true);
                });
            }
        });
        <? endif; ?>

        var $localeFilter = $form.find('.j-locale-filter');
        $form.on('click', '.j-locale-filter', function(){
            var $c = $(this);
            if ($c.hasClass('j-all')) {
                if ($c.is(':checked')) {
                    $localeFilter.not($c).prop({checked:false});
                }
            } else {
                if ($c.is(':checked')) {
                    $localeFilter.filter('.j-all').prop({checked:false});
                }
            }
        });
    });

    return {
        onPosition: function() {
            // скрываем/отображаем фильтры в зависимости от настроек позиции
            var filters = $('#j-banner-position option:selected', $form).metadata();
            $('.j-banner-filter', $form).hide();
            for(var k in filters) {
                if( filters.hasOwnProperty(k) && intval(filters[k]) === 1 ) {
                    $('#j-banner-filter-'+k, $form).show();
                }
            }
        },
        onType: function(typeID)
        {
            var typeKey = types[typeID].key;
            $('[id^="j-banner-type-data-"]', $form).hide();
            $('.j-banner-image', $form).toggle(types[typeID].image);
            $('#j-banner-type-data-'+typeKey, $form).show();
        }
    };
}());
</script>