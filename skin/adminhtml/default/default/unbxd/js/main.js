

//using global jquery as requirejs module
define('jquery', [], function() {
    return jQuery.noConflict();
});


requirejs.config({
    baseUrl: window._unbxdSkinurl + 'unbxd/js/lib',
    paths: {
        app: '../app'
    },
});

// Start the main app logic.
requirejs([ 
    'jquery',
    'ractive',
    'chosen',
    'ractive-chosen',
    'bootstrap-tooltip',
    'app/credentails',
    'app/catalog',
    'app/analytics',
    'app/widgets',
    'app/utils'],
function ( 
      $,
      Ractive, 
      Chosen,
      RC,
      BC,
      Credentails, 
      Catalog, 
      Analytics, 
      Widgets,
      Utils) {
   

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
                Utils.activateLink( "one" );
                Credentails.loadCredentailsTab( _unbxdObject );
                clearInterval( _unbxdObject.pollingId );

               });


              ractiveParent.on({

                  showCredentials: function ( event ) {
                     Utils.activateLink( "one" );
                     Credentails.loadCredentailsTab( _unbxdObject );
                     clearInterval( _unbxdObject.pollingId );
                  },

                  showCatalog:function( event ){
                     Utils.activateLink( "two" );
                     Catalog.loadCatalogTab( _unbxdObject );
                     clearInterval( _unbxdObject.pollingId );
                  },

                  showAnalytics:function( event ){
                    Utils.activateLink( "three" );
                    Analytics.loadAnalyticsTab( _unbxdObject );
                  },

                  showWidgets:function( event ){
                    Utils.activateLink( "four" );
                    Widgets.loadWidgetsTab( _unbxdObject );
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

               Credentails.loadCredentailsTab( _unbxdObject );
               Utils.updateAllSelect();

              
            })
      }//load main

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

  



Ractive.decorators.chosen.type.site = function (node) {
    return {
        disable_search_threshold:5
    }
};

     
});


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











