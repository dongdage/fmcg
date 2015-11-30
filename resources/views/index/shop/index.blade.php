@extends('index.index-master')

@section('subtitle', '首页')

@section('container')
    <div class="container dealer-index index search-page contents">
        <div class="row">
            <div class="col-sm-12">
                <div class="tab-title store-tab-title clearfix">
                    <p class="pull-left sequence">
                        <a href="{{ url('shop') }}"
                           class="{{ $sort == '' ? 'active' : '' }} control">全部</a>
                        <a href="{{ url('shop/hot') }}"
                           class="control {{ $sort == 'hot' ? 'active' : '' }}">热门</a>
                        <a href="{{ url('shop/new') }}"
                           class="control {{ $sort == 'new' ? 'active' : '' }}">最新</a>
                    </p>

                    <p class="pull-right">
                        <span>配送区域</span>
                        <select name="province_id" data-id="{{ $address['province_id'] or 0 }}"
                                class="address-province address"></select>
                        <select name="city_id" data-id="{{ $address['city_id'] or 0 }}"
                                class="address-city address"></select>
                        <select name="district_id" data-id="{{ $address['district_id'] or 0 }}"
                                class="address-district address"> </select>
                        <select name="street_id" data-id="{{ $address['street_id'] or 0 }}"
                                class="address-street address"> </select>
                    </p>
                </div>
            </div>
        </div>
        <div class="row list-penal dealer-commodity-wrap">
            @foreach($shops  as $shop)
                <div class="col-sm-3 commodity">
                    <div class="img-wrap">
                        <a href="{{ url('shop/' . $shop->id) }}"  target="_blank">
                            <img class="commodity-img" src="{{ $shop->image_url }}">
                        </a>
                    </div>
                    <div class="content-panel">
                        <p class="sell-panel">
                            <span class="sales ">最低配送额</span>
                            <span class="money pull-right">￥{{ $shop->min_money }}</span>
                        </p>

                        <p class="commodity-name">
                            <a href="{{ url('shop/' . $shop->id) }}"  target="_blank">
                                {{ $shop->name }}
                            </a>
                        </p>

                        <p class="order-count">销量 : <span>{{ $shop->sales_volume }}</span></p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="row">
            <div class="col-xs-12 text-right">
                {{--{{ $shops->render() }}--}}
            </div>
        </div>
    </div>
@stop
@section('js-lib')
    @parent
    <script type="text/javascript" src="{{ asset('js/address.js') }}"></script>
@stop

@section('js')
    @parent
    <script type="text/javascript">
        $('select.address').change(function () {
            var provinceControl = $('select[name="province_id"]'),
                    cityControl = $('select[name="city_id"]'),
                    districtControl = $('select[name="district_id"]'),
                    streetControl = $('select[name="street_id"]'),
                    address = provinceControl.val() ? '?province_id=' + provinceControl.val() : '';
            address += cityControl.val() ? '&city_id=' + cityControl.val() : '';
            address += districtControl.val() ? '&district_id=' + districtControl.val() : '';
            address += streetControl.val() ? '&street_id=' + streetControl.val() : '';
            var url = '{{ url('shop/' . $sort ) }}' + address;

            location.href = url;
        })
    </script>
@stop