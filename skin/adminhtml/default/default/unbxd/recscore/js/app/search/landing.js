define([ 
	"jquery", 
	'app/config', 
	'app/utils',
	'app/search/hosted',
	'app/search/api',
	'rv!templates/search/landing'], 
function( 
	$, 
	Config , 
	Utils,
	HostedSearch,
	APISearch,
	SearchTemplate){

	var SearchLanding = {

		runCunstructor:function(){

			if(this.data){
				for(key in this.data){
					if(this.data[key] ==='false' || this.data[key] ==='')
						this.data[key] = false;
					else if (this.data[key] ==='1' || this.data[key] ==='true')
						this.data[key] = true;

					this[key] = this.data[key];
				}

			}else{
				this.search_hosted_status = false;
				this.search_mod_status = false;
				this.search_hosted_int_status = false;
				this.search_hosted_redirect_url = false;
			}
			this.show();
			this.initialized = true;
		},
		
		load : function(){
			if(typeof this.initialized === 'undefined')
				this.loadSearchStatus();
			else
				this.show();
    	},

    	setDefaults:function( data ){
   	
    	},

    	show:function( ){

			    this.ractiveSearch = new Ractive({
						                el: 'innerContainr',
						                template: SearchTemplate,
						                data:{
											search_hosted_status 	: this.search_hosted_status,
											search_mod_status 		: this.search_mod_status,
											search_hosted_int_status : this.search_hosted_int_status,
											search_hosted_redirect_url : this.search_hosted_redirect_url
						                }
						             });
                

			    this.ractiveSearch.on({
			    	showHostedSearch:function(){
			    		HostedSearch.load( this.data );
			    	},

			    	showAPISearch:function(){
			    		APISearch.load( this.data );
			    	},
			    });

			    // this.ractiveSearch.observe('*', function(newValue, oldValue, property) {

			    // });

    	},

    	eventBindings:function(){
   
    	},

    	loadSearchStatus:function(){
    		var self = this;
    		Utils.getConfigData('search')
	    		.then(function( data ){
	    			if(data.success && data.success === true){
	    			   	self.data = data.config;
	    			}
	    		},function( error ){

	    		}).always(function(){
	    			self.runCunstructor();
	    		});
    	},
    	//method child views can use to set data
    	setData:function(key, value){
    		this[key] = value;
    	}

	};

	return SearchLanding;

});
