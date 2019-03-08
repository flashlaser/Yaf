//LOGIN
var Login = {
    //初始化
    init : function() {
        //添加
        jQuery("#frm_login").delegate("[action-type=login]", "click", function(){
            var $username = $(".loginuser").val();
            var $password = $(".loginpwd").val();
            var $remember = 0;
            var $refer = $(".loginrefer").val();
            $.post("/aj_f/login",
                {
                "username":$username,
                "password":$password,
                "remember":$remember
                },
                function(rv) {
                    Mp.ajax_callback(rv,function(){
                        if (rv.code == '100000') {
                            location.href=$refer;
                        } else {
                            location.href=location.href;
                        }
                    })
                }
            );
        });

    }
}

Login.init();