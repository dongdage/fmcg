@extends('index.master')

@section('subtitle', '首页')

@section('container')
    <div class="container wholesalers-index index">
        <div class="row">
            <div class="col-sm-8 left-store-logo">
                <div id="myCarousel" class="carousel slide banner banner-slide">
                    <ol class="carousel-indicators">
                        @for($index = 0; $index < count($shop->images); $index++)
                            <li data-target="#myCarousel" data-slide-to="{{ $index }}"
                                class="{{ $index == 0 ? 'active' : '' }}">
                        @endfor
                    </ol>
                    <div class="carousel-inner banner">
                        @foreach($shop->images as $key=>$image)
                            <div class="item {{ $key == 0 ? 'active' : '' }}">
                                <img src="{{ $image->url }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-sm-4 store">
                <div class="store-panel">
                    <img class="avatar" src="{{ $shop->logo_url }}">
                    <ul class="store-msg">
                        <li>店家姓名:{{ $shop->user->nickname }}</li>
                        <li>联系人:{{ $shop->contact_person }}</li>
                        <li>最低配送额:￥{{ $shop->min_money }}</li>
                    </ul>
                </div>
                <div class="address-panel">
                    <ul>
                        <i class="icon icon-tel"></i>
                        <li class="address-panel-item">
                            <span class="panel-name">联系方式</span>
                            <span>{{ $shop->contact_info }}</span>
                        </li>
                    </ul>
                    <ul>
                        <i class="icon icon-seller"></i>
                        <li class="address-panel-item">
                            <span class="panel-name">店家地址</span>
                            <span>{{ $shop->address }}</span>
                        </li>
                    </ul>
                    <ul>
                        <i class="icon icon-address"></i>
                        <li class="address-panel-item">
                            <span class="panel-name">商品配送区域</span>

                            <div class="address-list">
                                @foreach ($shop->deliveryArea as $area)
                                    <span>{{ $area->area_name .$area->address }}</span>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 ">
                <div class="tab-title clearfix">
                    <p class="sequence">
                        <a class="{{ $sort == 'all' || !$sort  ? 'active' : '' }}"
                           href="{{ url('shop/' . $shop->id) }}">全部</a>
                        <a class="{{ $sort == 'hot' ? 'active' : '' }}"
                           href="{{ url('shop/'.$shop->id . '/hot') }}">热销</a>
                        <a class="{{ $sort == 'new' ? 'active' : '' }}"
                           href="{{url('shop/'.$shop->id .  '/new') }}">最新</a>
                        <a class="{{ $sort == 'promotion' ? 'active' : '' }}"
                           href="{{url('shop/'.$shop->id . '/promotion') }}">促销</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="row list-penal">
            @foreach($goods as $item)
                <div class="col-sm-3 commodity">
                    <div class="img-wrap">
                        <a href="{{ url($url . '/' . $item->id) }}">
                            <img class="commodity-img" src="{{  $item->image_url }}">
                        </a>
                        <span class="prompt @if($item->is_out) 'lack'  @elseif($item->is_promotion) 'promotions' @elseif($item->is_new) 'new-listing' @endif  new-listing"></span>

                    </div>
                    <p class="commodity-name"><a href="{{ url($url . '/' . $item->id) }}">{{ $item->name }}</a></p>

                    <p class="sell-panel">
                        <span class="money">￥{{ $item->price }}</span>
                        <span class="sales pull-right">销量 : {{ $item->sales_volume }}</span>
                    </p>
                </div>
            @endforeach
            <div class="col-xs-12 text-right">
                {{ $goods->render() }}
            </div>
        </div>
    </div>
@stop
@section('js')
    @parent
    <script type="text/javascript">
        $(document).ready(function () {
            $('.carousel').carousel({
                interval: 2000
            })
        });
    </script>
@stop