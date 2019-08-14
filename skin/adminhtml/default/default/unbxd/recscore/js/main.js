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

//using global jquery as requirejs module
define('jquery', [], function() {
    return jQuery;
});


requirejs.config({
    baseUrl: window._unbxdSkinurl + 'unbxd/recscore/js/lib',
    paths: {
        app: '../app',
        templates:'../../templates',
        partials:'../../templates/partials'
    },
    waitSeconds:50
});

// Start the main app logic.
requirejs([ 
    'jquery',
    'ractive',
    'chosen',
    'ractive-chosen',
    'bootstrap-tooltip',
    'app/credentials',
    'app/catalog',
    'app/analytics',
    'app/widgets',
    'app/autosuggest',
    'app/search/landing',
    'rv!templates/index',
    'rv!partials/contact-form',
    'app/utils',
    'app/config',
    'app/search/hosted',
    'routie'],
    
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
      Autosuggest,
      SearchLanding,
      IndexTemplate,
      ContactUsModalTemplate,
      Utils,
      Config,
      HostedSearch) {
      
      for(key in _unbxdConfigs){
        Config[key] = _unbxdConfigs[key];

        if(_unbxdConfigs[key] === "1" || _unbxdConfigs[key] === "true" ){
          Config[key] = true;
        }else if( _unbxdConfigs[key] === "" || _unbxdConfigs[key] === "false" ){
          Config[key] = false;
        }
      };

      //set auth value from hidden field
      Config.authValue = $("input#auth").val();

        ractiveParent = {}, 
      _unbxdObject = {
          step1 : false,
          step2 : true,
          step3 : false,
          step4 : false,
          activeStep:{
            one:true
          },
          originalMapping:[],
          _unbxdSkinurl:_unbxdSkinurl,
          siteName : Config.site,
          ractiveAnalytics:{}
      };

       (function(){

              Utils.getSites()
              .then(function( data ){

                 ractiveParent = new Ractive({
                  el: 'container',
                  template: IndexTemplate,
                  partials:{
                    contactUsModal:ContactUsModalTemplate
                  },
                  data: { sites: ['a', 'b'],
                           site:data.sites[0].name,
                          _unbxdObject:_unbxdObject,
                          config:Config,
                          ccEmailIds:[]
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
                clearInterval( Config.pollingId );

               });


              ractiveParent.on({

                  showCredentials: function ( event ) {
                     Utils.activateLink( "one" );
                     Credentails.loadCredentailsTab( _unbxdObject );
                     clearInterval( Config.pollingId );
                  },

                  showCatalog:function( event ){
                     Utils.activateLink( "two" );
                     Catalog.loadCatalogTab( _unbxdObject );
                     clearInterval( Config.pollingId );
                  },

                  showAnalytics:function( event ){
                    Utils.activateLink( "three" );
                    Analytics.loadAnalyticsTab( _unbxdObject );
                  },

                  showWidgets:function( event ){
                    Utils.activateLink( "four" );
                    Widgets.loadWidgetsTab( _unbxdObject );
                    clearInterval( Config.pollingId );
                  },

                  showAutosuggest:function( event ){
                    Utils.activateLink( "five" );
                    Autosuggest.loadAutosuggest( _unbxdObject );
                    clearInterval( Config.pollingId );
                  },

                  showSearch:function( event ){
                    Utils.activateLink( "six" );
                    SearchLanding.load( _unbxdObject );
                    clearInterval( Config.pollingId );
                  },

                  addIdToCollection:function( event ){
                    var keycode = event.original.keyCode,
                        email = this.get('ccMailId').trim();

                    if(  (keycode === 188 || 
                         keycode === 32 || 
                         keycode === 13) &&
                         email.length > 0 &&
                         Utils.validateEmail(email) ){

                        this.get("ccEmailIds").push({
                          id:this.get('ccMailId')
                        });

                      this.set('ccMailId', '');
                    }else if(keycode ===8  && email.length ===0 ){
                       this.get('ccEmailIds').splice(this.get('ccEmailIds').length-1, 1);
                    }
                  },

                  removeId:function( event ){
                    var index = this.get('ccEmailIds').indexOf(event.context);
                    this.get('ccEmailIds').splice(index, 1);
                  },

                  sendMail:function(){
                    var $sendButton = $("#unbxdSend");

                     if( this.get('ccMailId').trim().length > 0 )
                        this.get("ccEmailIds").push({id:this.get('ccMailId')});

                     if( this.get("ccEmailIds").length > 0 )
                         emailIds = this.get("ccEmailIds").join("||");
                     else
                        return;

                     var data = {
                         content:this.get('mailBody'),
                         cc : emailIds,
                         subject:'Support request - magent search'
                     };

                     this.set('ccEmailIds',[]);
                     this.set('ccMailId','');
                     this.set('mailBody', '');

                     Utils.sendMail(data).then(
                      function( data ){
                        if(data.success && data.success===true && $sendButton.data().hostedsearch === true)
                          HostedSearch.setData({search_hosted_int_status:Config.status.requested});
                     },function(){

                     });
                  }

                });

               Config.site = data.sites[0].name;
               ractiveParent.set('sites', data.sites);
               data = data.sites[0];
               if(data.numDocs && data.numDocs > 0){
                  _unbxdObject.step2 = true;
                  _unbxdObject.products = data.numDocs
               }

               Credentails.loadCredentailsTab( _unbxdObject );
               Utils.updateAllSelect();

               Utils.getStates()
                    .then(function( data ){
                       for(key in Config.states){
                          Config.states[key] = data[key].status
                       }
                    },function( error ){

                    })
              
            })
      })();//load main


  
Ractive.decorators.chosen.type.site = function (node) {
    return {
        disable_search_threshold:5
    }
};

     
});














