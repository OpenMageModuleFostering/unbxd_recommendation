define([ "jquery", "rv!templates/widgets"], 
function( $, Template ){
	
	var Widgets = {

			loadWidgetsTab : function(){
		
				var ractiveWidgets = new Ractive({
				            el: 'innerContainr',
				            template: Template
				         });

				ractiveWidgets.on({
				  showCMSHelp:function(){
				      $('#cmsHelp').toggle();
				  }
				});

				$(".magento-tooltip").popover({ trigger:'hover' });
				$(".table-widget").popover({ trigger:'click' });
			}

	};

	return Widgets;
});

