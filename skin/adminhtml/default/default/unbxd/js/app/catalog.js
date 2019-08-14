define([ "jquery", "app/utils"], 
function( $, Utils ){

	var Catalog = {

		loadCatalogTab : function( _unbxdObject ){

         var mappedAttributes = _unbxdObject.originalMapping, //catalog map first table
             magentoFields = [ {value:'', disabled:false}],
             customAttributes = [],
             dataTypes        = [];

         var RactiveCatalog = Ractive.extend({
                    template: '#tab2Template',
                    partials: { item: $('#rowTemplate').text() },

                    addItem: function ( description ) {
                      this.push( 'customAttributes', {
                        field_name : '',
                        datatype: '',
                        canRemove:true
                      });
                    },

                    init: function ( options ) {
                      var self = this;
                  
                      this.on({
                  
                        newTodo: function ( event ) {
                          this.addItem();
                        }

                      });
                  },


                 });
   
        var ractiveCatalog = new RactiveCatalog({
             el: 'innerContainr',
             data : _unbxdObject.catalogData ||
                    { 
                      magentoFields : [{disabled:false, value:"", test:"test"}],
                      mappedAttributes : mappedAttributes,
                      customAttributes : customAttributes,
                      enableEdit : false,
                      dataTypes : dataTypes,
                      saveMapping:'disabled-btn',
                      disableSaveCustomAttr:'disabled-btn',
                      _unbxdObject:_unbxdObject,
                      successMsg:null,
                      errorMsg:null,
                      customAttrSucessMsg:null,
                      customAttrErrorMsg: null,
                      dataErrorMsg:null,
                      dataSuccessMsg:null,
                      dataWaitMsg:null,
                      disableButton:false
                    },

        });

        $(".magento-tooltip").popover({ trigger:'hover' });


        //when user maps a magento attribute, remove it from magento fields
        ractiveCatalog.observe('mappedAttributes.*.field_name', function(newValue, oldValue, keypath, s) {
            if( !oldValue && !newValue )
             this.set('saveMapping', 'disabled-btn');
            this.set('saveMapping', null);
            var count = 0,
                mappedAttributes = this.get('mappedAttributes'),
                arr = [],
                magentoFields = this.get("magentoFields"),
                self = this;
            
            
            magentoFields.each(function(obj, i){
               if(obj.value === newValue){
                  obj.disabled = 'unbxd-hide';
               }
               if( obj.value === oldValue ){
                  obj.disabled = '';
               }
                 
            })
            this.set('magentoFields', magentoFields);
            ractiveCatalog.update();
            _unbxdObject.catalogData = ractiveCatalog.data;
            Utils.updateAllSelect();
            
        });

        ractiveCatalog.observe('customAttributes.*.field_name', function(newValue, oldValue, keypath, s) {
            if( !newValue && !oldValue )
              return;
            this.set('disableSaveCustomAttr', 'disabled-btn');
            var count = 0,
                customAttributes = this.get('customAttributes'),
                self = this,
                magentoFields = this.get("magentoFields");
           
            if(customAttributes && customAttributes.length > 0)
              this.set('disableSaveCustomAttr', null);

              magentoFields.each(function(obj, i){
               if(obj.value === newValue ){
                 obj.disabled = 'unbxd-hide';
               }
               if(obj.value === oldValue)
                 obj.disabled = '';
            })
         
            this.set('magentoFields', magentoFields);
            ractiveCatalog.update();
            _unbxdObject.catalogData = ractiveCatalog.data;
            Utils.updateAllSelect();
        });

         ractiveCatalog.observe('customAttributes.*.datatype', function(newValue, oldValue, keypath, s) {
            
            if( !newValue && !oldValue )
              return;

            var customAttributes = this.get('customAttributes');
           
            if(customAttributes && customAttributes.length > 0)
              this.set('disableSaveCustomAttr', null);
        });

         if( !_unbxdObject.catalogData ){
               var $obj = $.get( _unbxdBaseurl +  "unbxd/config/fields?site="+site);
               $obj.then(function( data ){

                var allFields    = data.fields,
                    field       = null;

                 $.each(data.fields, function(a, val){ 
                     magentoFields.push({ value:val, disabled:false} );
                 });

                 $.each(data.mappedFields, function(a, obj){ 
                     if(obj.featured_field){
                         if(!obj.field_name)
                          obj.field_name = "";
                        mappedAttributes.push(obj );
                     }else{
                        obj.disabled = "disabled";
                        customAttributes.push(obj)
                     }

                     magentoFields.each( function(item, j){
                       if(obj.field_name === item.value)
                          item.disabled = 'unbxd-hide';

                    });

                    field =  magentoFields.find(function( item ){
                        return item.value === obj.field_name
                   })

                   if(!field)
                    magentoFields.push({value:obj.field_name, disabled:'unbxd-hide'});
                      
                 });

                //data = JSON.parse(data);
          
                _unbxdObject.originalMapping = $.map( mappedAttributes, function(obj){
                                                           return $.extend({}, obj);
                                                   })
                if(customAttributes.length < 1)
                  customAttributes = [{ field_name: '', datatype: '', canRemove:true }];

                try{
                  mappedAttributes.forEach(function(obj, i){
                   obj.tooltip =  unbxdDimensions.filter(
                    function( dimension ){
                       return dimension.value === obj.featured_field;
                   })[0].helpText;
                  });
                }catch(e){}
  
                
                ractiveCatalog.set({
                  mappedAttributes: mappedAttributes,
                  magentoFields: magentoFields,
                  customAttributes:customAttributes,
                  dataTypes : data.datatype
                });
              
                _unbxdObject.catalogData = ractiveCatalog.data; 
                $("._tooltip").popover({ trigger:'hover' });
                $('select[name="unbxd-customattr"]').chosen('chosen:updated');
                $('select[name="unbxd-select"]').prop('disabled', true).trigger("chosen:updated");
                Utils.updateAllSelect();
                ractiveCatalog.update();
             })
         }
    
                     

    

      
         ractiveCatalog.on({

            showDataHelp:function(){
               $('#dataHelp').toggle();
            },

            removeRow:function ( event ) {
                var obj  =  this.get('customAttributes')[event.index.i];
                if( !obj.canRemove  ){
                    result = window.confirm("Are you sure, you want to delete "+obj.field_name );
                    if(result){
                        $.ajax({
                          url:_unbxdBaseurl + 'unbxd/config/fields?site='+site ,
                          type:'PUT',
                          data:JSON.stringify({ fields:[obj] }),
                        });
                        this.get('customAttributes').splice(event.index.i, 1);
                    }
                }else{
                  this.get('customAttributes').splice(event.index.i, 1);
                }
                  
            },

           saveMapping:function(event){
              var mapping = this.get('mappedAttributes'),
                  self = this, data = [];

              this.set( {'saveMapping':'disabled-btn',
                          'successMsg':null,
                          'errorMsg':null
                });
              mapping.each(function(obj, i){
                 if(obj.field_name)
                    data.push(obj);
              })

              Utils.saveFields( data ).then(
              function( data ){
                 if(data.success){
                     self.set({ 
                        enableEdit: false,
                        saveMapping:'disabled-btn',
                        successMsg:"Saved",
                        errorMsg:null
                    });
                 }else{
                  for(var key in data.errors)
                    self.set("errorMsg", data.errors[key])
                }
                self.set('saveMapping', null);
              },
              function( error ){

              })

           },

           saveCustomAttribute : function(){
              var customMapping = this.get('customAttributes'),
                  self = this,
                  data = [];
              customMapping.each(function(obj, i){
                 if( obj.field_name )
                  data.push(obj);
              });
       
              this.set("disableSaveCustomAttr", "disabled-btn");
              Utils.saveFields( data ).then(
                function( response ){
                  if(response.success){
                     self.set("customAttrSucessMsg", "Saved");
                     data.each(function(obj){
                       obj.canRemove = false;
                     })
                  }else{
                    self.set("customAttrErrorMsg", "Some error in saving !");
                  }
                    
                  self.set("disableSaveCustomAttr", null);
                },
                function( error ){

                });

           },

           showCustomAttribute:function(){
              Utils.updateAllSelect();
              $(".add-attr").toggle();
              $(".custom-attributes").toggle();
              this.set({ 
                   disableSaveCustomAttr : 'disabled-btn',
                   customAttrSucessMsg : null,
                   customAttrErrorMsg : null
                });
           },

           enableEdit:function(){
             this.set({ enableEdit: true,
                        saveMapping:'disabled-btn',
                        successMsg:""
              });
             $('select[name="unbxd-select"]').prop('disabled', false).trigger("chosen:updated");
           },

           disableEdit:function(){
           
              this.set({ enableEdit: false,
                        saveMapping:'disabled-btn',
                        mappedAttributes : _unbxdObject.originalMapping,
                        successMsg:null,
                        errorMsg:null
              });

              _unbxdObject.originalMapping = $.map( _unbxdObject.originalMapping, function(obj){
                                                       return $.extend({}, obj);
                                               })
        
             ractiveCatalog.update();
             _unbxdObject.catalogData = ractiveCatalog.data;
             $('select[name="unbxd-select"]').prop('disabled', true).trigger("chosen:updated");
           },

           uploadData:function(){
               this.set({
                dataWaitMsg : "Uploading data",
                dataSuccessMsg:"",
                dataErrorMsg:"",
                disableButton:true
               });
               var self = this;

               $.ajax({

                  url:_unbxdBaseurl + "unbxd/config/productsync?site=" +site,
                  type:"GET",
                  success : function(data){

                    if(data.success){
                       self.set({
                          dataSuccessMsg:unbxdMessages.uploadSuccess,
                          disableButton:false
                       });
                       _unbxdObject.step2 = true;
                    }else{
                      self.set({
                          dataErrorMsg:data.message,
                          dataSuccessMsg:"",
                          disableButton:false
                       });
                    }
                    ractiveParent.update();
                  },
                  error : function(){
                    self.set({
                      dataErrorMsg : "Some error, please try again"
                   });
                  },
                  complete:function(){
                    self.set({
                      disableButton:false,
                      dataWaitMsg:""
                   });
                  }

               });
              
           }

         });
         
    	}

	};

	return Catalog;
});