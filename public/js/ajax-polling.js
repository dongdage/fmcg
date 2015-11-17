$(function () {//间隔30s自动加载一次
        //前台轮询结果处理
        function getPushData() {//获取消息
            var targetUrl = site.baseUrl + "/order-buy/order-polling";
            $.get(targetUrl, function (json) {//利用ajax返回json的方式
                var div = $('.msg-channel'), check = div.find('.check');
                var uri = '';
                if (json.data != undefined) {
                    switch (json.type) {
                        case 'user':
                            uri = '{{ url("order-buy") }}';
                            break;
                        case 'seller':
                            uri = '{{ url("order-sell") }}';
                            break;
                        default :
                            uri = '{{ url("personal/withdraw") }}';
                            break;
                    }
                    check.attr('href', uri);
                    check.html(json.data);
                    div.fadeIn();
                    setTimeout(function () {
                        div.fadeOut(3000);
                    }, 6000);
                }
            });
        }

        getPushData(); //首次立即加载
        window.setInterval(getPushData, 500000); //循环执行！！
    }
);