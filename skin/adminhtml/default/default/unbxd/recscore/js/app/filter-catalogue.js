define([ "jquery", 
        "app/utils", 
        "app/config", 
        "rv!partials/filters"], 
function( $, 
          Utils, 
          Config, 
          FiltersTemplate){

	var FiltersCatalog = {
     
      runCunstructor:function( magentoFileds ){
         if(this.initialized === true)
          return;


         magentoFileds.unshift({field_name:"Field", field_type: "string", disabled:"unbxd-hide"});
         this.magentoFileds = magentoFileds;
         this.nomoreFilters = false;
         this.filterSaveDisabled = true;
         this.initialized = true;
         this.filterShown = false;
         this.include_out_of_stock = false;
      },

		  load : function( magentoFileds ){
            this.runCunstructor( magentoFileds );
            if(this.filters)
              this.show();
            else
              this.loadFilters();
      },

      show : function(){
        var self = this;

        this.ractiveCatlogueFilter = new Ractive({
             el: 'unbxdFilters',
             template: FiltersTemplate,
             data: {
               filters : this.filters,
               magentoFileds : this.magentoFileds,
               nomoreFilters : this.nomoreFilters,
               filterSaveDisabled : this.filterSaveDisabled,
               saveSuccess:false,
               filterShown:this.filterShown,
               include_out_of_stock:this.include_out_of_stock
             } 
        });
 
        $(".magento-tooltip").popover({ trigger:'hover' });

         this.ractiveCatlogueFilter.on({
           
           showFilters:function(){
              this.set('filterShown', !this.get('filterShown'));
              if(this.get('filterShown')){
                $(".filter_toggle").slideToggle();
              }
              else{
                $(".filter_toggle").slideToggle();
              }
           },


           addFilter:function(){
                this.get( 'filters').push({
                  field_name : 'Field',
                  field_type:'',
                  rule: '',
                  value:'',
                  from:'',
                  to:''
                });

            },

            removeFilter:function( event ){
               var index = this.get('filters').indexOf(event.context);
               var obj = this.get('filters').splice(index, 1);
               if( obj && obj[0].field_name !== "Field" )
                 this.set('filterSaveDisabled', false);
            },

            saveFilters:this.saveFilters,

            valueChange:function( event ){
              var index = this.get('filters').indexOf(event.context),
                  filters = this.get("filters");

                  filters[index].noValue = false;
                  this.set("filters",filters); 
                  this.set('filterSaveDisabled', false);
            },

            toValueChange:function( event ){
              if( !self.isNumber(event) )
                return false;
              var index = this.get('filters').indexOf(event.context),
                  filters = this.get("filters");

                  filters[index].noTo = false;
                  this.set("filters",filters); 
                  this.set('filterSaveDisabled', false);
            },

            fromValueChange:function( event ){
              if( !self.isNumber(event) )
                return false;
              var index = this.get('filters').indexOf(event.context),
                  filters = this.get("filters");

                  filters[index].noFrom = false;
                  this.set("filters",filters); 
                  this.set('filterSaveDisabled', false);
            }

         });
        
        this.ractiveCatlogueFilter.observe('filters.*.field_name', function(newValue, oldValue, keypath, s) {

          var filters = this.get('filters'),
              magentoFileds = this.get("magentoFileds");

          if( filters.length == 0)
              return;

          if(typeof oldValue !=='undefined' &&  newValue && newValue!= 'Field' && oldValue !== newValue ){
            this.set('filterSaveDisabled', false);
            filters[s].noField = false;
            this.set('filters', filters);
          }
            

           if(filters.length === 3 ){
             this.set('nomoreFilters', true);
           }else{
             this.set('nomoreFilters', false);
           }


 
           for(var k=0; k<magentoFileds.length; k++){
            if(magentoFileds[k].field_name === newValue){
              for(var j=0; j<filters.length; j++){
                if(filters[j].field_name === newValue)
                  filters[j].field_type = magentoFileds[k].field_type;
              }
              break;
            }
           }
           this.set('filters', filters);
           Utils.updateAllSelect();
            
        });

        this.ractiveCatlogueFilter.observe('filterSaveDisabled', function(newValue, oldValue, keypath ){
            if( this.get('filterSaveDisabled') === false ){
              this.set('saveSuccess', false);
            }
        });

        this.ractiveCatlogueFilter.observe('include_out_of_stock', function(newValue, oldValue, property ){
          if(typeof newValue === 'undefined' || newValue === oldValue)
              return;

          var data = {};
           data[property] = newValue;
           Utils.saveConfigs( data )
           .then(function(){

           },function(){

           });

        });


      },

      saveFilters:function(){
         var filters  = this.get('filters'),
              isValid = false,
              data    = {},
              self    = this;

         for(var k=0; k<filters.length; k++){

            if( filters[k].field_type === 'string' ){
              isValid = true;
              if( filters[k].field_name =='Field' ){
                 filters[k].noField = true;
                 isValid = false;
              }
              if( filters[k].value=='' ){
                filters[k].noValue = true;
                isValid = false;
              }
            }

            if( filters[k].field_type === 'number' ){
                isValid = true;
                if( filters[k].field_name =='Field' ){
                 filters[k].noField = true;
                 isValid = false;
                }
                
                if(!filters[k].from || filters[k].from > filters[k].to ){
                  filters[k].noFrom=true;
                  isValid = false;
                }
                
               if(!filters[k].to ){
                  filters[k].noTo=true;
                  isValid = false;
               }
                
            }
            
         };


         if(!isValid && filters.length !== 0){
            this.set('filters', filters);
            return;
         }

         for(var k=0; k<filters.length; k++){
            var key = filters[k].field_name
            if( filters[k].field_type === 'string')
              data[ key ] = filters[k].value;
            else
              data[ key ] = filters[k].from +"||"+filters[k].to
         }

         Utils.postData(data).then(
          function( data ){
            self.set('filterSaveDisabled', true);
            self.set('saveSuccess', true);
         },function( error ){

         });
          


      },

      loadFilters:function(){
            var self = this;
            this.filters = [];

        Utils.getFilters().then(
           function( data ){
              if( data && data.filters ){
                 data = data.filters;
                 if(data.length === 0)
                  return;

                 for( var key in data ){
                     var obj = {
                      field_name:key,
                      value:data[key],
                      to:'',
                      from:''
                     };
                     if(data[key].indexOf('||') > 0){
                       var values = data[key].split('||');
                       obj['field_type']='number';
                       obj['from']  = values[0];
                       obj['to']    = values[1];
                     }else{
                       obj['field_type']='string';
                     }
                    

                    self.filters.push(obj);
                 }
              };
        }, function( error ){

        }).always(function(){
            self.show();
        });

        Utils.getConfigData('include_out_of_stock')
        .then(function( data ){
          if(data.success && data.success === true){
            if( data.config.include_out_of_stock === "1" || data.config.include_out_of_stock === 'true' )
              self.setData('include_out_of_stock',  true );
            else
              self.setData('include_out_of_stock',  false );
          }
            
        },function( error ){

        });

      },

      setData:function(key, val){
         if(this.ractiveCatlogueFilter)
          this.ractiveCatlogueFilter.set(key,val);
         else
          this[key] = val;
      },

      isNumber:function( evt ){
         if(evt.original.shiftKey)
          return false;
         var charCode = (evt.which) ? evt.which : event.keyCode
          if (charCode > 31 && (charCode < 48 || charCode > 57))
              return false;
          return true;
      }

	};


	return FiltersCatalog;
});