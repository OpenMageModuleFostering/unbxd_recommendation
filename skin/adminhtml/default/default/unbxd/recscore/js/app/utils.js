define([ "jquery", "app/config"], 
function( $, Config ){

	var Utils = {

		endpoints:{
			sites:'/config/site',
			keys:'/config/keys?site=',
			fields:'/config/fields?site=',
			productsync:'/config/productsync?site=',
			analytics : "/config/analyticsimpression?site=",
			globalConfigs:'/config/global?site=',
			supportMail:'/config/supportmail?site=',
			filters:'/catalog/filter?site=',
			feedStatus : "/config/feedstatus?site=",
			states:"/config/state?site="
		},

		addAuth:function( url ){
			if(url.indexOf('?') > -1 )
				return url + "&auth=" + Config.authValue;
			else
				return url + "?auth=" + Config.authValue;
		},

		activateLink : function( step ){
           _unbxdObject.activeStep = {};
           _unbxdObject.activeStep[step] = true;
           ractiveParent.update();
        },

		configureSelect : function(){
	      var chosenObj = $('select[name="unbxd-select"]').chosen();
	          chosenObj.trigger('chosen:updated');
	    },
	    //re render all chosen selects
	    updateAllSelect : function(){
	       $('select').trigger('chosen:updated');
	    },

	    getStates:function(){
	    	var url = _unbxdBaseurl + this.endpoints.states + Config.site;
	    	return this.ajaxGet( url );
	    },

	    getSites:function(){
	    	var url = _unbxdBaseurl + this.endpoints.sites;
	    	return this.ajaxGet( url );
	    },

	    authenticateKeys:function( data ){
	    	var url =  _unbxdBaseurl + this.endpoints.keys + Config.site;
	    	return this.ajaxPost(url, data );
	    },

	    getKeys:function(){
	    	var url =  _unbxdBaseurl + this.endpoints.keys + Config.site;
	    	return this.ajaxGet( url );
	    },

	    saveFields : function( data, TYPE ){
	        var url =  _unbxdBaseurl + this.endpoints.fields + Config.site;
			return this.ajaxPost( url, data, TYPE);
	    },

	    getFields:function(){
	    	var url =  _unbxdBaseurl + this.endpoints.fields + Config.site;
	        return this.ajaxGet( url );
	    },

	    productSync:function(){
	    	var url = _unbxdBaseurl + this.endpoints.productsync + Config.site;
	    	return this.ajaxGet( url);
	    },

	    saveConfigs:function( data ){
	    	var url = _unbxdBaseurl + this.endpoints.globalConfigs +  Config.site;
	    	return this.ajaxPost( url, data );
	    },

	    getConfigData:function( key ){
	    	var url = _unbxdBaseurl  + this.endpoints.globalConfigs + Config.site+'&key='+key;
	    	return this.ajaxGet( url );
	    },

	    sendMail:function(data){
	    	var url = _unbxdBaseurl  + this.endpoints.supportMail + Config.site;
	    	return this.ajaxPost( url, data);
	    },

	    postData:function(data){
	    	var url = _unbxdBaseurl  + this.endpoints.filters + Config.site;
	    	return this.ajaxPost( url, data );
	    },

	    validateEmail:function(email) {
		    var re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		    return re.test(email);
		},

		feedStatus:function(){
			var url = _unbxdBaseurl + this.endpoints.feedStatus + Config.site;
			return this.ajaxGet(url);
		},

		getAnalytics:function(){
			var url =  _unbxdBaseurl + this.endpoints.analytics + Config.site;
			return this.ajaxGet(url);
		},

		getFilters:function( url ){
			var url =  _unbxdBaseurl + this.endpoints.filters + Config.site
			return this.ajaxGet( url );
		},

		ajaxGet:function( url ){
			url = this.addAuth(url);

			return $.ajax({
	                contentType:'application/json',
	                type:'GET',
	                url: url
	          });
		},

		ajaxPost:function( url, data, TYPE ){
			url = this.addAuth(url);

			for(key in data){
				if(data[key] === true)
					data[key] = "true";
				else if( data[key] === false )
					data[key] = "false";
			};
			return $.ajax({
	                contentType:'application/json',
	                type: TYPE || 'POST',
	                url: url,
	                data:JSON.stringify( data )
	          });
		}


	};

	return Utils;
});