define([ "jquery"], 
function( $ ){

	var Utils = {

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

	    saveFields : function( data ){
	        return $.ajax({
	                data: JSON.stringify({ "fields": data }) ,
	                contentType:'application/json',
	                type:'POST',
	                url: _unbxdBaseurl + 'unbxd/config/fields?site='+site
	          })
	    }

	};

	return Utils;
});