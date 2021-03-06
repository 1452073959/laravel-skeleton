<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 * @license http://www.larva.com.cn/license/
 */

namespace App\Admin\Forms\Settings;

/**
 * 基本设置
 * @author Tongle Xu <xutongle@gmail.com>
 */
class System extends Settings
{
    /**
     * Build a form here.
     */
    public function form()
    {
        $this->text('system.icp_record','ICP备案')->rules('string|nullable');
        $this->text('system.police_record','公安备案')->rules('string|nullable');
        $this->email('system.support_email','服务邮箱')->rules('email|nullable');
    }
}
