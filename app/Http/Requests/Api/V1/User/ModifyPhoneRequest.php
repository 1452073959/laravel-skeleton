<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 * @license http://www.larva.com.cn/license/
 */

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Request;

/**
 * 修改手机号码请求
 * @property-read int $phone
 * @property-read string $verifyCode
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class ModifyPhoneRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return (bool)$this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'phone' => [
                'required',
                'max:11',
                'phone',
                'unique:users',
            ],
            'verify_code' => [
                'required',
                'min:4',
                'max:6',
                'phone_verify_code:phone',
            ],
        ];
    }
}
