var Mp = {
    ajax_callback : function(rv, success) {
        /*try{
            var $json = $.parseJSON(rv);
        }catch(e){
            alert(rv);
        }*/
        //if($json.type !== 1 && $json.type !== 2)
        //alert($json.msg);
        // alert(rv.msg);
        if(typeof success == "function") {
            success(rv);
        }
    },
	
	show_model : function(msg) {
        //e.preventDefault();
       
        var title = arguments[1] ? arguments[1] : '提示';
        var refresh = arguments[2] ? arguments[2] : true; 
        var url     = arguments[3] ? arguments : false;
        var closeTime = arguments[4] ? arguments[3] : 1000;
        
        if(!refresh){
        	$("#xModalTitle").html(title);
            $("#msg").html(msg);
            //$('#xModal').modal('show');
            $("#xModal").fadeIn("slow");
        }else{
            $("#refresh-msg").html(msg);
            //$('#xModalRefresh').modal('show');
            $("#xModalRefresh").fadeIn("slow");
        }
    
        //定时关闭功能
        if( closeTime ){
	        setTimeout(function(){
				if(!refresh){
		            //$('#xModal').modal('hide');
		            $("#xModal").fadeOut("slow");
		        }else{
		            //$('#xModalRefresh').modal('hide');
		            $("#xModalRefresh").fadeOut("slow");
		            if ( url ) {
		            	location.href = url;
		            }
		        }
	        }, closeTime);
        }
    },
    file_upload : function(obj, url, success) {
        if(typeof obj !== 'object') return;
        var formData = new FormData();
        formData.append('file', obj);
        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: formData,
            processData: false,
            contentType: false
        }).done(function(res) {
            if(typeof success == "function") {
                success(res);
            }
        }).fail(function(res) {
            if(typeof success == "function") {
                success(res);
            }
        });
    }
}
