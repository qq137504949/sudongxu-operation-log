<?php

namespace Sudongxu\OperationLog\Http\Controllers;

use Dcat\Admin\Grid;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Sudongxu\OperationLog\Models\OperationLog;
use Sudongxu\OperationLog\OperationLogServiceProvider;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Arr;
use Dcat\Admin\Admin;


class LogController
{
    public function index(Content $content)
    {
        return $content->title(OperationLogServiceProvider::trans('log.title'))
            ->description(trans('admin.list'))
            ->body($this->grid());
    }

    protected function grid()
    {
        Admin::script($this->getDeleteAllScript());
        return new Grid(OperationLog::with('user'), function (Grid $grid) {
            $grid->model()->where('app_type', Admin::app()->getName());
            $grid->column('id', 'ID')->sortable();

            $grid->column('user', trans('admin.user'))->display(function ($user) {
                if (!$user) {
                    return;
                }

                $user = Helper::array($user);

                return $user['name'] ?? ($user['username'] ?? $user['id']);
            })->link(function () {
                if ($this->user) {
                    return admin_url('auth/users/' . $this->user['id']);
                }
            });

            $grid->column('method', trans('admin.method'))->label(OperationLog::$methodColors)->filterByValue();

            $grid->column('path', trans('admin.uri'))->display(function ($v) {
                return "<code>$v</code>";
            })->filterByValue();

            $grid->column('ip', 'IP')->filterByValue();

            $grid->column('input')->display(function ($input) {
                $input = json_decode($input, true);

                if (empty($input)) {
                    return;
                }

                $input = Arr::except($input, ['_pjax', '_token', '_method', '_previous_']);

                if (empty($input)) {
                    return;
                }

                return '<pre class="dump" style="max-width: 500px">' . json_encode($input,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
            });

            $grid->column('created_at', trans('admin.created_at'));

            $grid->model()->orderBy('id', 'DESC');

            $grid->disableCreateButton();
            $grid->disableQuickEditButton();
            $grid->disableEditButton();
            $grid->disableViewButton();
            $grid->showColumnSelector();
            $grid->setActionClass(Grid\Displayers\Actions::class);


            $grid->filter(function (Grid\Filter $filter) {
                $userModel = config('admin.database.users_model');

                $filter->in('user_id', trans('admin.user'))->multipleSelect($userModel::pluck('name', 'id'));

                $filter->equal('method', trans('admin.method'))->select(array_combine(OperationLog::$methods,
                    OperationLog::$methods));

                $filter->like('path', trans('admin.uri'));
                $filter->equal('ip', 'IP');
                $filter->between('created_at')->datetime();
            });

            $grid->tools(function (Grid\Tools $tools) {
                $tools->append(
                    '<a href="javascript:void(0);"
                       class="btn btn-sm btn-danger"
                       id="delete-selected"
                       title="删除选中日志">
                       <i class="feather icon-trash-2"></i> 删除选中
                    </a>'
                );
                $tools->append(
                    '<a href="javascript:void(0);"
                   class="btn btn-sm btn-danger"
                   id="delete-all"
                   title="清空所有日志">
                   <i class="feather icon-trash-2"></i> 清空全部
                </a>'
                );
            });
        });
    }
    protected function getDeleteAllScript(){
        $deleteSelectedUrl = admin_url("auth/operation-logs/destroy-selected");
        $deleteAllUrl = admin_url("auth/operation-logs/destroy-all");
        return <<<JS
        $('#delete-selected').on('click', function () {
            var selectedRows = Dcat.grid.selectedRows();
            if (selectedRows.length === 0) {
                Dcat.warning('请先选择要删除的记录');
                return;
            }
            Dcat.confirm('确认删除', '确定要删除选中的 ' + selectedRows.length + ' 条记录吗？', function () {
                var ids = [];
                selectedRows.forEach(function (row) {
                    ids.push(row.id);
                });
                $.ajax({
                    url: '{$deleteSelectedUrl}',
                    type: 'POST',
                    data: {
                        ids: ids,
                        _token: Dcat.token,
                    },
                    success: function (data) {
                        if (data.status) {
                           Dcat.swal.success(data.message || '删除成功').then(result=>{
                             Dcat.reload();
                           });

                        } else {
                            Dcat.error(data.message || '删除失败');
                        }
                    },
                    error: function (xhr) {
                        Dcat.error('删除失败');
                    }
                });
            });
        });

        // 全选删除
        $('#delete-all').on('click', function () {
            Dcat.confirm('确认清空', '确定要清空所有日志吗？此操作不可恢复！', function () {
                $.ajax({
                    url: '{$deleteAllUrl}',
                    type: 'POST',
                    success: function (data) {
                        if (data.status) {
                           Dcat.swal.success(data.message || '删除成功').then(result=>{
                               Dcat.reload();
                           });
                        } else {
                            Dcat.error(data.message || '删除失败');
                        }
                    },
                    error: function (xhr) {
                        Dcat.error('删除失败');
                    }
                });
            });
        })
        JS;
    }

    public function destroyAll()
    {
        // 删除当前应用类型的所有操作日志
        OperationLog::query()
            ->where('app_type', Admin::app()->getName())
            ->delete();
        return JsonResponse::make()
            ->success(trans('admin.delete_succeeded'))
            ->refresh()
            ->send();
    }
    public function destroySelected(Request $request)
    {
        $ids = $request->get('ids');
        OperationLog::query()->where('app_type', Admin::app()->getName())->whereIn('id',array_filter($ids))->delete();
        return JsonResponse::make()->success(trans('admin.delete_succeeded'))->refresh()->send();
    }

    public function destroy($id)
    {
        $ids = explode(',', $id);
        OperationLog::query()->where('app_type', Admin::app()->getName())->whereIn('id',array_filter($ids))->delete();
        return JsonResponse::make()->success(trans('admin.delete_succeeded'))->refresh()->send();
    }
}
