@extends('child-user.manage-master')
@include('includes.timepicker')
@include('includes.order-refund')
@include('includes.order-invalid')
@include('includes.shipping-address-map')
@include('includes.order-select-delivery_man')
@include('includes.select-templete')


@section('subtitle', '订单列表')

@section('container')
    @include('includes.child-menu')
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="col-sm-12 path-title">
                    <a href="{{ url('child-user/order') }}">订单管理</a> >
                    <span class="second-level"> 订单列表</span>
                </div>
            </div>
            <div class="row wholesalers-management margin-clear">
                @include('child-user.order.menu')
                <form class="form" method="get" action="{{ url('child-user/order') }}" autocomplete="off">
                    @if (\Request::is('child-user/order'))
                        <div class="col-sm-8 pay-detail search-options">
                            <select name="type" class="ajax-select control">
                                <option value="">全部类型</option>
                                @foreach(cons()->valueLang('order.type') as $key => $value)
                                    <option value="{{ $key }}" {{ !is_null(array_get($search, 'type')) && $key == array_get($search, 'type') ? 'selected' : ''}}>{{ $value }}</option>
                                @endforeach
                            </select>
                            <select name="pay_type" class="ajax-select control">
                                <option value="">全部方式</option>
                                @foreach(cons()->valueLang('pay_type') as $key => $value)
                                    <option value="{{ $key }}" {{ $key==array_get($search, 'pay_type') ? 'selected' : ''}}>{{ $value }}</option>
                                @endforeach
                            </select>

                            <select name="status" class="ajax-select control">
                                <option value="">全部状态</option>
                                @foreach($order_status as $key => $value)
                                    <option value="{{ $key }}" {{ $key == array_get($search, 'status') ? 'selected' : ''}}>{{ $value }}</option>
                                @endforeach
                            </select>
                            <label>下单时间：</label>
                            <input type="text" class="datetimepicker control" placeholder="开始时间" name="start_at"
                                   data-format="YYYY-MM-DD" value="{{ $search['start_at'] or '' }}"/>　至　
                            <input type="text" class="datetimepicker control" id="end-time" placeholder="结束时间"
                                   name="end_at"
                                   data-format="YYYY-MM-DD" value="{{ $search['end_at'] or '' }}"/>
                        </div>
                        <div class="col-sm-4 right-search search search-options">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search_content" placeholder="终端商、订单号"
                                       aria-describedby="course-search" value="{{ $search['search_content'] or '' }}">
                                <span class="input-group-btn">
                    <button class="btn btn-primary ajax-submit search-by-get">搜索</button>
                </span>
                            </div>
                        </div>
                    @endif
                    <div class="col-sm-12 table-responsive table-col">
                        @foreach($orders as $order)
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th width="15%">
                                        <label>
                                            <input type="checkbox" class="order_id children" name="order_id[]"
                                                   value="{{ $order['id'] }}">
                                            订单号 :<b>{{ $order['id'] . '(' . $order->type_name . ')' }} </b>
                                        </label>
                                        <span class="order-number">下单时间 : {{ $order['created_at'] }}</span>
                                    </th>
                                    <th width="25%" colspan="4">
                                        <b>{{ $order->user_shop_name }}</b>
                                        @if ($order->user && $order->user->shop_id)
                                            <a href="javascript:"
                                               onclick="window.open('{{ url('child-user/chat/kit?remote_uid=' .$order->user->shop_id) }}&fullscreen', 'webcall',  'toolbar=no,title=no,status=no,scrollbars=0,resizable=0,menubar＝0,location=0,width=700,height=500');"
                                               class="contact"><span class="iconfont icon-kefu"></span> 联系客户</a>
                                        @endif
                                        @if($order->pay_type != cons('pay_type.pick_up'))
                                            <a href="javascript:" class="check-address"
                                               data-target="#shippingAddressMapModal"
                                               data-toggle="modal"
                                               data-x-lng="{{ isset($order->shippingAddress)? $order->shippingAddress->x_lng : 0 }}"
                                               data-y-lat="{{ isset($order->shippingAddress)? $order->shippingAddress->y_lat : 0 }}"
                                               data-address="{{ isset($order->shippingAddress->address) ? $order->shippingAddress->address->address_name : '' }}"
                                               data-consigner="{{  isset($order->shippingAddress) ? $order->shippingAddress->consigner : '' }}"
                                               data-phone= {{ isset($order->shippingAddress) ? $order->shippingAddress->phone : '' }}
                                            >
                                                <i class="fa fa-map-marker"></i> 查看收货地址
                                            </a>
                                        @endif
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($order->goods as $key => $goods)
                                    <tr>
                                        <td width="50%">
                                            <img class="store-img" src="{{ $goods->image_url }}">

                                            <div class="product-panel">
                                                <a class="product-name ellipsis"
                                                   href="{{  url('child-user/my-goods/' . $goods['id']) }}">{{ $goods->name }}</a>
                                                {!! $goods->is_promotion ? '<div class="promotions">(<span class="ellipsis"> ' . $goods->promotion_info . '</span>)</div>' : '' !!}

                                            </div>
                                        </td>
                                        <td width="10%" class="bordered text-center">
                                            <span class="red">¥{{ $goods['pivot']['price'] }}</span>
                                            /{{ cons()->valueLang('goods.pieces', $goods->pivot->pieces)  }}
                                        </td>
                                        </td>
                                        <td width="10%"
                                            class="bordered text-center">{{ '╳ '.$goods['pivot']['num'] }}</td>
                                        @if(0 == $key )
                                            <td rowspan="{{ $order->goods->count() }}"
                                                class="pay-detail text-center bordered"
                                                width="15%">
                                                <p>{{ $order['status_name'] }}</p>

                                                <p>{{ $order['payment_type'] }}</p>

                                                <p><span class="red">¥{{ $order['after_rebates_price'] }}</span></p>
                                            </td>
                                            <td rowspan="{{ $order->goods->count()}}"
                                                class="operating text-center bordered"
                                                width="15%">
                                                <p><a href="{{ url('child-user/order/'.$order['id']) }}"
                                                      class="btn btn-blue">查看</a></p>
                                                @if(!$order['is_cancel'])
                                                    @if($order->can_confirm)
                                                        <p>
                                                            <a class="btn btn-warning ajax" data-method='put'
                                                               data-url="{{ url('api/v1/child-user/order/order-confirm/' . $order->id) }}">
                                                                确认订单
                                                            </a>
                                                        </p>
                                                    @endif
                                                    @if($order['can_send'])
                                                        <p>
                                                            <a class="btn btn-warning send-goods"
                                                               data-target="#sendModal" data-toggle="modal"
                                                               data-id="{{ $order['id'] }}">
                                                                发货
                                                            </a>
                                                        </p>
                                                    @elseif($order['can_confirm_collections'])
                                                        <p><a class="btn btn-blue ajax" data-method='put'
                                                              data-url="{{ url('api/v1/child-user/order/batch-finish-of-sell') }}"
                                                              data-data='{"order_id":{{ $order['id'] }}}'>确认收款</a></p>
                                                    @endif
                                                    @if($order['can_export'])
                                                        <p>
                                                            <a class="btn btn-blue-lighter btn-print"
                                                               data-id="{{ $order->id }}" target="_blank"
                                                               href="javascript:"
                                                               data-templete-url="{{ url('api/v1/child-user/templete') }}"
                                                               data-url="{{ url('child-user/order/browser-export?order_id='.$order['id']) }}">打印</a>
                                                        </p>
                                                        <p>
                                                            <a class="btn btn-blue-lighter"
                                                               href="{{ url('child-user/order/export?order_id='.$order['id']) }}">下载</a>

                                                        <div class="prompt">
                                                            （{{ $order->download_count ? '已下载打印' . $order->download_count . '次'  :'未下载' }}
                                                            ）
                                                        </div>
                                                        </p>
                                                    @endif
                                                    @if($order->can_refund)
                                                        <p>
                                                            <a class="btn btn-red refund" data-danger="真的要取消该订单吗？"
                                                               data-target="#refund"
                                                               data-toggle="modal"
                                                               data-seller="true"
                                                               data-url="{{ url('api/v1/child-user/pay/refund/' . $order->id) }}">
                                                                取消并退款
                                                            </a>
                                                        </p>
                                                    @elseif($order['can_cancel'])
                                                        <p>
                                                            <a class="btn btn-red ajax" data-danger="真的要取消该订单吗？"
                                                               data-method='put'
                                                               data-url="{{ url('api/v1/order/child-user/cancel-sure') }}"
                                                               data-data='{"order_id":{{ $order->id }}}'>
                                                                取消
                                                            </a>
                                                        </p>
                                                    @elseif($order['can_invalid'])
                                                        <p>
                                                            <a class="btn btn-red" data-target="#invalid"
                                                               data-toggle="modal"
                                                               data-url="{{ url('api/v1/child-user/order/invalid/' . $order->id) }}">
                                                                作废
                                                            </a>
                                                        </p>

                                                    @endif
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endforeach
                    </div>
                    <div class="col-sm-12 text-right">
                        @if(\Request::is('order-sell'))
                            {!! $orders->appends($search)->render() !!}
                        @else
                            {!! $orders->render() !!}
                        @endif
                    </div>
                    @if(\Request::is('order-sell') && $orders->count() )
                        <div class="col-sm-12" id="foot-nav">
                            <input type="checkbox" class="parent">全选
                            <button class="btn btn-red ajax" data-url="{{ url('api/v1/child-user/order/cancel-sure') }}"
                                    data-method="put">批量取消
                            </button>
                            <a class="btn btn-primary batch-send" data-target="#sendModal" data-toggle="modal">批量发货</a>
                            <button class="btn btn-blue ajax btn-receive"
                                    data-url="{{ url('api/v1/child-user/order/batch-finish-of-sell') }}" data-method="put">确认收款
                            </button>
                            <a class="btn btn-blue-lighter export" data-url="{{ url('child-user/order/export') }}"
                               data-method="get">下载
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
@stop
@section('js')
    @parent
    <script type="text/javascript">
        $(function () {
            getOrderButtonEvent();
            onCheckChange('.parent', '.children');
            formSubmitByGet(['delivery_man_id', 'order_id[]']);
            @if(session('export_error'))
                alert('{{ session('export_error') }}');
            @endif
        })
    </script>
@stop