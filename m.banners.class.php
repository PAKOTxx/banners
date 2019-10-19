<?php

class M_Banners
{
    public static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if (!$security->haveAccessToModuleToMethod('banners', 'listing')) {
            return;
        }

        # Баннеры
        $menu->assign('Баннеры', 'Список', 'banners', 'listing', true, 1, array(
                'rlink' => array('event' => 'add')
            )
        );
        $menu->assign('Баннеры', 'Добавление баннера', 'banners', 'add', false, 2);
        $menu->assign('Баннеры', 'Редактирование баннера', 'banners', 'edit', false, 3);

        # Статистика
        $menu->assign('Баннеры', 'Статистика по баннеру', 'banners', 'statistic', false, 4);

        # Позиции
        $menu->assign('Баннеры', 'Позиции', 'banners', 'positions', true, 5, (FORDEV ? array(
                'rlink' => array('event' => 'positions&act=add')
            ) : array())
        );
        $menu->assign('Баннеры', 'Удаление позиции', 'banners', 'position_delete', false, 6);
    }
}