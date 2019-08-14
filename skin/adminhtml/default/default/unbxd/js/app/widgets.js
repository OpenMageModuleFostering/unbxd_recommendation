define([ "jquery"], 
function( $ ){

	var Widgets = {

			loadWidgetsTab : function(){
		
				var ractiveWidgets = new Ractive({
				            el: 'innerContainr',
				            template: '#tab4Template'
				         });

				ractiveWidgets.on({
				  showCMSHelp:function(){
				      $('#cmsHelp').toggle();
				  }
				});

				$(".magento-tooltip").popover({ trigger:'hover' });
			}

	};

	return Widgets;
});