<?php

extract($banner, EXTR_REFS);
$aData = HTML::escape($aData, 'html', array('title','alt'));
$showURL  = Banners::url('show', array('id'=>$id));
$clickURL = Banners::url('click', array('id'=>$id));

?><div class="l-<?= (!empty($settings['pos']) ? $settings['pos'] : '') ?>-banner" data-spec="<?= ( ! empty($settings['spec']) ? $settings['spec'] : 0 ) ?>"><?
if($type == Banners::TYPE_CODE)
{
    echo $type_data;
    ?><div style="display:none;" ><img src="<?= $showURL ?>" width="1" height="1" alt="" /></div><?
}
else if($type == Banners::TYPE_FLASH)
{
    tpl::includeJS('swfobject', true);
    $flash = $this->flashData($type_data);
    $jsID = 'j-bn-'.$id.'-';
    ?>
    <script type="text/javascript">
        <? js::start(); ?>
        if(FlashDetect.installed) {
            swfobject.embedSWF("<?= $this->buildUrl($id, $flash['file'], Banners::szFlash) ?>", "<?= $jsID.'f' ?>", "<?= ($flash['width'] > 0 ? $flash['width'] : '100%') ?>", "<?= $flash['height'] ?>", "9.0.0", "<?= SITEURL_STATIC.'/js/bff/swfobject/' ?>expressInstall.swf", <?= ( ! empty($flash['key']) ? '{'.$flash['key'].':"'.HTML::escape($clickURL, 'js').'"}':'false') ?>, {wmode:'opaque'});
        } else {
            $(function(){ $('#<?= $jsID.'i' ?>').show(); });
        }
        <? js::stop(); ?>
    </script>
    <div id="<?= $jsID.'f' ?>" style="display:none;"></div>
    <a style="display:none;" href="<?= $clickURL; ?>" title="<?= $title; ?>" id="<?= $jsID.'i' ?>">
        <img src="<?= $showURL ?>" alt="<?= $alt; ?>" />
    </a>
<? 
}
else
{
    if( ! empty($clickURL)){ ?><a target="_blank" title="<?= $title; ?>" href="<?= $clickURL ?>"><img src="<?= $showURL ?>" alt="<?= $alt; ?>" <? if( ! empty($pos_data['height'])){ ?> height="<?= $pos_data['height'] ?>"<? } ?> /></a>
    <? } else { ?><img src="<?= $showURL ?>" /><? }
}
?></div>