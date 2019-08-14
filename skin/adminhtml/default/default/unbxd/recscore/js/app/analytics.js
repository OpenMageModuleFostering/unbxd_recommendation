define([ 
	"jquery",
	"app/config",
	"app/utils",
 	'rv!templates/analytics'], 
function( $,
		 Config,
		 Utils,
		 Template ){

	var Analytics = {

		

		loadAnalyticsTab : function(){
			    var self = this;
				this.site  = Config.site;
    
			    this.ractiveAnalytics = new Ractive({
			                el: 'innerContainr',
			                template: Template,
			                data:{
			                  searchHits		:  _unbxdObject.ractiveAnalytics.SEARCHHITS || false,
			                  productClick      :  _unbxdObject.ractiveAnalytics.CLICKRANK || false,
			                  addToCartClick    :  _unbxdObject.ractiveAnalytics.ADDTOCART || false,
			                  productBuysClick  :  _unbxdObject.ractiveAnalytics.ORDER || false,
			                  _unbxdObject		:  _unbxdObject,
			                  config:Config
			                }
			             });

		
			    if( _unbxdObject.step2 )
			    	this.poll();
			    

			    this.ractiveAnalytics.on({
			        showWidgets:function( event ){
			              activateLink( "four" );
			              loadWidgetsTab();
			              clearInterval( Config.pollingId );
			            }
			    });
    

    	},

    	poll:function(){

    		var self = this;

    		Config.pollingId = window.setInterval(function(){
			     
			       Utils.getAnalytics()
			        .then(
			        function(data){
			           if(data.success){
			              var obj = data.IntegrationDetails[0];
			              self.ractiveAnalytics.set({
			                productClick 		: obj.CLICKRANK,
			                addToCartClick 		: obj.ADDTOCART,
			                productBuysClick 	: obj.ORDER,
			                searchHits 			: obj.SEARCHHITS
			              })
			              _unbxdObject.ractiveAnalytics = data;

			              if(data.CLICKRANK && data.ADDTOCART && data.ORDER)
			                clearInterval( pollingId.pollingId );
			           }
			        },
			        function(error){
			            clearInterval( Config.pollingId );
			        });
			    }, 3000);

    	}

	};

	return Analytics;

});