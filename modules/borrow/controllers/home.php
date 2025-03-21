<?php
/**
 * @filesource modules/borrow/controllers/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Borrow\Home;

use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=borrow-home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟังก์ชั่นสร้าง card
     *
     * @param Request         $request
     * @param \Kotchasan\Html $card
     * @param array           $login
     */
    public static function addCard(Request $request, $card, $login)
    {
        if ($login) {
            $items = \Borrow\Home\Model::get($login);
            if (isset($items->allpending)) {
                \Index\Home\Controller::renderCard($card, 'icon-exchange', '{LNG_Can be approve}', number_format($items->allpending), '{LNG_Waiting list}', 'index.php?module=borrow-report&amp;status=0');
            }

            \Index\Home\Controller::renderCard($card, 'icon-valid', $login['name'], number_format($items->confirmed), '{LNG_Asking_status} : '.Language::get('BORROW_STATUS', null, 2), 'index.php?module=borrow-setup&amp;status=2');
            \Index\Home\Controller::renderCard($card, 'icon-close', $login['name'], number_format($items->returned), '{LNG_Asking_status} :  '.Language::get('BORROW_STATUS', null, 1), 'index.php?module=borrow-setup&amp;status=1');
            \Index\Home\Controller::renderCard($card, 'icon-exchange', $login['name'], number_format($items->pending), '{LNG_Asking_status} :  '.Language::get('BORROW_STATUS', null, 0), 'index.php?module=borrow-setup&amp;status=0');
        }
    }
}