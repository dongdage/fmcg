@extends('index.manage-master')
@section('subtitle', '月对账单')
@include('includes.timepicker')
@section('container')
    @include('includes.menu')
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="col-sm-12 path-title">
                    <a href="{{ url('personal') }}">月对账单</a> >
                    <span class="second-level">账单详情</span>
                </div>
            </div>
            <div class="row delivery">
                <div class="col-sm-12 control-search assets">
                    <form action="" method="get" autocomplete="off">
                        <input placeholder="输入日期" class="enter control datetimepicker" data-format="YYYY-MM" type="text"
                               name="time"
                               value="{{$timeInterval['start_at']->format('Y-m') ?? \Carbon\Carbon::now()->format('Y-m')}}">
                        <button type="button" class=" btn btn-blue-lighter search-by-get control ">查询</button>
                        <a href="{{url('personal/bill/'.$shop->id.'?time='.($timeInterval['start_at'] ->format('Y-m') ?? '').'&act=export')}}"
                           class=" btn btn-border-blue control ">打印</a>
                    </form>
                </div>
                <div class="col-sm-12 statement-item">
                    <div class="col-sm-12 statement-item">
                        <h3 class="text-center title">{{$shop -> name or null }} —— 月对账单</h3>
                        <div class="row list-wrap">
                            <div class="col-sm-5 item-col">
                                <label>商户名称 : </label>
                                {{$shop -> name }}
                            </div>
                            <div class="col-sm-7 item-col">
                                <label>对账时间 : {{$timeInterval['start_at']->toDateString()}}
                                    — {{$timeInterval['end_at']->toDateString()}}</label>

                            </div>
                            <div class="col-sm-12 item-col">
                                <label>商户地址 : {{$shop->shopAddress->area_name ?? ''}}</label>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12">
                    <table class="table table-bordered table-center public-table ">
                        <thead>
                        <tr>
                            <th>总计金额</th>
                            <th>优惠券金额</th>
                            <th>已支付金额</th>
                            <th>未支付金额</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>{{$bill['orderForm']->sum('after_rebates_price')}}</td>
                            <td>{{sprintf("%.2f",$bill['orderForm']->sum('how_much_discount')-$bill['orderForm']->sum('display_fee_amount'))}}</td>
                            <td>{{$bill['finishedAmount']}}</td>
                            <td>{{$bill['notFinishedAmount']}}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-xs-12 padding-clear">
                    <ul id="myTab" class="nav nav-tabs notice-bar notice-bar">
                        <li class="active"><a href="#table1" data-toggle="tab">订单对账单</a>
                        </li>
                        <li><a href="#table2" data-toggle="tab">陈列对账单</a></li>
                        <li><a href="#table3" data-toggle="tab">赠品对账单</a></li>
                        <li><a href="#table4" data-toggle="tab">促销对账单</a></li>
                        <li><a href="#table5" data-toggle="tab">退货对账单</a></li>
                    </ul>
                    <div id="myTabContent" class="tab-content statement-table-wrap">
                        <div class="tab-pane fade in active" id="table1">
                            <table class="table table-bordered table-center ">
                                <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>订单编号</th>
                                    <th>商品名称</th>
                                    <th>数量</th>
                                    <th>金额</th>
                                    <th>优惠券</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($bill['orderForm'] as $order)
                                    @if($order->orderGoods->where('type',0)->count())
                                        @foreach($order->orderGoods->where('type',0) as $orderGoods)
                                            <tr>
                                                @if($orderGoods == $order->orderGoods->where('type',0)->first())
                                                    <td rowspan="{{$order->orderGoods->where('type',0)->count()}}">{{$order->created_at}}</td>
                                                    <td rowspan="{{$order->orderGoods->where('type',0)->count()}}">{{$order->order_id . "(" . $order->order->status_name . ")"}}</td>
                                                @endif
                                                <td>{{$orderGoods->goods->name ?? ''}}</td>
                                                <td>{{$orderGoods->num ?? ''}} {{$orderGoods->pieces_name ?? ''}}</td>
                                                <td>{{$orderGoods->amount ?? ''}}</td>
                                                @if($orderGoods == $order->orderGoods->first())
                                                    <td rowspan="{{$order->orderGoods->where('type',0)->count()}}">{{bcsub($order->how_much_discount,$order->display_fee_amount,2)}}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="table2">
                            <table class="table table-bordered table-center ">
                                <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>订单编号</th>
                                    <th>月份</th>
                                    <th colspan="2">陈列实发项</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($bill['orderForm'] as $order)
                                    @if(count($order->mortgageGoods))
                                        @foreach($order->mortgageGoods as $mortgageGoods)
                                            <tr>
                                                @if($mortgageGoods == $order->mortgageGoods->first())
                                                    <td rowspan="{{count($order->mortgageGoods)}}">{{$order->created_at}}
                                                    </td>
                                                    <td rowspan="{{count($order->mortgageGoods)}}">{{$order->order_id . "(" . $order->order->status_name . ")"}}</td>
                                                @endif
                                                <td>{{$mortgageGoods->pivot->month ?? ''}}</td>
                                                <td>{{$mortgageGoods->goods_name ?? ''}}</td>
                                                <td>{{intval($mortgageGoods->pivot->used)}}{{$mortgageGoods->pieces_name}}</td>
                                            </tr>
                                        @endforeach
                                    @elseif(count($order->displayFees))
                                        @foreach($order->displayFees as $displayFees)
                                            <tr>
                                                @if($displayFees == $order->displayFees->first())
                                                    <td rowspan="{{count($order->displayFees)}}">{{$order->created_at}}</td>
                                                    <td rowspan="{{count($order->displayFees)}}">{{$order->order_id . "(" . $order->order->status_name . ")"}}</td>
                                                @endif
                                                <td>{{$displayFees->month ?? ''}}</td>
                                                <td>现金</td>
                                                <td>￥{{$displayFees->used}}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="table3">
                            <table class="table table-bordered table-center ">
                                <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>订单号</th>
                                    <th colspan="2">赠品内容</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($bill['orderForm'] as $order)
                                    @if(count($order->gifts))
                                        @foreach($order->gifts as $gifts)
                                            <tr>
                                                @if($gifts == $order->gifts->first())
                                                    <td rowspan="{{count($order->gifts)}}">{{$order->created_at}}
                                                    </td>
                                                    <td rowspan="{{count($order->gifts)}}">{{$order->order_id . "(" . $order->order->status_name . ")"}}</td>
                                                @endif
                                                <td>{{$gifts->name ?? ''}}</td>
                                                <td>{{$gifts->pivot->num . cons()->valueLang('goods.pieces',$gifts->pivot->pieces)}}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="table4">
                            <table class="table table-bordered table-center ">
                                <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>订单号</th>
                                    <th>促销名称</th>
                                    <th colspan="2">返利项</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($bill['orderForm'] as $order)
                                    @if($order->promo)
                                        @if(in_array($order->promo->type,[cons('promo.type.goods-goods'),cons('promo.type.money-goods')]))
                                            @foreach($order->promo->rebate as $rebate)
                                                <tr>
                                                    @if($rebate == $order->promo->rebate->first())
                                                        <td rowspan="{{count($order->promo->rebate)}}">{{$order->created_at}}
                                                        </td>
                                                        <td rowspan="{{count($order->promo->rebate)}}">{{$order->order_id . "(" . $order->order->status_name . ")"}}</td>
                                                        <td rowspan="{{count($order->promo->rebate)}}">{{$order->promo->name}}</td>
                                                    @endif
                                                    <td>{{$rebate->goods->name ?? ''}}</td>
                                                    <td>{{$rebate->quantity . cons()->valueLang('goods.pieces',$rebate->unit)}}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td>{{$order->created_at}}</td>
                                                <td>{{$order->order_id . "(" . $order->order->status_name . ")"}}</td>
                                                <td>{{$order->promo->name}}</td>
                                                <td colspan="2">
                                                    <span>
                                                        {{'('.($order->promo->type == cons('promo.type.custom') ? '自定义' : '现金').')'}}
                                                    </span>
                                                    @if(in_array($order->promo->type,[cons('promo.type.goods-money'),cons('promo.type.money-money')]))
                                                        {{$order->promo->rebate[0]->money}} 元
                                                    @else
                                                        {{$order->promo->rebate[0]->custom}}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="table5">
                            <table class="table table-bordered table-center ">
                                <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>订单编号</th>
                                    <th>商品名称</th>
                                    <th>数量</th>
                                    <th>金额</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($bill['orderReturn'] as $order)
                                    @foreach($order->orderGoods as $orderGoods)
                                        <tr>
                                            @if($orderGoods == $order->orderGoods->first())
                                                <td rowspan="{{count($order->orderGoods)}}">{{$order->created_at}}</td>
                                                <td rowspan="{{count($order->orderGoods)}}">{{$order->id}}</td>
                                            @endif
                                            <td>{{$orderGoods->goods->name ?? ''}}</td>
                                            <td>{{$orderGoods->num ?? ''}} {{$orderGoods->pieces_name ?? ''}}</td>
                                            <td>{{$orderGoods->amount ?? ''}}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="6" class="text-right">
                                        <div class="money-item">
                                            <label>总计金额：</label>{{sprintf("%.2f",$bill['orderReturn']->sum('amount'))}}
                                        </div>
                                    </td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@stop
@section('js')
    @parent
    <script>
        formSubmitByGet();
    </script>
@stop
