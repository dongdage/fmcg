@extends('index.manage-master')
@include('includes.timepicker')
@section('subtitle', '财务管理-提现记录')
@section('top-title')
    <a href="{{ url('personal/finance/balance') }}">财务管理</a> >
    <span class="second-level"> 提现记录</span>
@stop
@section('container')
    @include('includes.menu')
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="col-sm-12 path-title">
                    <a href="{{ url('personal/finance/balance') }}">财务管理</a> >
                    <span class="second-level"> 提现记录</span>
                </div>
            </div>
            <form method="get" action="{{ url('personal/finance/withdraw') }}" autocomplete="off">
                <div class="row balance">
                    <div class="col-sm-12 ">
                        @include('index.personal.finance-common')
                        <div class="table-responsive">
                            <div class="time form-group table">
                                提现单号 : <input type="text" class=" inline-control control" name="id"
                                              value="{{ $data['id'] or '' }}"/>
                                时间段： <input class="datetimepicker inline-control control" name="start_time"
                                            data-format="YYYY-MM-DD"
                                            type="text" value="{{ $data['start_time'] or '' }}"> 至
                                <input class="datetimepicker inline-control control" name="end_time"
                                       data-format="YYYY-MM-DD"
                                       value="{{ $data['end_time'] or '' }}"
                                       type="text">
                                <input type="submit" value="提交" class="btn btn-blue-lighter search-by-get">
                            </div>

                            <table class="table table-bordered table-center table-middle public-table">
                                <thead>
                                <tr>
                                    <th>提现单号</th>
                                    <th>提现金额</th>
                                    <th>银行卡所有人</th>
                                    <th>银行账号</th>
                                    <th>银行名称</th>
                                    <th>状态</th>
                                    <th>交易单号</th>
                                    <th>操作详情</th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($withdraws as $withdraw)
                                    <tr>
                                        <td>{{ $withdraw->id }}</td>
                                        <td>{{ $withdraw->amount }}</td>
                                        <td>{{ $withdraw->card_holder }}</td>
                                        <td>{{ $withdraw->card_number }}</td>
                                        <td>{{ cons()->valueLang('bank.type')[$withdraw->card_type] }}</td>
                                        <td>{{ $withdraw->status_info }} </td>
                                        <td>{{ $withdraw->trade_no }} </td>
                                        <td><a class="show-item btn btn-success" data-target="#withdraw-item"
                                               data-toggle="modal"
                                               data-data='{!! json_encode(['created_at'=>$withdraw->created_at->toDateTimeString(),'failed_at'=>$withdraw->failed_at->toDateTimeString(),'pass_at'=>$withdraw->pass_at->toDateTimeString(),'payment_at'=>$withdraw->payment_at->toDateTimeString(),'reason'=>$withdraw->reason]) !!}'>详细信息</a>
                                        </td>
                                    </tr>
                                @endforeach

                                @if($withdraws->isEmpty())
                                    <tr>
                                        <td colspan="8" align="center">
                                            无记录
                                        </td>
                                    </tr>
                                @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="text-right">
                            {!! $withdraws->appends($data)->render() !!}
                        </div>
                    </div>
                </div>
            </form>
            @include('includes.withdraw')
            <div class="modal fade in" id="withdraw-item" tabindex="-1" role="dialog"
                 aria-labelledby="cropperModalLabel"
                 aria-hidden="true" style="padding-right: 17px;">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="width:70%;margin:auto">

                        <div class="modal-header choice-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">关闭</button>
                            <div class="modal-title forgot-modal-title" id="cropperModalLabel">
                                <span>操作详情:</span>
                            </div>
                        </div>
                        <div class="modal-body">
                            <div class="text-left"></div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @parent
@stop
@section('js')
    @parent
    <script type="text/javascript">
        $(function () {
            getWithdrawTimeItem();
            formSubmitByGet();
            $("[data-toggle='popover']").popover();
        });
    </script>
@stop



