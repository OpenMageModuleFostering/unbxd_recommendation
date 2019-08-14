
define([ "jquery",
       "app/config",
       "app/utils",
        "rv!templates/credentails" ], 
function( $ ,
         Config,
         Utils,
        Template) {
    
    var credentails =  {

		//credentials view
        loadCredentailsTab : function(  _unbxdObject ){
               var site = Config.site;
     
                var ractiveCredentials = new Ractive({
                    el: 'innerContainr',
                    template: Template,
                    data : { 
                             site_key   : _unbxdObject.siteKey,
                             secret_key : _unbxdObject.secretKey,
                             step1:_unbxdObject.step1,
                             _unbxdObject:_unbxdObject,
                             btnDisabled:false,
                             errorMsg:null,
                             successMsg:null,
                             config:Config
                          }
                 });

                Config.site = jQuery("#selectSites").chosen().val();
                _unbxdObject.siteName = site;

                var $keys = Utils.getKeys();

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
            
                        var self = this;
                        this.set({
                           btnDisabled : true,
                           errorMsg : null,
                           successMsg:null
                        });

                        Utils.authenticateKeys( data )
                        .then(function( data ){
                           if(!data.success){
                                self.set('errorMsg', data.errors.message);
                                return;
                              }
                              _unbxdObject.step1 = true;
                              self.set({
                                 step1 : true,
                                 successMsg:Config.unbxdMessages.authSuccess
                               });
                        },function( error ){

                        },function(){
                           self.set('btnDisabled', false);
                        }); 
                    
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
      }


    };


    return credentails;

});





 




  

      

     

  