define([ "jquery"], 
function( $ ){

	var Analytics = {

		loadAnalyticsTab : function(){
    
			    var ractiveAnalytics = new Ractive({
			                el: 'innerContainr',
			                template: '#tab3Template',
			                data:{
			                  productClick      :  _unbxdObject.ractiveAnalytics.CLICKRANK || false,
			                  addToCartClick    :  _unbxdObject.ractiveAnalytics.ADDTOCART || false,
			                  productBuysClick  :  _unbxdObject.ractiveAnalytics.ORDER || false,
			                  _unbxdObject:_unbxdObject
			                }
			             });

			    _unbxdObject.pollingId = window.setInterval(function(){
			      $.get(_unbxdBaseurl+"unbxd/config/analyticsimpression?site="+site)
			        .then(
			        function(data){
			           if(data.success){
			              data = data.IntegrationDetails[0];
			              ractiveAnalytics.set({
			                productClick:data.CLICKRANK,
			                addToCartClick:data.ADDTOCART,
			                productBuysClick:data.ORDER
			              })
			              _unbxdObject.ractiveAnalytics = data;

			              if(data.CLICKRANK && data.ADDTOCART && data.ORDER)
			                clearInterval( _unbxdObject.pollingId );
			           }
			        },
			        function(error){
			            clearInterval( _unbxdObject.pollingId );
			        });
			    }, 3000);

			    ractiveAnalytics.on({
			        showWidgets:function( event ){
			              activateLink( "four" );
			              loadWidgetsTab();
			              clearInterval( _unbxdObject.pollingId );
			            }
			    });
    

    	}

	};

	return Analytics;

});