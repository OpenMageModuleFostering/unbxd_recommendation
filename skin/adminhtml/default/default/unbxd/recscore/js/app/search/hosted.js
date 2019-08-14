define([ 
	"jquery", 
	'app/config', 
	'app/utils',
	'rv!templates/search/hosted',
	'rv!partials/switch-on',
	'rv!partials/switch-off'], 
function( 
	$, 
	Config , 
	Utils,
	HostedTemplate,
	SwitchonTemplate,
	SwitchoffTemplate){

	var HostedSearch = {

		runConstructor:function( searchConfig ){
			if(this.initialized)
				return;

			if(searchConfig && searchConfig.search_hosted_int_status){
				for(key in searchConfig){
					this[key] = searchConfig[key];
				}	
			}else{
				this.loadConfigs();
			}

			this.initialized = true;
		},

		load : function( searchConfig ){
			this.runConstructor(searchConfig);
			this.show();
			this.reset();
			this.parentView = require('app/search/landing');
    	},

    	show:function( ){
    		var self = this;

		    this.ractiveSearch = new Ractive({
					                el: 'innerContainr',
					                template: HostedTemplate,
					                partials: { 
						                	switchon:SwitchonTemplate,
						                	switchoff:SwitchoffTemplate
						            },
					                data:{
					                	search_hosted_status : this.search_hosted_status,
					                	search_hosted_int_status : this.search_hosted_int_status,
					                	search_hosted_redirect_url : this.search_hosted_redirect_url
					                },
									 computed: {
									    hostedSearch: function () { return this.get( 'search_hosted_int_status' ) 	=== 	Config.status.ready },
									    requestedSetup:function() { return this.get('search_hosted_int_status') 	=== 	Config.status.requested}
									  }
					             });

		    this.ractiveSearch.observe('search_hosted_int_status', function(newValue, oldValue, keypath, s){

		    	if( typeof newValue !== 'undefined' && typeof oldValue !== 'undefined' && oldValue !== newValue ){
		    		var data = {};
		    		data[keypath] = newValue;
		    		Utils.saveConfigs( data );
		    	}
             });
		    

		    this.ractiveSearch.observe('search_hosted_status', function(newValue, oldValue, keypath, s){
		    	if( typeof newValue !== 'undefined' &&  typeof oldValue !== 'undefined' && oldValue !== newValue ){
		    		var data = {};
		    		data[keypath] = newValue;
		    		Utils.saveConfigs( data )
		    		.then(function( data ){
		    			if( data && data.success ){
		    				self.search_hosted_status = newValue;
		    				self.parentView.setData('search_hosted_status', newValue);
		    			}else{
			    			self.ractiveSearch.set({
			    				 search_hosted_status : oldValue,
			    				 errorMsg : data.search_hosted_status});
			    		}
			    	},function(){

			    	});
		    	}
             });

		    this.ractiveSearch.on({
			    	toggleSearchIntegration:function(){
			    		this.set('search_hosted_status', !this.get('search_hosted_status'));
			    	},

			    	goBack:function(){
			    		self.parentView.load();
			    	}
			});
		    
		    this.eventBindings();
    	},

    	eventBindings:function(){

   			$('#contactModal').on('show.bs.modal', function (event) {
			  var button = $(event.relatedTarget);
			  var modal  = $(this);
			  modal.find('#unbxdSend').attr('data-hostedsearch', false);
			  modal.find('#unbxdSend').attr('data-hostedsearch', button.data().hostedsearch);
			});
    	},

    	loadConfigs:function(){
    		var self = this;

    		Utils.getConfigData('search_host')
    		.then(function( data ){
    			if(data.success && data.success===true){
    				var config = data.config;
    				for(key in config){
    					if(config[key] === "false")
    						config[key] = false;

    					self.ractiveSearch.set( key , config[key] );
    				}
    			}
    		},function( error ){

    		});
    	},

    	//called from main
    	setData:function( obj ){
    		for( key in obj ){
    			this[key] = obj[key];
    			this.ractiveSearch.set(key, obj[key]);
    		}
    	},

    	//to clear error msgs
    	reset:function(){
    		this.ractiveSearch.set('errorMsg', null)
    	}

	};

	return HostedSearch;

});
