<?php
/**
 * @filesource modules/inventory/controllers/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Inventory\Setup;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=inventory-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ตารางรายการ สินค้า
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_List of} {LNG_Inventory}');
        // เลือกเมนู
        $this->menu = 'settings';
    
        // ตรวจสอบสิทธิ์
        if (Login::checkPermission(Login::isMember(), 'can_manage_inventory')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array('class' => 'breadcrumbs'));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-product">{LNG_Settings}</span></li>');
            $ul->appendChild('<li><span>{LNG_Inventory}</span></li>');
            $ul->appendChild('<li><span>{LNG_List of}</span></li>');
            $section->add('header', array('innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>'));
    
            // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'settings', 'inventory'));
            $div = $section->add('div', array('class' => 'content_bg'));
    
            // ดึงค่าจากฟอร์มค้นหามาใช้งาน
            $params = [];
            $params['stock_condition'] = $request->request('stock_condition')->toInt(); // รับค่าของ stock_condition
    
            // แสดงตาราง
            $div->appendChild(\Inventory\Setup\View::create()->render($request, $params)); // ส่ง params ไปที่ View
    
            // คืนค่า HTML
            return $section->render();
        }
        // 404 ถ้าไม่มีสิทธิ์
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
    
    
}
