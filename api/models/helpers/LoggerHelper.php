<?php

namespace frontend\modules\api\models\helpers;

class LoggerHelper
{
    const ADD_TO_COMPARE                = 1;
    const REMOVE_FROM_COMPARE           = 2;
    const ADD_TO_FAVORITE               = 3;
    const REMOVE_FROM_FAVORITE          = 4;
    const CART_REMOVE_PRODUCT           = 6;
    const CHECKOUT_GET_PAYMENT_TYPE     = 7;
    const CHECKOUT_USE_PROMO_CODE       = 8;
    const CHECKOUT_REMOVE_PROMO_CODE    = 9;
    const CHECKOUT_USE_LIKE_IT          = 10;
    const CHECKOUT_REMOVE_LIKE_IT       = 11;
    const CHECKOUT_DELIVERY_CHANGE      = 12;
    const CART_CHANGE                   = 13;
    const CART_ACCESSORY_ADD            = 14;
    const ADD_PRODUCT_TO_CART           = 15;
    const USER_SIGN_UP                  = 16;
    const ORDER_SAVE                    = 17;
    const PRODUCT_PAGE_VIEW             = 18;
    const CRM_POPUP                     = 19;
    const ADD_TO_CART                   = 20;


    /*
    * Действия пользователя с Сайта
    * */
    public static function actionNames() {

        $statuses = [
            self::ADD_TO_COMPARE => 'Товар успешно добавлен в список сравнения!',
            self::REMOVE_FROM_COMPARE => 'Товар удален из списка сравнения!',
            self::ADD_TO_FAVORITE => 'Товар успешно добавлен в избранное!',
            self::REMOVE_FROM_FAVORITE => 'Товар удален из избранного!',
            self::CART_REMOVE_PRODUCT => 'Корзина. Удаление товара!',
            self::CHECKOUT_GET_PAYMENT_TYPE => 'Получение цен для типа оплаты:',
            self::CHECKOUT_USE_PROMO_CODE => 'Применение промо-кода:',
            self::CHECKOUT_REMOVE_PROMO_CODE => 'Удаление промо-кода:',
            self::CHECKOUT_USE_LIKE_IT => 'Использование likeIt:',
            self::CHECKOUT_REMOVE_LIKE_IT => 'Удаление likeIt:',
            self::CHECKOUT_DELIVERY_CHANGE => 'Тип доставки изменен на:',
            self::CART_CHANGE => 'Корзина. Код товара -',
            self::CART_ACCESSORY_ADD => 'Корзина. Добавление аксессуара!',
            self::ADD_PRODUCT_TO_CART => 'Товар успешно добавлен в корзину!',
            self::USER_SIGN_UP => 'Пользователь успешно зарегистрировался. Email:',
            self::ORDER_SAVE => 'Заказ успешно создан!',
            self::PRODUCT_PAGE_VIEW => 'Страницу с товаром',
            self::CRM_POPUP => 'Всплывающее окно',
            self::ADD_TO_CART => 'Добавил товар в корзину'
        ];

        return $statuses;
    }

    public static function getName($option) {

        $statuses = self::actionNames();

        return isset($statuses[$option]) ? $statuses[$option] : '';

    }

}