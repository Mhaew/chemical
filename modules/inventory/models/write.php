<?php
/**
 * @filesource modules/inventory/models/write.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Inventory\Write;

use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=inventory-write
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int $id ID
     *
     * @return object|null
     */
    public static function get($id)
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'id' => 0,
                'product_no' => '',
                'topic' => '',
                'cheme_no' => '',
                'un_class' => '',
                'grade' => '',
                'exp' => '',
                'size' => '',
                'sds' => '',
                'seller' => '',
                'inuse' => 1,
                'unit' => '',
                'vat' => 0,
                'category_id' => 0,
                'type_id' => 0,
                'model_id' => 0
            );
        } else {
            // แก้ไข อ่านรายการที่เลือก
            $query = static::createQuery()
                ->from('inventory V')
                ->join('inventory_items I', 'LEFT', array('I.inventory_id', 'V.id'))
                ->where(array('V.id', $id));
            $select = array('V.*', 'I.product_no', 'I.unit');
            $n = 1;
            foreach (Language::get('INVENTORY_METAS', []) as $key => $label) {
                $query->join('inventory_meta M'.$n, 'LEFT', array(array('M'.$n.'.inventory_id', 'V.id'), array('M'.$n.'.name', $key)));
                $select[] = 'M'.$n.'.value '.$key;
                ++$n;
            }
            return $query->first($select);
        }
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (write.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, can_manage_inventory, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_manage_inventory') && Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $save = array(
                        'topic' => $request->post('topic')->topic(),
                        'cheme_no' => $request->post('cheme_no')->topic(),
                        'un_class' => $request->post('un_class')->topic(),
                        'grade' => $request->post('grade')->topic(),
                        'exp' => $request->post('exp')->topic(),
                        'size' => $request->post('size')->topic(),
                        'sds' => $request->post('sds')->topic(),
                        'seller' => $request->post('seller')->topic(),
                        'inuse' => $request->post('inuse')->topic()
                    );
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt());
                    if ($index) {
                        // หมวดหมู่
                        $category = \Inventory\Category\Model::init(false);
                        foreach ($category->items() as $key => $label) {
                            if ($key != 'unit') {
                                $save[$key] = $category->save($key, $request->post($key.'_text')->topic());
                            }
                        }
                        $meta = [];
                        foreach (Language::get('INVENTORY_METAS', []) as $key => $label) {
                            if ($key == 'detail') {
                                $meta[$key] = $request->post($key)->textarea();
                            } else {
                                $meta[$key] = $request->post($key)->topic();
                            }
                        }
                        // Database
                        $db = $this->db();
                        // ตาราง
                        $table_inventory = $this->getTableName('inventory');
                        $inventory_items = $this->getTableName('inventory_items');
                        $table_meta = $this->getTableName('inventory_meta');
                        if ($index->id == 0) {
                            // ใหม่
                            $items = array(
                                'product_no' => $request->post('product_no')->topic(),
                                'stock' => $request->post('stock')->toDouble(),
                                'unit' => $request->post('unit_text')->topic(),
                                'cheme_no' => $request->post('cheme_no')->topic(),
                                'un_class' => $request->post('un_class')->topic(),
                                'grade' => $request->post('grade')->topic(),
                                'exp' => $request->post('exp')->topic(),
                                'size' => $request->post('size')->topic(),
                                'sds' => $request->post('sds')->topic(),
                                'seller' => $request->post('seller')->topic(),

                            );
                            if ($items['product_no'] == '') {
                                // ไม่ได้กรอก product_no
                                $ret['ret_product_no'] = 'Please fill in';
                            } else {
                                // ค้นหา product_no ซ้ำ
                                $search = $db->first($inventory_items, array('product_no', $items['product_no']));
                                if ($search && $index->id != $search->inventory_id) {
                                    $ret['ret_product_no'] = Language::replace('This :name already exist', array(':name' => Language::get('Serial/Registration No.')));
                                }
                            }
                            if ($items['unit'] == '') {
                                // ไม่ได้กรอก unit
                                $ret['ret_unit'] = 'Please fill in';
                            } else {
                                // save unit
                                $category->save('unit', $items['unit']);
                            }
                            if ($items['stock'] == 0) {
                                // ไม่ได้กรอก stock
                                $ret['ret_stock'] = 'Please fill in';
                            }
                        }
                        if ($save['topic'] == '') {
                            // ไม่ได้กรอก topic
                            $ret['ret_topic'] = 'Please fill in';
                        }
                        if ($save['cheme_no'] == '') {
                            // ไม่ได้กรอก cheme_no
                            $ret['ret_cheme_no'] = 'Please fill in';
                        }
                        if ($save['un_class'] == '') {
                            // ไม่ได้กรอก un_class
                            $ret['ret_un_class'] = 'Please fill in';
                        }
                        if ($save['grade'] == '') {
                            // ไม่ได้กรอก grade
                            $ret['ret_grade'] = 'Please fill in';
                        }
                        if ($save['exp'] == '') {
                            // ไม่ได้กรอก exp
                            $ret['ret_exp'] = 'Please fill in';
                        }
                        if ($save['size'] == '') {
                            // ไม่ได้กรอก size
                            $ret['ret_size'] = 'Please fill in';
                        }
                        if ($save['sds'] == '') {
                            // ไม่ได้กรอก sds
                            $ret['ret_sds'] = 'Please fill in';
                        }
                        if ($save['seller'] == '') {
                            // ไม่ได้กรอก seller
                            $ret['ret_seller'] = 'Please fill in';
                        }
                        if (!empty($ret)) {
                            // ถ้ามีข้อผิดพลาด, คืนค่า
                            echo json_encode($ret);
                            return;
                        }

                        // อัปโหลดไฟล์
                        $dir = ROOT_PATH.DATA_FOLDER.'inventory/';
                        foreach ($request->getUploadedFiles() as $item => $file) {
                            /* @var $file \Kotchasan\Http\UploadedFile */
                            if ($item == 'picture') {
                                if ($file->hasUploadFile()) {
                                    if (!File::makeDirectory($dir)) {
                                        // ไดเรคทอรี่ไม่สามารถสร้างได้
                                        $ret['ret_'.$item] = Language::replace('Directory %s cannot be created or is read-only.', DATA_FOLDER.'inventory/');
                                    } elseif (!in_array($file->getClientMediaType(), array('image/jpeg', 'image/png'))) {
                                        // ไม่ใช่ไฟล์รูปภาพที่รองรับ
                                        $ret['ret_'.$item] = Language::get('Invalid file type');
                                    } elseif ($file->getSize() > 2 * 1024 * 1024) {
                                        // ขนาดไฟล์ใหญ่เกินไป
                                        $ret['ret_'.$item] = Language::get('File size exceeds limit');
                                    } else {
                                        try {
                                            $file->resizeImage(self::$cfg->inventory_img_typies, $dir, $save['id'].'.jpg', self::$cfg->inventory_w);
                                        } catch (\Exception $exc) {
                                            // ไม่สามารถอัปโหลดได้
                                            $ret['ret_'.$item] = Language::get($exc->getMessage());
                                        }
                                    }
                                } elseif ($file->hasError()) {
                                    // ข้อผิดพลาดการอัปโหลด
                                    $ret['ret_'.$item] = Language::get($file->getErrorMessage());
                                }
                            }
                        }
                        if (!empty($ret)) {
                            // ถ้ามีข้อผิดพลาด, คืนค่า
                            echo json_encode($ret);
                            return;
                        }

                        // บันทึกข้อมูลในฐานข้อมูล
                        try {
                            if ($index->id == 0) {
                                // ใหม่
                                $save['id'] = $db->getNextId($table_inventory);
                                $db->insert($table_inventory, $save);
                                // เพิ่ม inventory_items รายการแรก
                                $db->delete($inventory_items, array('inventory_id', $save['id']), 0);
                                $items['inventory_id'] = $save['id'];
                                $db->insert($inventory_items, $items);
                            } else {
                                // แก้ไข
                                $save['id'] = $index->id;
                                $db->update($table_inventory, $save['id'], $save);
                            }
                            // อัปเดต meta
                            $db->delete($table_meta, array('inventory_id', $save['id']), 0);
                            foreach ($meta as $key => $value) {
                                if ($value != '') {
                                    $db->insert($table_meta, array(
                                        'inventory_id' => $save['id'],
                                        'name' => $key,
                                        'value' => $value
                                    ));
                                }
                            }
                            // log
                            \Index\Log\Model::add($save['id'], 'inventory', 'Save', '{LNG_Equipment} ID : '.$save['id'], $login['id']);
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'inventory-setup'));
                            // เคลียร์
                            $request->removeToken();
                        } catch (\Exception $e) {
                            $ret['alert'] = Language::get('Unable to complete the transaction: ').$e->getMessage();
                        }
                        echo json_encode($ret);
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    // คืนค่า ข้อผิดพลาดการกรอกข้อมูล
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
    }
}
