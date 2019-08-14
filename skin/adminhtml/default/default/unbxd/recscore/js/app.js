// code, which is in a single file
(function($) {

var baseUrl = _unbxdSkinurl +'unbxd/templates/';
     site = "", 
    ractiveParent = {}, 
    _unbxdObject = {
        step1 : false,
        step2 : false,
        step3 : false,
        step4 : false,
        activeStep:{
          one:true
        },
        originalMapping:[],
        _unbxdSkinurl:_unbxdSkinurl,
        siteName : site,
        ractiveAnalytics:{}
    },
    unbxdMessages = {
        authSuccess:"The Unbxd Module has been authenticated. Proceed to Catalog Configuration",
        uploadSuccess:"Uploaded Catalog Data successfully. Proceed to Analytics integration"
    },
    unbxdDimensions = [
        {
            "value": "brand",
            "helpText": "The brand to which a product belongs to"
        },
        {
            "value": "imageUrl",
            "helpText": "Thumbnail - the URL of the image of a product that needs to be displayed in the recommendation widget, normally the thumnail"
        },
        {
            "value": "title",
            "helpText": "The title or name of the product e.g 'Blue Nike Shoe' "
        },
        {
            "value": "price",
            "helpText": "The display price of the product e.g $499"
        },
        {
            "value": "color",
            "helpText": "The color of the product e.g. 'Green t-shirt' "
        },
        {  
            "value": "productUrl",
            "helpText": "The material the product is made of e.g. 'Cotton trousers' "
        },
        {     
            "value": "sellingPrice",
            "helpText": "The display price of the product e.g $499" 
        },    
        {
            "value": "size",
            "helpText": "The size of the product e.g. 'Size 10' shoes"
        }
  ];

$.get( baseUrl + 'credentails.html')
      .then(function( template ){
         $('#tab1Template').text( template );
         //load tab2
         $.get( baseUrl + 'catalog.html')
          .then(function( template ){
             $('#tab2Template').text( template );
             //load tab3
             $.get( baseUrl + 'analytics.html')
             .then(function( template ){
               $('#tab3Template').text( template );
               //load tab4
               $.get( baseUrl + 'widgets.html')
                .then(function( template ){
                 $('#tab4Template').text( template );
                  //load row
                  $.get( baseUrl + 'custom.html')
                  .then(function( template ){
                    $('#rowTemplate').text( template );
                      loadMain();
                  })         
                });
             });
          });
      });


 //activate the clicked side nav, remove active from rest  
  var activateLink = function( step ){
     _unbxdObject.activeStep = {};
     _unbxdObject.activeStep[step] = true;
     ractiveParent.update();
  };


  var loadMain = function(){



        var $result = $.get( _unbxdBaseurl + 'unbxd/config/site');
            $result.then(function( data ){

               ractiveParent = new Ractive({
                el: 'container',
                template: '#template',
                data: { sites: ['a', 'b'],
                         site:data.sites[0].name,
                        _unbxdObject:_unbxdObject
                      }
                });

             ractiveParent.observe('site', function(newValue, oldValue, keypath, s) { 
                if( !oldValue) 
                  return; 
  
                _unbxdObject.catalogData = null;
                _unbxdObject.originalMapping = [];
                _unbxdObject.secretKey = "";
                _unbxdObject.siteKey = "";
                _unbxdObject.step1 = false;
                  activateLink( "one" );
                  loadCredentailsTab();
                  clearInterval( _unbxdObject.pollingId );

             });


              ractiveParent.on({

                showCredentials: function ( event ) {
                   activateLink( "one" );
                   loadCredentailsTab();
                   clearInterval( _unbxdObject.pollingId );
                },

                showCatalog:function( event ){
                   activateLink( "two" );
                   loadCatalogTab();
                   clearInterval( _unbxdObject.pollingId );
                },

                showAnalytics:function( event ){
                  activateLink( "three" );
                  loadAnalyticsTab();
                },

                showWidgets:function( event ){
                  activateLink( "four" );
                  loadWidgetsTab();
                  clearInterval( _unbxdObject.pollingId );
                }

              });

             site = data.sites[0].name;
             ractiveParent.set('sites', data.sites);
             data = data.sites[0];
             if(data.numDocs && data.numDocs > 0){
                _unbxdObject.step2 = true;
                _unbxdObject.products = site.numDocs
             }

             loadCredentailsTab();
             updateAllSelect();

            
          })

      

     

      //credentials view
      var loadCredentailsTab = function(site_key, secret_key){

                var ractiveCredentials = new Ractive({
                    el: 'innerContainr',
                    template: '#tab1Template',
                    data : { 
                             site_key   : _unbxdObject.siteKey,
                             secret_key : _unbxdObject.secretKey,
                             step1:_unbxdObject.step1,
                             _unbxdObject:_unbxdObject,
                             btnDisabled:false,
                             errorMsg:null,
                             successMsg:null
                          }
                 });

                site = jQuery("#selectSites").chosen().val();
                _unbxdObject.siteName = site;

                var $keys = $.get( _unbxdBaseurl +  "unbxd/config/keys?site="+site);

               $keys.then(function( data ){
                     if( !data.success) {
                       ractiveCredentials.set({site_key:"", secret_key:"", step1:false})
                       return;
                     }
                     
                    _unbxdObject.step1 = true;
                    _unbxdObject.siteKey = data.site_key;
                    _unbxdObject.secretKey = data.secret_key;
                    ractiveCredentials.set({site_key:data.site_key, secret_key:_unbxdObject.secretKey, step1:true})
                    ractiveParent.update();
                  },function( error ){
                    console.log(error);
                    
                  })

      

                ractiveCredentials.on({
                    //show tool tip
                    showSiteKeyHelp:function(){
                       $('.site-key-help').show();
                    },

                    hideSiteKeyHelp:function(){
                      $('.site-key-help').hide();
                    },

                    showSecretKeyHelp:function(){
                      $('.secret-key-help').show();
                    },

                    hideSecretKeyHelp:function(){
                       $('.secret-key-help').hide();
                    },

                    authenticate:function(){
                       var  data = { 
                                site_key: this.get("site_key"), 
                                secret_key:this.get("secret_key") 
                              };
                        data = JSON.stringify(data);
                        var self = this;
                        this.set({
                           btnDisabled : true,
                           errorMsg : null,
                           successMsg:null
                        });
                        $.ajax({
                          type:'POST',
                          url:_unbxdBaseurl +'unbxd/config/keys?site='+ site,
                          contentType:"application/json",
                          data:data,
                          success:function( data ){
                              if(!data.success){
                                self.set('errorMsg', data.errors.message);
                                return;
                              }
                              _unbxdObject.step1 = true;
                              self.set({
                                 step1 : true,
                                 successMsg:unbxdMessages.authSuccess
                               });
                          },
                          error:function(){

                          },
                          complete:function(){
                            self.set('btnDisabled', false);
                          }
                        })
                    
                    },

                    reauthenticate:function(){
                      this.set({
                        step1:false,
                        site_key:"",
                        secret_key:""
                      })
                    }
                })

              
                $(".magento-tooltip").popover({ trigger:'hover' });
      };

    //catalog view
    var loadCatalogTab = function(){
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
            updateAllSelect();
            
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
            updateAllSelect();
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
                updateAllSelect();
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

              saveFields( data ).then(
              function( data ){
                 if(data.success){
                     self.set({ 
                        enableEdit: false,
                        saveMapping:'disabled-btn',
                        successMsg:"Saved succesfully",
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
              saveFields( data ).then(
                function( response ){
                  if(response.success){
                     self.set("customAttrSucessMsg", "Saved succesfully");
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
              updateAllSelect();
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
         
    };

    //analytics view
    var loadAnalyticsTab = function(){
        
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
    

    };

    //widgets view
    var loadWidgetsTab = function(){
        
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
    };

    var configureSelect = function(){
      var chosenObj = $('select[name="unbxd-select"]').chosen();
          chosenObj.trigger('chosen:updated');
    };
    //re render all chosen selects
    var updateAllSelect = function(){
       $('select').trigger('chosen:updated');
    };

    var saveFields = function( data ){
        return $.ajax({
                data: JSON.stringify({ "fields": data }) ,
                contentType:'application/json',
                type:'POST',
                url: _unbxdBaseurl + 'unbxd/config/fields?site='+site
          })
    }
      
}

Ractive.decorators.chosen.type.site = function (node) {
    return {
        disable_search_threshold:5
    }
};

 })(jQuery)

jQuery.noConflict();
//hack for prototypejs
Array.prototype.map = function(callback, thisArg) {

    var T, A, k;

    if (this == null) {
      throw new TypeError(" this is null or not defined");
    }

    var O = Object(this);
    var len = O.length >>> 0;

    if (typeof callback !== "function") {
      throw new TypeError(callback + " is not a function");
    }

    if (arguments.length > 1) {
      T = thisArg;
    }

    A = new Array(len);
    k = 0;
    while (k < len) {
      var kValue, mappedValue;
      if (k in O) {
        kValue = O[k];
        mappedValue = callback.call(T, kValue, k, O);
        A[k] = mappedValue;
      }
      k++;
    }

    return A;
  };








