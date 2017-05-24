@extends('index.manage-master')
@include('includes.timepicker')
@section('subtitle', '出库')

@section('container')
    @include('includes.menu')
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="col-sm-12 path-title">
                    <a href="{{ url('inventory') }}">库存管理</a> >
                    <span class="second-level">出库</span>
                </div>
            </div>
            <div class="row delivery">
                <div class="col-sm-12 control-search">
                    <form action="" method="get" autocomplete="off">
                        <input class="enter control datetimepicker" name="start_at" placeholder="开始时间" type="text"
                               value="{{$data['start_at'] ?? ''}}">至
                        <input class="enter control datetimepicker" name="end_at" placeholder="结束时间" type="text"
                               value="{{$data['end_at'] ?? ''}}">
                        <input class="enter control" name="number" placeholder="出库单号/售货单号" type="text"
                               value="{{$data['number'] ?? ''}}">
                        <button type="button" class=" btn btn-blue-lighter search control search-by-get">搜索</button>
                        <a href="{{ url('inventory/out-create') }}" class="btn btn-default control">我要出库</a>
                        <div class="warehousing-error-btn red">出库异常<a href="{{ url('inventory/out-error') }}"
                                                                      class="badge badge-danger">{{$errorCount}}</a>
                        </div>
                    </form>
                </div>
                <div class="col-sm-12 table-responsive table-wrap">
                    <table class="table-bordered table table-center table-title-blue">
                        <thead>
                        <tr>
                            <th>出库单号</th>
                            <th>售货单号</th>
                            <th>买家名称</th>
                            <th>类型</th>
                            <th>出库人</th>
                            <th>出库时间</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(isset($lists))
                            @foreach($lists as $list)
                                <tr>
                                    <td>{{$list->inventory_number ?? ''}}</td>
                                    <td>{{$list->order_number <= 0 ? '---' : $list->order_number}}</td>
                                    <td>{{$list->buyer_name ?? '---'}}</td>
                                    <td>{{cons()->valueLang('inventory.inventory_type',$list->inventory_type).cons()->valueLang('inventory.action_type',$list->action_type)}}</td>
                                    <td>{{$list->user->user_name ?? '系统'}}</td>
                                    <td>{{$list->created_at}}</td>
                                    <td><a class="edit"
                                           href="{{url('inventory/out-detail')}}/{{$list->inventory_number}}">查看</a>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@stop
@section('js')
    @parent
    <script type="text/javascript">
        $(function () {
            formSubmitByGet();
        })
    </script>
@stop
