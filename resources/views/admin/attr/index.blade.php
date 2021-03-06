@extends('admin.master')
@include('includes.treetable')

@section('subtitle' , '用户管理')

@section('right-container')
    <div id="container">
        <div class="row">
            <form action="{{ url('admin/attr') }}" method="get" class="categories" autocomplete="off">
                <div class="form-group">
                    <div class="col-sm-2">
                        <select name="cate_level_1" class="address-province form-control">
                            <option selected="selected" value="">请选择</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <select name="cate_level_2" class="address-city form-control">
                            <option selected="selected" value="">请选择</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <select name="cate_level_3" class="address-district form-control">
                            <option selected="selected" value="">请选择</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary">搜索</button></span>
                    </div>
                </div>
            </form>
        </div>
        <table id="attr" class="table">
            @foreach( $attrs as $id => $attr )
                <tr data-tt-id="{{ $id  }}" data-tt-parent-id="{{  $attrs->data('pid')  }}">
                    <td>{{  $attr  }}</td>
                    <td class=" btn-group-xs">
                        <a class="btn btn-primary" href="{{ url('admin/attr/' . $id . '/edit') }}">
                            <i class="fa fa-edit"></i> 编辑
                        </a>
                        <a type="button" class="btn btn-danger ajax" data-method="delete"
                           data-url="{{  url('admin/attr/' . $attrs->data('id'))  }}">
                            <i class="fa fa-trash-o"></i> 删除
                        </a>

                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@stop

@section('js')
    @parent
    <script>
        $(function () {
            $('#attr').treetable({expandable: true});
            getCategory(site.api('categories'));
            getAllCategory(site.api('categories'), '{{ $search['cate_level_1'] }}', '{{ isset($search['cate_level_2']) ? $search['cate_level_2'] : 0 }}', '{{ isset($search['cate_level_3']) ? $search['cate_level_3'] : 0 }}');
        });
    </script>
@stop