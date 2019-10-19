<?php

use bff\img\Thumbnail;
use bff\utils\Files;

abstract class BannersBase extends Module
{
    /** @var BannersModel */
    public $model = null;
    /** bool Задействовать настройку фильтрации в зависимости от авторизации пользователей */
    const FILTER_AUTH_USERS = true;
    /** bool Задействовать настройку фильтрации по REQUEST_URI */
    const FILTER_URL_MATCH = true;
    /** bool Задействовать настройку фильтрации по локализации */
    const FILTER_LOCALE = true;

    # типы баннеров
    const TYPE_IMAGE  = 1;
    const TYPE_FLASH  = 2;
    const TYPE_CODE   = 3;
    const TYPE_TEASER = 4;

    # префиксы файлов
    const szThumbnail = 't';
    const szView = 'v';
    const szFlash = 'f';

    /** @var string путь к изображению баннера по-умолчанию */
    protected $defaultImagePath = '';
    /** @var string путь к файлам баннера (изображения, flash) */
    protected $filesPath = '';
    /** @var string URL к файлам баннера (изображения, flash) */
    protected $filesUrl = '';

    # Специальные разделы сайта
    const SITEMAP_ALL   = -1; # все разделы сайта
    const SITEMAP_INDEX = -2; # главная страница сайта

    # Специальные специализации
    const SPECIALIZATION_ALL = -1; # все специализации

    # Локализации
    const LOCALE_ALL = 'all'; # для всех локализаций

    public function init()
    {
        parent::init();

        $this->filesPath = PATH_PUBLIC . 'files' . DS . 'bnnrs' . DS;
        $this->filesUrl = SITEURL . '/files/bnnrs/';
        $this->defaultImagePath = $this->filesPath . 'default.gif';
    }

    /**
     * Shortcut
     * @return Banners
     */
    public static function i()
    {
        return bff::module('banners');
    }

    /**
     * Shortcut
     * @return BannersModel
     */
    public static function model()
    {
        return bff::model('banners');
    }

    public static function url($key, $opts = array(), $dynamic = false)
    {
        $base = SITEURL;
        switch ($key) {
            # ссылка перехода
            case 'click':
                return $base . '/bn/click/' . (!empty($opts['id']) ? $opts['id'] : '');
                break;
            # ссылка просмотра
            case 'show':
                return $base . '/bn/show/' . (!empty($opts['id']) ? $opts['id'] : '');
                break;
        }
    }

    /**
     * Получаем код баннера по ключу позиции
     * @param string $sPositionKey ключ позиции
     * @param array $aSettings доп. параметры
     * @return string HTML
     */
    public function viewByPosition($sPositionKey, array $aSettings = array())
    {
        $sResult = '';
        do {
            # настройки позиции (по ключу позиции)
            static $aPositions;
            if (!isset($aPositions)) {
                $aPositions = $this->model->positionsList();
            }
            $aPosition = array();
            $nPositionID = 0;
            foreach ($aPositions as $v) {
                if ($v['keyword'] == $sPositionKey) {
                    $aPosition = $v;
                    $nPositionID = $v['id'];
                }
            }
            if (!$nPositionID || empty($aPosition) || !$aPosition['enabled']) {
                break;
            }

            # баннеры на позиции (по ID позиции)
            $aBanners = $this->model->bannersData(array('pos' => $nPositionID));
            if (empty($aBanners)) {
                break;
            }
            # в случае если ротация на позиции запрещена - оставляем первый из полученных баннеров
            if (!$aPosition['rotation'] && sizeof($aBanners) > 0) {
                $aBanners = array_slice($aBanners, 0, 1);
            }

            # фильтруем баннеры:

            # 1. Скрываем для авторизованных пользователей
            if (self::FILTER_AUTH_USERS && $aPosition['filter_auth_users'] && User::id()) {
                break;
            }

            # 2. Регион
            if ($aPosition['filter_region']) {
                if (!empty($aSettings['region'])) {
                    if (Geo::countrySelect()) {
                        $regionData = Geo::regionData($aSettings['region']);
                        foreach ($aBanners as $k => $v) {
                            if (!$v['region_id']) {
                                continue;
                            }
                            if ($v['region_id'] == $regionData['id']) {
                                continue;
                            }
                            if ($v['region']['numlevel'] == Geo::lvlCountry) { # баннер в пределах страны
                                if ($regionData['country'] == $v['region']['id']) {
                                    continue;
                                }
                            } else if ($v['region']['numlevel'] == Geo::lvlRegion) { # баннер в пределах области
                                if ($regionData['pid'] == $v['region']['id']) {
                                    continue;
                                }
                            }
                            unset($aBanners[$k]);
                        }
                    } else {
                        $regionData = Geo::regionData($aSettings['region']);
                        $regionIsCity = Geo::isCity($regionData);
                        foreach ($aBanners as $k => $v) {
                            if (!$v['region_id']) {
                                continue;
                            }
                            # если баннер в пределах города
                            if (Geo::isCity($v['region'])) {
                                if ($regionIsCity) {
                                    if ($v['region_id'] == $regionData['id']) {
                                        continue;
                                    }
                                } else {
                                    if ($v['region']['pid'] == $regionData['id']) {
                                        continue;
                                    }
                                }
                                # если баннер в пределах области
                            } else {
                                if ($regionIsCity) {
                                    if ($regionData['pid'] == $v['region_id']) {
                                        continue;
                                    }
                                } else {
                                    if ($v['region_id'] == $regionData['id']) {
                                        continue;
                                    }
                                }
                            }
                            unset($aBanners[$k]);
                        }
                    }
                } else {
                    # при отсутствии фильтрации по региону(все регионы),
                    # игнорируем баннеры с определенным регионом
                    foreach ($aBanners as $k => &$v) {
                        if ($v['region_id']) {
                            unset($aBanners[$k]);
                        }
                    }
                    unset($v);
                }
            }

            # 3. Раздел сайта
            if ($aPosition['filter_sitemap'] && sizeof($aBanners) > 0) {
                $bIndex = bff::isIndex();
                $nSitemapID = Sitemap::i()->getActivatedID();
                foreach ($aBanners as $k => $v) {
                    if (!$v['sitemap']) {
                        continue;
                    }
                    if ($v['sitemap_all']
                        || ($bIndex && $v['sitemap_index'])
                        || ($nSitemapID && in_array($nSitemapID, $v['sitemap']))
                    ) {
                        continue;
                    }
                    unset($aBanners[$k]);
                }
            }

            # 4. Специализация
            if ($aPosition['filter_specialization'] && sizeof($aBanners) > 0)
            {
                $specID = (!empty($aSettings['spec']) ? $aSettings['spec'] : 0);
                if ($specID > 0) {
                    foreach ($aBanners as $k => $v) {
                        if (!$v['specialization'] || array_sum($v['specialization']) == self::SPECIALIZATION_ALL) {
                            continue;
                        } else {
                            # простая проверка
                            if (in_array($specID, $v['specialization'])) {
                                continue;
                            }
                        }
                        unset($aBanners[$k]);
                    }
                } else {
                    # при отсутствии фильтрации по специализации,
                    # игнорируем баннеры с определенным специализациями
                    foreach ($aBanners as $k => &$v) {
                        if ($v['specialization'] && array_sum($v['specialization']) != self::SPECIALIZATION_ALL) {
                            unset($aBanners[$k]);
                        }
                    }
                    unset($v);
                }
            }

            # 5. URI
            if (self::FILTER_URL_MATCH && sizeof($aBanners) > 0 && ($URI = Request::uri())) {
                foreach ($aBanners as $k => $v) {
                    if (empty($v['url_match'])) {
                        continue;
                    }
                    if (!$v['url_match_exact']) {
                        # частичное совпадение
                        if (mb_strpos($URI, $v['url_match']) === 0) {
                            continue;
                        }
                    } else {
                        # полное совпадение
                        if ($URI == $v['url_match']) {
                            continue;
                        }
                    }
                    unset($aBanners[$k]);
                }
            }

            # 6. Локализация
            if (self::FILTER_LOCALE && sizeof($aBanners) > 0)
            {
                foreach ($aBanners as $k => &$v) {
                    if (empty($v['locale']) || in_array(self::LOCALE_ALL, $v['locale'])) {
                        continue;
                    }
                    if (in_array(LNG, $v['locale'])) {
                        continue;
                    }
                    unset($aBanners[$k]);
                }
                unset($v);
            }

            if (empty($aBanners)) {
                break;
            }
            if (sizeof($aBanners) > 1) {
                $aBanners = array($aBanners[array_rand($aBanners, 1)]);
            }
            $aData = array(
                'banner' => reset($aBanners),
                'settings' => $aSettings,
            );

            $sResult = $this->viewPHP($aData, 'view');
        } while (false);

        // не выводим пустой блок
        //if (empty($sResult) && ! isset($aSettings['no-empty'])) {
        //    $sResult = $this->viewPHP($aSettings, 'view.empty');
        //}

        return $sResult;
    }

    /**
     * Доступна ли ротация баннеров на указанной позиции
     * @param integer $nPositionID ID позиции
     * @return bool
     */
    protected function checkPositionRotation($nPositionID)
    {
        $aPositionData = $this->model->positionData($nPositionID);
        if (empty($aPositionData)) {
            return false;
        }

        if (!$aPositionData['rotation'] && $aPositionData['banners_enabled'] > 0) {
            return false;
        }

        return true;
    }

    /**
     * Загрузка файла изображения
     * @param integer $nBannerID ID баннера
     * @param integer $nPositionID ID позиции
     * @param string $sFieldName имя file-поля
     * @return bool|string
     */
    protected function imgUpload($nBannerID, $nPositionID, $sFieldName = 'img')
    {
        $aPositionData = $this->model->positionData($nPositionID);
        if (empty($aPositionData)) {
            return false;
        }
        if (!isset($_FILES[$sFieldName]) || $_FILES[$sFieldName]['error'] == UPLOAD_ERR_NO_FILE) {
            return false;
        }
        $FILE = $_FILES[$sFieldName];
        $sFilenameTmp = $FILE['tmp_name'];

        $sExtension = Files::getExtension($FILE['name']);
        if (!in_array($sExtension, array('jpg', 'gif', 'png'))) {
            $this->errors->set('Допустимые форматы изображений: jpg, png, gif');

            return false;
        }
        $sFilename = func::generator(8) . '.' . $sExtension;

        $aImageSize = getimagesize($sFilenameTmp);
        if (empty($aImageSize)) {
            return false;
        }
        $nWidth = $aImageSize[0];

        $szView = array(
            'filename' => $this->buildPath($nBannerID, $sFilename, self::szView),
        );
        if ($this->input->post('img_resize', TYPE_BOOL)) {
            # сохраняем, урезаем до требуемых размеров (настройки позиции)
            $szView['width'] = (!empty($aPositionData['width']) ? $aPositionData['width'] : false);
            $szView['height'] = (!empty($aPositionData['height']) ? $aPositionData['height'] : false);
        } else {
            # сохраняем в исходном размере
            $szView['original_sizes'] = true;
        }
        $aSaveSettings = array(
            array(
                'filename' => $this->buildPath($nBannerID, $sFilename, self::szThumbnail),
                'width'    => ($nWidth > 100 ? 100 : $nWidth),
                'height'   => false,
            ),
            $szView
        );

        $oThumb = new Thumbnail($sFilenameTmp, false);
        if ($oThumb->save($aSaveSettings)) {
            return $sFilename;
        }
    }

    /**
     * Удаление изображения баннера
     * @param integer $nBannerID ID баннера
     * @param string $sFilename имя файла
     */
    protected function imgDelete($nBannerID, $sFilename)
    {
        foreach (array(
                     self::szThumbnail,
                     self::szView,
                 ) as $sizePrefix) {
            $sSizeFilename = $this->buildPath($nBannerID, $sFilename, $sizePrefix);
            if (file_exists($sSizeFilename)) {
                unlink($sSizeFilename);
            }
        }
    }

    /**
     * Загрузка flash файла баннера
     * @param integer $nBannerID ID баннера
     * @param string $sFieldName имя file-поля
     * @return bool|string
     */
    protected function flashUpload($nBannerID, $sFieldName = 'flash_file')
    {
        if (empty($_FILES[$sFieldName]) || $_FILES[$sFieldName]['error'] == UPLOAD_ERR_NO_FILE) {
            return false;
        }
        $FILE = $_FILES[$sFieldName];
        $sFilenameTmp = $FILE['tmp_name'];

        # Загружен ли файл?
        if (!is_uploaded_file($sFilenameTmp)) {
            $this->errors->setUploadError(CUploader::errUploadError);

            return false;
        }

        $sExtension = Files::getExtension($FILE['name']);
        if (!in_array($sExtension, array('swf', 'flv'))) {
            $this->errors->set('Допустимые форматы flash-баннера: swf');

            return false;
        }

        $sFilename = func::generator(8) . '.' . $sExtension;
        $sFilenameSave = $this->buildPath($nBannerID, $sFilename, self::szFlash);

        # Сохранение
        if (!move_uploaded_file($sFilenameTmp, $sFilenameSave)) {
            $this->errors->setUploadError(CUploader::errUploadError);

            return false;
        }

        return $sFilename;
    }

    /**
     * Удаление flash файла баннера
     * @param integer $nBannerID ID баннера
     * @param array $aTypeData настройки баннера
     */
    protected function flashDelete($nBannerID, $aTypeData)
    {
        $aData = $this->flashData($aTypeData);
        $sFilename = $this->buildPath($nBannerID, $aData['file'], self::szFlash);
        if (file_exists($sFilename)) {
            unlink($sFilename);
        }
    }

    /**
     * Подготовка настроек баннера типа TYPE_FLASH
     * @param array|string $aTypeData настройки
     * @return array|mixed
     */
    public function flashData($aTypeData)
    {
        $aData = array();
        if (is_string($aTypeData) && strpos($aTypeData, '"file"') !== false) {
            $aData = unserialize($aTypeData);
        } elseif (is_array($aTypeData)) {
            $aData = $aTypeData;
        }

        $this->input->clean_array($aData, array(
                'file'   => TYPE_STR,
                'width'  => TYPE_UINT,
                'height' => TYPE_UINT,
                'key'    => TYPE_STR,
            )
        );

        return $aData;
    }

    /**
     * Строим полный путь к файлу баннера
     * @param integer $nBannerID ID баннера
     * @param string $sFilename имя файла
     * @param string $sPrefix префикс файла
     * @return string
     */
    public function buildPath($nBannerID, $sFilename, $sPrefix)
    {
        return $this->filesPath . $nBannerID . $sPrefix . $sFilename;
    }

    /**
     * Строим URL файла баннера
     * @param integer $nBannerID ID баннера
     * @param string $sFilename имя файла
     * @param string $sPrefix префикс файла
     * @return string
     */
    public function buildUrl($nBannerID, $sFilename, $sPrefix)
    {
        return $this->filesUrl . $nBannerID . $sPrefix . $sFilename;
    }

    /**
     * Удаление баннера / нескольких баннеров по ID
     * @param integer|array $mBannerID ID баннера / нескольких баннеров
     */
    protected function deleteBanner($mBannerID)
    {
        if (empty($mBannerID)) {
            return;
        }
        if (is_array($mBannerID)) {
            foreach ($mBannerID as $id) {
                $this->deleteBanner($id);
            }
        } else {
            $aBannerData = $this->model->bannerData($mBannerID);
            if (!empty($aBannerData)) {
                # удаляем изображение/flash
                switch ($aBannerData['type']) {
                    case self::TYPE_IMAGE:
                    {
                        $this->imgDelete($mBannerID, $aBannerData['img']);
                    }
                        break;
                    case self::TYPE_FLASH:
                    {
                        $this->imgDelete($mBannerID, $aBannerData['img']);
                        $this->flashDelete($mBannerID, $aBannerData['type_data']);
                    }
                        break;
                    case self::TYPE_TEASER:
                    {
                        $this->imgDelete($mBannerID, $aBannerData['img']);
                    }
                        break;
                }
                # удаляем баннер (+статистику)
                $this->model->bannerDelete($mBannerID);
            }
        }
    }

    /**
     * Формируем список разделов Sitemap
     * @param integer|array $aSelectedID ID предвыбранных разделов
     * @param string $sFormat 'option' - в формате <option value="id">, 'checkbox' - в формате <label><input type="checkbox" value="id" /></label>
     * @param string $sFieldName имя поля
     * @return string HTML
     */
    public function getSitemap($aSelectedID, $sFormat = 'option', $sFieldName = 'sitemap_id')
    {
        if (!is_array($aSelectedID)) {
            $aSelectedID = array($aSelectedID);
        }

        # Формируем список разделов меню "main"
        $aMain = Sitemap::model()->itemDataByFilter(array('keyword' => 'main', 'numlevel' => 1));
        $aItems = Sitemap::model()->itemsListing($aMain['numleft'], $aMain['numright']);
        $nNumlevel = $aMain['numlevel'] + 1;
        array_unshift($aItems, array('id'       => self::SITEMAP_INDEX,
                                     'title'    => 'Главная страница',
                                     'numlevel' => $nNumlevel,
                                     'pid'      => self::SITEMAP_INDEX
            )
        );
        array_unshift($aItems, array('id'       => self::SITEMAP_ALL,
                                     'title'    => 'Все разделы сайта',
                                     'numlevel' => $nNumlevel,
                                     'pid'      => self::SITEMAP_ALL
            )
        );

        $sHTML = '';
        if ($sFormat == 'option') {
            # <option value="id">
            array_unshift($aItems, array('id' => 0, 'title' => 'не важно', 'numlevel' => $nNumlevel));
            foreach ($aItems as $v) {
                $sHTML .= '<option value="' . $v['id'] . '" class="' . ($v['id'] == self::SITEMAP_ALL ? 'bold' : '') . '" ' . (in_array($v['id'], $aSelectedID) ? ' selected="selected"' : '') . '>' . ($v['numlevel'] > $nNumlevel ? '&nbsp;&nbsp;&nbsp;' : '') . $v['title'] . '</option>';
            }
        } else {
            if ($sFormat == 'checkbox') {
                # <label><input type="checkbox" value="id" /></label>
                foreach ($aItems as $v) {
                    $sHTML .= '<label class="checkbox" style="margin-left:' . ($v['numlevel'] == $nNumlevel ? 0 : 15) . 'px;' . ($v['id'] == self::SITEMAP_ALL ? ' font-weight:bold;' : '') . '"><input type="checkbox" name="' . $sFieldName . '[]" data-pid="' . $v['pid'] . '" class="j-check' . ($v['id'] == self::SITEMAP_ALL ? ' j-all' : '') . '" value="' . $v['id'] . '"' . (in_array($v['id'], $aSelectedID) ? ' checked="checked"' : '') . ' />' . $v['title'] . '</label>';
                }
            }
        }

        return $sHTML;
    }

    /**
     * Формируем список специализаций
     * @param integer|array $aSelectedID ID предвыбранных специализаций
     * @param string $sFormat 'checkbox' - в формате <label><input type="checkbox" value="id" /></label>
     * @param string $sFieldName имя поля
     * @return string HTML
     */
    public function getSpecializations($aSelectedID, $sFormat = 'checkbox', $sFieldName = 'specialization_id')
    {
        $aCategories = Specializations::model()->specializationsListing(array(':cond' => 'S.enabled = 1'));
        array_unshift($aCategories, array('id'    => self::SPECIALIZATION_ALL,
                                          'title' => 'Все специализации',
                                          'pid'   => 0
            )
        );

        $sHTML = '';
        if ($sFormat == 'checkbox') {
            # <label><input type="checkbox" value="id" /></label>
            $sClassName = '';
            foreach ($aCategories as $v) {
                $sHTML .= '<label class="checkbox" ' . ($v['id'] == self::SPECIALIZATION_ALL ? ' style="font-weight:bold;"' : '') . '><input type="checkbox" name="' . $sFieldName . '[]" data-pclass="' . $sClassName . '" class="j-check ' . $sClassName . ($v['id'] == self::SPECIALIZATION_ALL ? ' j-all' : '') . '" value="' . $v['id'] . '"' . (in_array($v['id'], $aSelectedID) ? ' checked="checked"' : '') . ' />' . $v['title'] . '</label>';
            }
        }

        return $sHTML;
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            $this->filesPath => 'dir', # файлы баннеров
        ));
    }
}