<?php
    tpl::includeJS(array('datepicker','autocomplete'), true);
    tplAdmin::adminPageSettings(array(
        'link'=>array('title'=>'+ добавить баннер', 'href'=>$this->adminLink('add')),
        'fordev'=>array(
            array('title'=>'сбросить кеш', 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('ajax&act=dev-reset-cache')."'})", 'icon'=>'glyphicon glyphicon-refresh'),
        ),
    ));
    $locales = bff::locale()->getLanguages(false);
    $localeFilter = (Banners::FILTER_LOCALE && sizeof($locales) > 1);
    $countrySelect = Geo::countrySelect();
?>
<div class="actionBar">
    <form action="<?= $this->adminLink(NULL) ?>" method="get" name="bannersForm" id="j-banners-form" class="form-inline">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <input type="hidden" name="order" value="<?= $order_by.tpl::ORDER_SEPARATOR.$order_dir ?>" />
        <div class="controls controls-row">
            <select name="pos" class="input-medium"<? if($localeFilter) { ?> style="width: 130px;"<? } ?>>
                 <option value="">Все позиции</option>
                 <? foreach($positions as $k=>$v) { ?>
                    <option value="<?= $v['id'] ?>" <? if($f['pos'] == $v['id']){ ?>selected="selected"<? } ?>><?= $v['title'] ?>&nbsp;(<?= $v['sizes'] ?>)</option>
                 <? } ?>
            </select>
            <? if($localeFilter) { ?>
            <select name="locale" onchange="jBannersList.submit();" style="width: 120px;">
                <option value=""<? if(empty($f['locale'])){ ?> selected="selected"<? } ?>>Локализация</option>
                <option value="<?= Banners::LOCALE_ALL ?>"<? if($f['locale'] == Banners::LOCALE_ALL){ ?> selected="selected"<? } ?>>Все локализации</option>
                <? foreach ($locales as $k=>$v) { ?>
                    <option value="<?= $k ?>"<? if($f['locale'] == $k){ ?> selected="selected"<? } ?>><?= $v['title'] ?></option>
                <? } ?>
            </select>&nbsp;
            <? } ?>
            <label style="display: none;">
                <input type="hidden" name="region" value="<?= $f['region'] ?>" id="j-banners-region-id" />
                <input type="text" name="" value="<?= HTML::escape( Geo::regionTitle($f['region']) ) ?>" id="j-banners-region-ac" class="input-medium autocomplete" placeholder="Во всех регионах" style="width: 120px;" />
            </label>
            &nbsp;Показ: <input type="text" name="show_start" value="<?= HTML::escape($f['show_start']) ?>" placeholder="с" style="width:70px;" />
            <input type="text" name="show_finish" value="<?= HTML::escape($f['show_finish']) ?>" placeholder="по" style="width:70px;" />
            &nbsp;<select name="status" style="width: 110px;"><?= HTML::selectOptions(array(0=>'все',1=>'выключенные',2=>'включенные'), $f['status']) ?></select>
            &nbsp;<input class="btn btn-small button submit" type="submit" value="<?= _t('', 'search') ?>" />
            <a class="cancel" onclick="jBannersList.reset(); return false;"><?= _t('', 'reset') ?></a>
        </div>
    </form>
</div> 
<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <?
            $aCols = array(
                'id'          => array('t'=>'ID',       'w'=>45,   'order'=>'desc'),
                'title'       => array('t'=>'Баннер',   'w'=>false,'order'=>false),
                'limit'       => array('t'=>'Лимит',    'w'=>60,   'order'=>false),
                'show_start'  => array('t'=>'Начало показа', 'w'=>75, 'order'=>'desc'),
                'show_finish' => array('t'=>'Конец показа',  'w'=>75, 'order'=>'desc'),
                'shows'       => array('t'=>'Показов',  'w'=>66,   'order'=>'desc'),
                'clicks'      => array('t'=>'Кликов',   'w'=>60,   'order'=>'desc'),
                'ctr'         => array('t'=>'CTR(%)',   'w'=>61,   'order'=>'desc'),
                'action'      => array('t'=>'Действие', 'w'=>105,   'order'=>false),
            );
            foreach($aCols as $k=>$v) {
                if( empty($v['order']) ) {
                    ?><th<? if(!empty($v['w'])) echo ' width="'.$v['w'].'"' ?>><?= $v['t'] ?></th><?
                } else {
                    ?><th<? if(!empty($v['w'])) echo ' width="'.$v['w'].'"' ?>>
                     <? if( $order_by == $k ) { ?>
                        <a href="javascript:void(0);" onclick="jBannersList.order('<?= $k ?>-<?= $order_dir_needed ?>');"><?= $v['t'] ?>
                        <div class="order-<?= $order_dir ?>"></div></a>
                     <? } else { ?>
                        <a href="javascript:void(0);" onclick="jBannersList.order('<?= $k ?>-<?= $v['order'] ?>');"><?= $v['t'] ?></a>
                     <? } ?>
                     </th><?
                }
            }
        ?>
    </tr>
</thead>
<? foreach($banners as $k=>$v) { ?>
<tr class="row<?= $k%2 ?>" <? if( ! $v['enabled']) { ?> style="color:#808080"<? } ?>>
        <td class="small"><?= $v['id'] ?></td>
        <td width="200">
            <a href="<?= $v['click_url'] ?>" class="but linkout" target="_blank"></a><a href="javascript:void(0)" onclick="return jBannersList.preview(<?= $v['id'] ?>);"><?= $v['pos']['title'] ?></a>&nbsp;<span class="desc small">(<?= $v['pos']['sizes'] ?>)</span>
            <? /* ?><br /><a href="#" onclick="jBannersList.region(<?= $v['region_id'] ?>); return false;" class="desc"><?= $v['region_title'] ?></a><br /><? */ ?>
            <? if($localeFilter && ! empty($v['locale']) && ! in_array(Banners::LOCALE_ALL, $v['locale'])) { ?>
               <span class="desc"><? foreach ($v['locale'] as $l) { ?><a href="javascript:void(0);" class="but lng-<?= $l ?>" style="margin-right: 3px;"></a><? } ?></span>
            <? } ?>
        </td>
        <td><?= ( ! empty($v['show_limit']) ? $v['show_limit'] : 'нет') ?></td>
        <td><?= tpl::date_format3($v['show_start'], 'Y-m-d') ?></td>
        <td><?= tpl::date_format3($v['show_finish'], 'Y-m-d') ?></td>
        <td><?= intval($v['shows']) ?></td>
        <td><?= intval($v['clicks']) ?></td>
        <td><?= $v['ctr'] ?></td>
        <td>
            <a class="but sett" title="Статистика" href="<?= $this->adminLink('statistic&id='.$v['id']) ?>" ></a>
            <a class="but <? if($v['enabled']){ ?>un<? } ?>block" onclick="return jBannersList.toggle(<?= $v['id'] ?>, this);"></a>
            <a class="but edit" href="<?= $this->adminLink('edit&id='.$v['id']) ?>"></a>
            <a class="but del" href="#" onclick="bff.confirm('sure',{r: '<?= $this->adminLink('delete&id='.$v['id']) ?>'}); return false;"></a>
        </td>       
</tr>
<? } if(empty($banners)) { ?>
<tr class="norecords">
    <td colspan="9">нет баннеров</td>
</tr>
<? } ?>
</table>
<script type="text/javascript">
var jBannersList = (function(){
    var $form;

    $(function(){
        $form = $('#j-banners-form');
        bff.datepicker('input[name^=show_]', {yearRange: '-5:+5'});
        $('#j-banners-region-ac').autocomplete( '<?= $this->adminLink('regionSuggest', 'geo') ?>',
            {valueInput: $('#j-banners-region-id'), params:{<?= $countrySelect ? 'all:1' : 'reg:1' ?>}, suggest: <?= ! $countrySelect ? Geo::regionPreSuggest() : 'false' ?>});
    });

    function formSubmit()
    {
        $form.submit();
    }

    return {
        toggle: function(id, link)
        {
            bff.ajaxToggle(id, '<?= $this->adminLink('ajax&act=banner-toggle') ?>', {link: link});
            return false;
        },
        order: function(order)
        {
           $('[name=order]', $form).val(order);
           formSubmit();
        },
        preview: function(id)
        {
            bff.ajax('<?= $this->adminLink('preview') ?>', {id:id}, function(data){
                if(data) {
                    $.fancybox.open(data, {touch : false});
                }
            });
            return false;
        },
        region: function(regionID)
        {
            $('#j-banners-region-id', $form).val(regionID);
            formSubmit();
        },
        reset: function()
        {
            bff.redirect('<?= $this->adminLink(bff::$event) ?>');
        }
    };
}());
</script>