<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 * @license http://www.larva.com.cn/license/
 */

namespace App\Admin\Controllers\User;

use App\Admin\Repositories\PassportClient;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Support\Carbon;

/**
 * OAuth客户端管理
 * @author Tongle Xu <xutongle@gmail.com>
 */
class ClientController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new PassportClient(), function (Grid $grid) {
            $grid->model()->repository()->eloquent()->hidden = [];
            $grid->id->sortable();
            $grid->user_id;
            $grid->name;
            $grid->secret;
            $grid->redirect;
            $grid->personal_access_client;
            $grid->password_client;
            $grid->revoked;
            $grid->created_at->sortable();
            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->scope('today', '今天数据')->whereDay('created_at', Carbon::today());
                $filter->scope('yesterday', '昨天数据')->whereDay('created_at', Carbon::yesterday());
                $thisWeek = [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
                $filter->scope('this_week', '本周数据')
                    ->whereBetween('created_at', $thisWeek);
                $lastWeek = [Carbon::now()->startOfWeek()->subWeek(), Carbon::now()->endOfWeek()->subWeek()];
                $filter->scope('last_week', '上周数据')->whereBetween('created_at', $lastWeek);
                $filter->scope('this_month', '本月数据')->whereMonth('created_at', Carbon::now()->month);
                $filter->scope('last_month', '上月数据')->whereBetween('created_at', [Carbon::now()->subMonth()->startOfDay(), Carbon::now()->subMonth()->endOfDay()]);
                $filter->scope('year', '本年数据')->whereYear('created_at', Carbon::now()->year);

                $filter->equal('id', '应用ID');
                $filter->equal('user_id', '用户ID');
                $filter->equal('revoked', '吊销')->select([
                    0 => '否',
                    1 => '是',
                ]);
                $filter->equal('personal_access_client', '个人访问令牌')->select([
                    0 => '否',
                    1 => '是',
                ]);
                $filter->equal('password_client', '密码授权')->select([
                    0 => '否',
                    1 => '是',
                ]);
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new PassportClient(), function (Show $show) {
            $show->id;
            $show->user_id;
            $show->name;
            $show->secret;
            $show->redirect;
            $show->personal_access_client;
            $show->password_client;
            $show->revoked;
            $show->created_at;
            $show->updated_at;
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new PassportClient(), function (Form $form) {
            $form->number('user_id');
            $form->text('name')->required();
            $form->text('secret');
            $form->text('redirect')->default('http://localhost');
            $form->switch('personal_access_client');
            $form->switch('password_client');
            $form->switch('revoked');
        });
    }
}
