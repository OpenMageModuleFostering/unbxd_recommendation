define([ "jquery", 
        "app/utils", 
        "app/config", 
        "app/filter-catalogue",
        "rv!templates/catalog",
        "rv!partials/row",
        "rv!partials/custom-attr",
        "rv!partials/catalogue"], 
function( $, 
          Utils, 
          Config, 
          Filters,
          Template,
          RowTemplate,
          CustomAttrTemplate,
          CatalogueTemplate ){

	var Catalog = {

    loadChilds:function(){
        if(this.fields)
          Filters.load(this.fields);
    },

		loadCatalogTab : function( _unbxdObject ){

         var mappedAttributes = _unbxdObject.originalMapping, //catalog map first table
             customAttributes = [],
             dataTypes        = [],
             site             = Config.site,
             unbxdMessages    = Config.unbxdMessages;

             this.magentoFields = [ {field_name:'', disabled:false}];


         var RactiveCatalog = Ractive.extend({
                    template: Template,
                    partials: { item:RowTemplate, 
                              catalogue:CatalogueTemplate,
                              customAttr:CustomAttrTemplate
                               },

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
                      magentoFields : [{disabled:false, field_name:"", test:"test"}],
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
                      disableButton:false,
                      config:Config,
                      lastSyncTime:''
                    },

        });

        this.loadFeedStatus( ractiveCatalog );

        this.loadChilds();
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
               if(obj.field_name === newValue){
                  obj.disabled = 'unbxd-hide';
               }
               if( obj.field_name === oldValue ){
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
               if(obj.field_name === newValue ){
                 obj.disabled = 'unbxd-hide';
               }
               if(obj.field_name === oldValue)
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
               var self = this;
               var $obj = Utils.getFields();
               $obj.then(function( data ){

                    var allFields    = data.fields,
                        field       = null;

                     self.fields = data.fields;
                     $.each(data.fields, function(a, val){ 
                         self.magentoFields.push({ field_name:val.field_name, disabled:false} );
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

                         self.magentoFields.each( function(item, j){
                           if(obj.field_name === item.field_name)
                              item.disabled = 'unbxd-hide';

                        });

                        field =  self.magentoFields.find(function( item ){
                            return item.value === obj.field_name
                       })

                       if(!field)
                        self.magentoFields.push({value:obj.field_name, disabled:'unbxd-hide'});
                          
                     });

                    //data = JSON.parse(data);
              
                    _unbxdObject.originalMapping = $.map( mappedAttributes, function(obj){
                                                               return $.extend({}, obj);
                                                       })
                    if(customAttributes.length < 1)
                      customAttributes = [{ field_name: '', datatype: '', canRemove:true }];

                    // try{
                    //   mappedAttributes.forEach(function(obj, i){
                    //    obj.tooltip =  Config.unbxdDimensions.filter(
                    //     function( dimension ){
                    //        return dimension.value === obj.featured_field;
                    //    })[0].helpText;
                    //   });
                    //}catch(e){}
      
                    data.datatype.unshift("");
                    ractiveCatalog.set({
                      mappedAttributes: mappedAttributes,
                      magentoFields: self.magentoFields,
                      customAttributes:customAttributes,
                      dataTypes : data.datatype
                    });
                  
                    _unbxdObject.catalogData = ractiveCatalog.data; 
                    $("._tooltip").popover({ trigger:'hover' });
                    $('select[name="unbxd-customattr"]').chosen('chosen:updated');
                    $('select[name="unbxd-select"]').prop('disabled', true).trigger("chosen:updated");
                    Utils.updateAllSelect();
                    ractiveCatalog.update();
                    self.loadChilds();
             })
         }
    
                     

    

      
         ractiveCatalog.on({

            showDataHelp:function(){
               $('#dataHelp').toggle();
            },

            removeRow:function ( event ) {
                var obj  =  this.get('customAttributes')[event.index.i],
                    len  =  this.get('customAttributes').length;

               if( !obj.canRemove  ){
                    result = window.confirm("Are you sure, you want to delete "+obj.field_name );
                    if(result){
                        var data = { fields:[obj] };
                        Utils.saveFields( data, 'PUT' )
                        .then(function(){
                        },function(){
                        });
                        this.get('customAttributes').splice(event.index.i, 1);
                    }
                }else{
                  this.get('customAttributes').splice(event.index.i, 1);
                }

                if(len === 1){
                   $(".add-attr").toggle();
                   $(".custom-attributes").toggle();
                   this.addItem();
                }

                  
            },

           saveMapping:function(event){
              var mapping = this.get('mappedAttributes'),
                  self = this, data = [], obj = {};

              this.set( {'saveMapping':'disabled-btn',
                          'successMsg':null,
                          'errorMsg':null
                });
              mapping.each(function(obj, i){
                 if(obj.field_name)
                    data.push(obj);
              })

              obj["fields"] = data;
              Utils.saveFields( obj ).then(
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
                  data = [],
                  obj = {};
              customMapping.each(function(obj, i){
                 if( obj.field_name )
                  data.push(obj);
              });
       
              this.set("disableSaveCustomAttr", "disabled-btn");
              this.set('customAttrSucessMsg', null);
              this.set('customAttrErrorMsg', null);
              obj["fields"] = data;
              Utils.saveFields( obj ).then(
                function( response ){
          
                  if(response.success){
                     self.set("customAttrSucessMsg", "Saved");
                     data.each(function(obj){
                       obj.canRemove = false;
                     })
                  }else{
                    for(key in response.errors)
                      self.set("customAttrErrorMsg", key +" "+response.errors[key] );
                      return;
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

               Utils.productSync()
               .then(function( data ){
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
               },function( error ){
                   self.set({
                      dataErrorMsg : "Some error, please try again"
                   });
               }).always(function(){
                   self.set({
                      disableButton:false,
                      dataWaitMsg:""
                   });
               });
     
           }

         });
        
         
    	},

      loadFeedStatus:function( ractiveCatalog ){
         Utils.feedStatus()
         .then(function( data ){
          if( data.success && data.lastUpload )
            ractiveCatalog.set({
               dataSuccessMsg:"Successfully uploaded",
               lastSyncTime : data.lastUpload 
            });
         },function( error ){

         });
      }

	};

	return Catalog;
});