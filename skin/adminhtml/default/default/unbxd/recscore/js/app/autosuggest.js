define([ 
	"jquery", 
	"colpick",
	'app/config', 
	'rv!templates/auto-suggest', 
	'app/utils', 
	'rv!partials/preview-modal',
	'rv!partials/switch-on',
	'rv!partials/switch-off',
	'bootstrap-modal'], 
function( $, 
	 	 ColorPicker,
		 Config , 
		 Template, 
		 Utils, 
		 ModalTemplate,
		 SwitchonTemplate,
		 SwitchoffTemplate){

	var AutoSuggest = {

		loadAutosuggest : function(){

			  if( typeof this.autosuggestData === "undefined" ){
			  	    var self = this;
			  	    Utils.getConfigData( 'autosuggest')
			  		.then(
			  		function( data ){
			  			if(data.success === true){
			  				self.autosuggestData = self.setDefaults( data.config );
			  				self.showAutosuggest();
			  			}
	
			  		},
			  		function( error ){
			
			  		})
			  }else{
			  	 this.showAutosuggest();
			  }
    	},

    	setDefaults:function( data ){
           for(key in data){
           	   if(data[key] === "" || data[key] === "false"){
           	   	   data[key] = false;
           	   }else if( key === "autosuggest_skin" ){
           	   	   if( !Config.colorMap[ data[key] ] ){
           	   	   	  data['skinBackground'] = data[key];
           	   	   	  data['customColor'] = true;
           	   	   }else{
           	   	   	  data[key] = Config.colorMap[ data[key] ];
           	   	   	  data['customColor'] = false;
           	   	   }

           	   }else if( key === "autosuggest_template" ){
           	   	   data[key] = Config.templateMap[ data[key] ];
           	   }  	
           }
           var obj = $.extend( Config.autoSuggestDefaults, data);
           obj['config'] = Config;
           return obj;
    	},

    	showAutosuggest:function( ){

			    this.ractiveAutosuggest = new Ractive({
						                el: 'innerContainr',
						                template: Template,
						                partials: { 
						                		modal: ModalTemplate,
						                		switchon:SwitchonTemplate,
						                		switchoff:SwitchoffTemplate },
						                data: this.autosuggestData,
						                computed: {
										    hideSuggestions: function () { return this.get( 'autosuggest_template' ) === 4  }
										}
						             });
                

			    this.ractiveAutosuggest.on({

			    	autosuggestToggle:function(){
			    		this.set('autosuggest_status', !this.get("autosuggest_status"));
			    	},

			    	selectSkin:function( event, color ){
			    		this.set("autosuggest_skin", color);
			    		this.set('customColor', false);
			    	},

			    	selectTemplate:function( event, template ){
			    		this.set("autosuggest_template", template);
			    	},

			    	noOfSuggestionsUp:function(){
			    		var noOfSuggestions = parseInt(this.get("autosuggest_max_suggestion")) + 1;
			    		    noOfSuggestions = noOfSuggestions > Config.maxSuggestions ? Config.maxSuggestions : noOfSuggestions; 
			    		this.set("autosuggest_max_suggestion", noOfSuggestions );
			    	},

			    	noOfSuggestionsDown:function(){
			    		var noOfSuggestions = parseInt(this.get("autosuggest_max_suggestion")) - 1;
			    		 	noOfSuggestions = noOfSuggestions > 0 ? noOfSuggestions : 0; 
			    		this.set("autosuggest_max_suggestion",  noOfSuggestions );
			    	},

			    	topqueriesToggle:function(){
			    		this.set('autosuggest_top_queries_status', !this.get("autosuggest_top_queries_status"));
			    	},

			    	keywordsToggle:function(){
			    		this.set('autosuggest_keyword_status', !this.get("autosuggest_keyword_status"));
			    	},

			    	searchscopeToggle:function(){
			    		this.set('autosuggest_search_scope_status', !this.get("autosuggest_search_scope_status"));
			    	},

			    	noOfPopularProductsUp:function(){
			    		var noOfPopularProducts = parseInt(this.get("autosuggest_max_products")) + 1;
			    		    noOfPopularProducts = noOfPopularProducts > Config.maxPopularProducts ? Config.maxPopularProducts : noOfPopularProducts; 
			    		this.set("autosuggest_max_products", noOfPopularProducts );
			    	},

			    	noOfPopularProductsDown:function(){
			    		var noOfPopularProducts = parseInt(this.get("autosuggest_max_products")) - 1;
			    		 	noOfPopularProducts = noOfPopularProducts > 0 ? noOfPopularProducts : 0; 
			    		this.set("autosuggest_max_products", noOfPopularProducts );
			    	}

			    });

			    this.ractiveAutosuggest.observe('*', function(newValue, oldValue, property) {
			    	   if(typeof oldValue === "undefined" || property === "templatePreview" || property==="hideSuggestions")
			    	   		return;
			    	   console.log("newValue "+ newValue);
			    	   if( property === "autosuggest_skin")
			    	   		newValue = Config.colorMap[newValue]
			    	   else if( property === "autosuggest_template" )
			    	   		newValue = Config.templateMap[newValue]
			    	   else if( property === 'skinBackground')
			    	   		property = 'autosuggest_skin';

			    	   	

			    	   var data = {};
			    	   data[property] = newValue;
			    	   Utils.saveConfigs( data )
			    	   .then(function(){

			    	   },function(){

			    	   });

			    });

			    this.ractiveAutosuggest.observe('customColor', function(newValue, oldValue, property) {
			    	   if(typeof oldValue === "undefined" )
			    	   		return;
			    	  if( this.get('customColor') === false )
			    	  	this.set('skinBackground', '#FFF');
			    });


			    $('.magento-templates').hover(function(){
			    	$(this).find('.preview_template').show();
			    }, function(){
			    	$(this).find('.preview_template').hide();
			    });

			    this.eventBindings();
    	},

    	eventBindings:function(){
    		var self = this;
			$('#previewModal').on('show.bs.modal', function (event) {
				 
				  var button = $(event.relatedTarget) 
				  var template = button.data('template');
				  var skin  = $(event.relatedTarget).attr("data-skin")
				  self.ractiveAutosuggest.set('templatePreview', template + skin );

		
			});

			$("#colorPicker").colpick({
				layout:'hex',
				onSubmit:function( hsb,hex,rgb,el,bySetColor ){
					$("#colorPicker").colpickHide();
					self.ractiveAutosuggest.set('skinBackground', '#'+hex);
					self.ractiveAutosuggest.set('autosuggest_skin', '#'+hex);
					self.ractiveAutosuggest.set('customColor', true);
				}
			}).keyup(function(){
				$(this).colpickSetColor(this.value);
			});

			$(".magento-tooltip").popover({ trigger:'hover' });
    	}

	};

	return AutoSuggest;

});
