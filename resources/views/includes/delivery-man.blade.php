@section('body')
    <div class="modal fade" id="deliveryModal" tabindex="-1" role="dialog" aria-labelledby="cropperModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="cropperModalLabel">添加配送人员<span class="extra-text"></span></h4>
                </div>
                <div class="modal-body address-select">
                    <form class="form-horizontal ajax-form" action="{{ url('api/v1/personal/delivery-man') }}"
                          method="post" data-help-class="col-sm-push-2 col-sm-10" autocomplete="off">
                        <div class="form-group row">
                            <label class="col-sm-2 control-label" for="name">姓名:</label>

                            <div class="col-sm-10 col-md-6">
                                <input class="form-control" id="name" name="name" placeholder="请输入姓名"
                                       value=""
                                       type="text">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 control-label" for="phone">手机号码:</label>

                            <div class="col-sm-10 col-md-6">
                                <input class="form-control" id="phone" name="phone" placeholder="请输入手机号码"
                                       value=""
                                       type="text">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 control-label" for="user_name">POS机登录名:</label>

                            <div class="col-sm-10 col-md-6">
                                <input class="form-control" id="user_name" name="user_name" placeholder="请输入POS机登录名（必须为6位数字）"
                                       value=""
                                       type="text">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 control-label" for="password">POS机登录密码:</label>

                            <div class="col-sm-10 col-md-6">
                                <input class="form-control" id="password" name="password" placeholder="请输入POS机登录密码"
                                       value=""
                                       type="password">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 control-label" for="password_confirmation">POS机密码确认:</label>

                            <div class="col-sm-10 col-md-6">
                                <input class="form-control" id="password_confirmation" name="password_confirmation" placeholder="请输入POS机登录密码"
                                       value=""
                                       type="password">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 control-label" for="name">POS机编号:</label>

                            <div class="col-sm-10 col-md-6">
                                <input class="form-control" id="pos_sign" name="pos_sign" placeholder="请输入POS机编号"
                                       value=""
                                       type="text">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary btn-sm btn-add" data-text="添加">添加</button>
                                <button type="button" class="btn btn-default btn-sm btn-close" data-dismiss="modal">关闭
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @parent
@stop
