define([ 
	"jquery", 
	'app/config', 
	'app/utils',
	'rv!templates/search/api',
	'rv!partials/switch-on',
	'rv!partials/switch-off'], 
function( 
	$, 
	Config , 
	Utils,
	APISearchTemplate,
	SwitchonTemplate,
	SwitchoffTemplate){

	var APISearch = {

		runConstructor:function( searchConfig ){
			if(this.initialized)
				return;

			if(searchConfig && searchConfig.search_mod_status ){
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
						                template: APISearchTemplate,
						                partials: { 
						                	switchon:SwitchonTemplate,
						                	switchoff:SwitchoffTemplate
						                	 },
						                data:{
						                	search_mod_status:this.search_mod_status
						                }
						             });
                

			    this.ractiveSearch.on({
			    	toggleAPIIntegration:function(){
			    		this.set('search_mod_status', !this.get('search_mod_status'));
			    	},

			    	goBack:function(){
			    		self.parentView.load();
			    	}
			    });

			    this.ractiveSearch.observe('search_mod_status', function(newValue, oldValue, property) {
			    	if( typeof newValue !== 'undefined' && oldValue !== newValue ){
			    		var data = {};
			    		data[property] = newValue;
			    		Utils.saveConfigs( data )
			    		.then(function( data ){
			    			if( data && data.success){
			    				self.search_mod_status = newValue;
			    				self.parentView.setData('search_mod_status', newValue);
			    			}else{
			    				self.ractiveSearch.set({
			    					search_mod_status:oldValue,
			    					errorMsg:data.search_mod_status
			    				});
			    			}
			    			
			    		});

		    		}
			    });

    	},

    	eventBindings:function(){
   
    	},

    	loadConfigs:function(){
    		var self = this;

    		Utils.getConfigData('search')
    		.then(function( data ){
    			if(data.success && data.success===true){
    				var config = self.setData( data.config );
					self.ractiveSearch.set( data );
    			}
    		},function( error ){

    		});
    	},

    	setData:function( data ){
    		for(key in data){
    			if( data[key] === "false" || data[key] === ""){
    				data[key] = false;
    			}else if( data[key] === "true" || data[key] === "1" ){
    				data[key] = true;
    			}
    		}

    		return data;
    	},

    	//to clear error msgs
    	reset:function(){
    		this.ractiveSearch.set('errorMsg', null)
    	}

	};

	return APISearch;

});
