<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 * @license http://www.larva.com.cn/license/
 */

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

/**
 * 用户模型
 * @property int $id ID
 * @property string $phone 手机号
 * @property string $username 昵称
 * @property string $email 邮箱
 * @property string $password 密码
 * @property string $remember_token
 * @property Carbon|null $phone_verified_at 手机验证时间
 * @property Carbon|null $email_verified_at 邮箱验证时间
 * @property Carbon $created_at 注册时间
 * @property Carbon $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 *
 * @property UserExtra $extra 扩展信息
 * @property UserProfile $profile 个人信息
 * @property UserSocial[] $socials 社交账户
 * @property UserDevice[] $devices 移动设备
 * @property UserLoginHistory[] $loginHistories 登录历史
 *
 * @method static \Illuminate\Database\Eloquent\Builder|User phone($phone)
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * 可以批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'username', 'email', 'phone', 'password',
    ];

    /**
     * 隐藏输出的属性
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * 属性类型转换
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * 获取用户资料
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * 获取用户扩展资料
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function extra()
    {
        return $this->hasOne(UserExtra::class);
    }

    /**
     * 获取用户已经绑定的社交账户
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function socials()
    {
        return $this->hasMany(UserSocial::class);
    }

    /**
     * 获取登录历史
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function loginHistories()
    {
        return $this->hasMany(UserLoginHistory::class);
    }

    /**
     * 获取用户设备列表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * 查询指定的手机号
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $phone
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePhone($query, $phone)
    {
        return $query->where('phone', $phone);
    }

    /*
     * 获取手机号
     * @param \Illuminate\Notifications\Notification|null $notification
     * @return int|null
     */
    public function routeNotificationForPhone($notification)
    {
        return $this->phone;
    }

    /**
     * 获取移动端设备
     * @param \Illuminate\Notifications\Notification|null $notification
     * @return UserDevice|null
     */
    public function routeNotificationForDevice($notification)
    {
        return UserDevice::byUser($this->id)->latest('id')->first();
    }

    /**
     * 用户是否在线
     * @return bool
     */
    public function isOnline()
    {
        return Cache::has('user-online-' . $this->id);
    }

    /**
     * 发送邮箱验证通知
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        if (!is_null($this->email)) {
            $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail);
        }
    }

    /**
     * Determine if the user has verified their phone number.
     *
     * @return bool
     */
    public function hasVerifiedPhone()
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Mark the given user's phone as verified.
     *
     * @return bool
     */
    public function markPhoneAsVerified()
    {
        $status = $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
        event(new \App\Events\User\PhoneVerified($this));
        return $status;
    }

    /**
     * 重置用户密码
     *
     * @param string $password
     * @return void
     */
    public function resetPassword($password)
    {
        $this->password = Hash::make($password);
        $this->setRememberToken(\Illuminate\Support\Str::random(60));
        $this->save();
        event(new \Illuminate\Auth\Events\PasswordReset($this));
    }

    /**
     * 重置用户手机号
     * @param int $phone
     * @return bool
     */
    public function resetPhone($phone)
    {
        $status = $this->forceFill([
            'phone' => $phone,
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
        event(new \App\Events\User\PhoneReset($this));
        return $status;
    }

    /**
     * 重置用户邮箱
     * @param string $email
     * @return bool
     */
    public function resetEmail($email)
    {
        $status = $this->forceFill([
            'email' => $email,
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
        event(new \App\Events\User\MailReset($this));
        return $status;
    }

    /**
     * 更新最后登录
     * @param string $clientIp
     * @param string $userAgent
     */
    public function updateLogin($clientIp, $userAgent = null)
    {
        $this->extra()->increment('login_num', 1, [
            'login_at' => $this->fromDateTime($this->freshTimestamp()),
            'login_ip' => $clientIp
        ]);
        $this->loginHistories()->create([
            'ip' => $clientIp,
            'user_agent' => $userAgent
        ]);
    }

    /**
     * 通过ID获取用户，带缓存
     * @param int $id
     * @return User|null
     */
    public static function findById($id)
    {
        if (!$id || $id <= 0 || !is_numeric($id)) {
            return null;
        }
        return Cache::remember('users:' . $id, Carbon::now()->addMinutes(30), function () use ($id) {
            return static::find($id);
        });
    }

    /**
     * 随机生成一个用户名
     * @param string $username 用户名
     * @return string
     */
    public static function generateUsername($username)
    {
        if (static::query()->where('username', '=', $username)->exists()) {
            $row = static::query()->max('id');
            $username = $username . ++$row;
        }
        return $username;
    }
}