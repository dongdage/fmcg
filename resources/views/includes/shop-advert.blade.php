@include('includes.uploader')
@include('includes.timepicker')
@include('includes.tinymce',['full' => true])
@section('body')
    <div class="modal fade" id="shopAdvertModal" tabindex="-1" role="dialog" aria-labelledby="shopAdvertModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form class="form-horizontal ajax-form" method="post"
                      action="{{ url('api/v1/personal/model/advert') }}"
                      data-help-class="col-sm-push-2 col-sm-10"
                      data-done-then="refresh" autocomplete="off">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="cropperModalLabel">添加店铺广告<span class="extra-text"></span></h4>
                    </div>
                    <div class="modal-body address-select">

                        <div class="form-group">
                            <label for="name" class="col-sm-2 control-label">广告名称</label>

                            <div class="col-sm-4">
                                <input type="text" class="form-control" id="name" name="name" placeholder="请输入广告名">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="upload-file" class="col-sm-2 control-label">广告图片</label>

                            <div class="col-sm-4">
                                <button data-height="200" data-width="800" data-target="#cropperModal" data-toggle="modal"
                                        data-loading-text="图片已达到最大数量" class="btn btn-primary btn-sm" type="button"
                                        id="pic-upload">
                                    请选择图片文件(裁剪)
                                </button>

                                <div class="progress collapse">
                                    <div class="progress-bar progress-bar-striped active"></div>
                                </div>

                                <div class="row pictures">

                                </div>

                                <div class="image-preview w160">
                                    <img src="" class="img-thumbnail">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-2 col-xs-offset-2">
                                <input class="goodsIdRadio" type="radio" name="identity"  checked="checked" value="shop" />商品id
                            </div>
                            <div>
                                <input class="promoteRadio" type="radio" name="identity" value="promote" >促销信息
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="url" class="col-sm-2 control-label goodsId">商品id</label>

                            <div class="col-sm-4 goodsidDiv">
                                <input type="text" class="form-control" id="goods_id" name="goods_id" placeholder="请输入商品id" />
                            </div>
                            <div class="col-xs-8 promoteDiv" style="display:none">
                                <textarea name="promoteinfo"  class="introduce tinymce-editor form-control promotInfo"></textarea>
                            </div>

                        </div>

                        <div class="form-group" id="date-time">
                            <label class="col-sm-2 control-label">起止时间</label>

                            <div class="col-sm-3 time-limit">
                                <input type="text" class="form-control datetimepicker" name="start_at"
                                       placeholder="起始时间"/>
                            </div>

                            <div class="col-sm-3 time-limit">
                                <input type="text" class="form-control datetimepicker" name="end_at" placeholder="结束时间"/>
                            </div>
                            <div class="col-sm-push-2 col-sm-10">
                                <p class="help-block">结束时间为空时，表示广告永久有效。</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-bg btn-success"> 添加</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @parent
@stop
@section('js')
    @parent
    <script type="text/javascript">
        $(document).ready(function () {
            radioCheck();
        })
    </script>
@stop
