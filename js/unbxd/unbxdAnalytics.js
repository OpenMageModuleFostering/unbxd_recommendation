if(typeof(Unbxd) === 'undefined')
    window.Unbxd = {};

Unbxd.version = "2.8.22.1";

// set UnbxdMode to manage tracker_url
Unbxd.local = typeof UnbxdMode !== 'undefined' && UnbxdMode == 'local'

// Setting tracker URL
Unbxd.tracker_url = Unbxd.local === true ? '/v2/1p.jpg' : '//tracker.unbxdapi.com/v2/1p.jpg';

// Initialize an empty config object
Unbxd.conf = Unbxd.conf || {};

/**
 * Cookies
 */
Unbxd.cookies = {
    uid : "userId",
    visitor : "visit",
    pending : "pen", // Pending
    debug : "debug", // enables Debugging
    disabled : "disabled" // A/B
};

Unbxd.extend = function(){
    for(var i=1; i<arguments.length; i++)
        for(var key in arguments[i])
            if(arguments[i].hasOwnProperty(key))
                arguments[0][key] = arguments[i][key];
    return arguments[0];
}

; // Do not remove
(function (u) {
    var pluses = /\+/g;

    function decode(s) {
        if (config.raw) {
            return s;
        }
        return decodeURIComponent(s.replace(pluses, ' '));
    }

    function decodeAndParse(s) {
        if (s.indexOf('"') === 0) {
            // This is a quoted cookie as according to RFC2068, unescape...
            s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        }

        s = decode(s);

        try {
            return config.json ? JSON.parse(s) : s;
        } catch(e) {}
    }

    var config = u.cookie = function (key, value, options) {

        // Write
        if (value !== undefined) {
            options = Unbxd.extend({}, config.defaults, options);

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            value = config.json ? JSON.stringify(value) : String(value);

            return (document.cookie = [
                config.raw ? key : encodeURIComponent(key),
                '=',
                config.raw ? value : encodeURIComponent(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // Read
        var cookies = document.cookie.split('; ');
        var result = key ? undefined : {};
        for (var i = 0, l = cookies.length; i < l; i++) {
            var parts = cookies[i].split('=');
            var name = decode(parts.shift());
            var cookie = parts.join('=');

            if (key && key === name) {
                try{
                    result = decodeAndParse(cookie);
                    break;
                }catch(e){}
            }

            if (!key) {
                try{
                    result[name] = decodeAndParse(cookie);
                }catch(e){}
            }
        }

        return result;
    };

    config.defaults = {};

    u.deleteCookie = function (key, options) {
        if (u.cookie(key) !== undefined) {
            // Must not alter options, thus extending a fresh object...
            u.cookie(key, '', Unbxd.extend({}, options, { expires: -1 }));
            return true;
        }
        return false;
    };

})(Unbxd);

Unbxd.key = function(){
    if(typeof(UnbxdKey) != 'undefined' && UnbxdKey != ""){
        return UnbxdKey;
    }

    if(typeof(UnbxdSiteName) != 'undefined' && UnbxdSiteName != ""){
        return UnbxdSiteName;
    }

    return false;
}

Unbxd.setCookie = function(name, value, expires){
    try{
        var params = {path : '/'};
        if(expires){
            params.expires = expires;
        }

        // Explicitly set domain for cookies
        var domain = Unbxd.getRootDomain(document.URL);
        if(domain != undefined){
            params.domain = domain;
        }

        return this.cookie('unbxd.' + name, value, params);
    }catch(e){
        Unbxd.log(e);
    }
}

Unbxd.readCookie = function(name){
    try{
        return this.cookie('unbxd.' + name, undefined, { path: '/'});
    }catch(e){
        // Unbxd.log(e);
    }

    return undefined;
}

Unbxd.removeCookie = function(name){
    var domain = Unbxd.getRootDomain(document.URL);
    this.deleteCookie('unbxd.' + name, {path : '/', domain : domain});
    this.deleteCookie('unbxd.' + name, {path : '/', domain : 'www' + domain});
}

Unbxd.getCookiesStartsWith = function(prefix){
    try{
        var cookies = this.cookie();
        var keys = _.keys(cookies);

        keys = _.filter(keys, function(key){
            return key.indexOf('unbxd.' + prefix) == 0;
        });

        var filtered = {};
        _.each(keys, function(key){
            filtered[key] = cookies[key];
        });

        return filtered;
    }catch(e){
        Unbxd.log(e);
    }

    return {};
}

Unbxd.setCookieIfNotSet = function(name, value, expires){
    var v = Unbxd.readCookie(name);
    if(_.isUndefined(v) || v == ''){
        Unbxd.log("Cookie : " + name + " not found. Will set to : " + value);
        Unbxd.setCookie(name, value, expires);
        return true;
    }

    return false;
}

Unbxd.log = function(str){
    if(Unbxd.readCookie(Unbxd.cookies.debug) === '1'){
        console.log("Unbxd : " + str);

        if(window.jQuery)
            jQuery('#ubx-console').append("<div style='padding:2px;'>" + str + "</div>");
    }
}

Unbxd.getPathName = function(url){
    if(url == undefined) return null;

    var a = document.createElement('a'); // Create a dummy <a> element
    a.href = url;                       // Assign link, let the browser parse it
    return a.pathname;
}

Unbxd.getHostName = function(url){
    if(url == undefined) return null;

    var a = document.createElement('a'); // Create a dummy <a> element
    a.href = url;                       // Assign link, let the browser parse it
    return a.hostname;
}

Unbxd.getRootDomain = function(url){
    var hostname = Unbxd.getHostName(url);
    try{
        var checkIP = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/;
        if(checkIP.test(hostname)){
            return hostname;
        }

        var parts = hostname.split('.').reverse();
        if(parts.length > 1){
            var domain =  '.' + parts[1] + '.' + parts[0];

            if(parts.length > 2){ // Handling SLDs
                var slds = [".co.uk", ".co.in", ".com.au", ".com.my", ".co.nz"]; // :( This will need to be updated from time to time.
                if(slds.indexOf(domain) != -1){
                    domain = '.' + parts[2] + domain;
                }
            }

            return domain;
        }
    }catch(e){}
}

Unbxd.getParameterByName = function(name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

Unbxd.getPendingBeacons = function(){
    return Unbxd.getCookiesStartsWith(Unbxd.cookies.pending);
}

Unbxd.fire = function(action, beacon, success){
    var key = Unbxd.key();
    if(key == false) return;

    var uid = Unbxd.readCookie(Unbxd.cookies.uid);

    try{
        var url = Unbxd.tracker_url
            + '?data=' + encodeURIComponent(beacon)
            + '&UnbxdKey=' + key
            + '&action=' + action
            + '&uid=' + uid
            + '&t=' + (new Date().getTime()) + "|" + Math.random();

        var img = new Image();
        img.src = url;

        Unbxd.log("Beaconing --> Action : " + action + ", uid : " + uid + ", " + beacon);
    }catch(e){
        Unbxd.log(e);
    }
}

Unbxd.beaconCount = 0;
Unbxd.send = function(){
    if(Unbxd.beaconCount >= 100){
        Unbxd.log("Too many beacons. Something might be wrong")
        return;
    }

    Unbxd.beaconCount++;

    // Get oldest pending beacon;
    var pendingBeacons = Unbxd.getPendingBeacons();

    if(_.size(pendingBeacons) > 0){
        var oldest = _.min(_.keys(pendingBeacons), function(key){
            return key.substr(key.lastIndexOf('.') + 1);
        });

        var tokens = oldest.split(".");

        if(tokens.length < 4){
            // Fatal. Do something!
            // Will try to delete the cookie.
            Unbxd.removeCookie(oldest.substr(oldest.indexOf('.') + 1));
        }else{
            var action = tokens[2];
            var time = tokens[3];

            var beacon = pendingBeacons[oldest];

            try{
                Unbxd.removeCookie(Unbxd.cookies.pending + "." + action + "." + time);
                Unbxd.fire(action, beacon);
                if(_.size(pendingBeacons) > 1){
                    Unbxd.send();
                }

                // Unbxd.fire(action, beacon, function(){
                // 	// Remove the cookie if image is loaded
                // 	Unbxd.removeCookie(Unbxd.cookies.pending + "." + action + "." + time);
                // });

                // setTimeout(function(){
                // 	Unbxd.removeCookie(Unbxd.cookies.pending + "." + action + "." + time);
                // }, 500); // Delete anyway after 1 sec

                // if(_.size(pendingBeacons) > 1){
                // 	setTimeout(Unbxd.send, 1000); // Send others after 1.5 sec
                // }
            }catch(e){
                Unbxd.log(e);
            }
        }
    }
}

Unbxd.push = function(action, options, immediate){
    if(Unbxd.key() == false){
        Unbxd.log("Key not found. Abort");
        return;
    }

    if(Unbxd.conf && Unbxd.conf.off != undefined && Unbxd.conf.off == true){
        Unbxd.log("Tracking is off. Abort");
        return;
    }

    options = options || {};
    options["url"] = document.URL;
    options["referrer"] = document.referrer;
    options['visit_type'] = Unbxd.readCookie(Unbxd.cookies.visitor);
    options['ver'] = Unbxd.version

    // If A/B test is on, push the disabled field
    var disabled = Unbxd.readCookie(Unbxd.cookies.disabled);
    if(!_.isUndefined(disabled) && disabled !== ''){
        var tokens = disabled.split('-');
        if(tokens.length == 2){
            options['disabled'] = tokens[0];
        }
    }

    // Override immediate behavior
    if(Unbxd.conf && Unbxd.conf.immediate && Unbxd.conf.immediate[action] != undefined){
        immediate = Unbxd.conf.immediate[action];
    }

    if(immediate){
        Unbxd.fire(action, JSON.u_stringify(options));
    }else{
        var cookieName = Unbxd.cookies.pending + "." + action + "." + new Date().getTime();
        Unbxd.setCookie(cookieName, JSON.u_stringify(options));
        Unbxd.log("Pending cookie : " + cookieName);

        setTimeout(Unbxd.send, 10000); // Send anyway after 10 secs
    }
}

Unbxd.ab = function(){
    // If only a certain percentage of users have to be shown Unbxd results.
    if(!_.isUndefined(Unbxd.conf)
        && !_.isUndefined(Unbxd.conf.disabled)
        && !_.isUndefined(Unbxd.conf.disabled.percentage)
        && !_.isUndefined(Unbxd.conf.disabled.version)){
        var date = new Date();

        var percentageDisabled = Unbxd.conf.disabled.percentage;
        var version = Unbxd.conf.disabled.version;

        var deleteDisabledCookie = true;

        // If the version of setting is newer than what cookie says, delete the visitor and disabled cookie
        var disabled = Unbxd.readCookie(Unbxd.cookies.disabled);
        if(!_.isUndefined(disabled) && disabled !== ''){
            var tokens = disabled.split('-');
            if(tokens.length == 2){
                oldVersion = tokens[1];

                if(version == oldVersion){
                    deleteDisabledCookie = false;
                }
            }
        }

        if(deleteDisabledCookie){
            Unbxd.removeCookie(Unbxd.cookies.disabled);
        }

        // Figure out is the user has to be disabled
        var rand = Math.floor((Math.random() * 100) + 1);
        Unbxd.log("Random number : " + rand);
        if(rand <= percentageDisabled){
            // Disable
            Unbxd.setCookieIfNotSet(Unbxd.cookies.disabled, "1-" + version, new Date(date.getFullYear() + 10, 1, 1));
        }else{
            Unbxd.setCookieIfNotSet(Unbxd.cookies.disabled, "0-" + version, new Date(date.getFullYear() + 10, 1, 1));
        }
    }else{
        var disabled = Unbxd.readCookie(Unbxd.cookies.disabled);
        if(!_.isUndefined(disabled) && disabled !== ''){
            // Will reach here if there were A/B tests running before but not now.
            Unbxd.removeCookie(Unbxd.cookies.disabled);
        }
    }
}

/**
 * Sets a user id
 */
Unbxd.user = function(){
    var visitType = undefined;

    var date = new Date();
    var uid = 'uid-' + date.getTime() +  "-" + Math.floor(Math.random() * 100000);
    if(Unbxd.setCookieIfNotSet(Unbxd.cookies.uid, uid, new Date(date.getFullYear() + 10, 1, 1))){
        visitType = 'first_time';
    }else{
        visitType = 'repeat';
    }

    Unbxd.ab(); // Do whatever to be done when running ab test

    var now = date.getTime();
    var expire = new Date(now + 30 * 60000); // 30 mins
    if(Unbxd.setCookieIfNotSet(Unbxd.cookies.visitor, visitType, expire)){
        Unbxd.push("visitor", {});
        Unbxd.log("Pushed Visitor Event");
    }
}

Unbxd.init = function(){
    Unbxd.log("Initializing...");

    Unbxd.user(); // Initialize uid

    Unbxd.send(); // Dispatch pending beacons
    // setInterval(Unbxd.send, 3 * 1000); // Start a timer interval to send beacons periodically

    // Injecting UnbxdKey in dom
    // jQuery('body').append("<div style='display:none;' id='unbxd-site-name'>" + Unbxd.key() + "</div>");
}

// <meta name="unbxd:type" content="category">
// Requires jQuery
if(Unbxd.resolveCategory == undefined){
    Unbxd.resolveCategory = function(){
        var content = jQuery("meta[name='unbxd:type']").attr('content');
        if(content != undefined && (content == "landing" || content == "category")){
            return Unbxd.getPathName(document.URL);
        }

        return false;
    }
}

// Requires jQuery
Unbxd.gatherImpressions = function(){
    var boxElems = jQuery('[unbxdAttr="product"]');
    _.each(boxElems, function(elem){
        var boxType = jQuery(elem).attr("unbxdParam_boxtype");

        identifier = jQuery(elem).attr("unbxdParam_source_pid")
            || jQuery(elem).attr("unbxdParam_category")
            || jQuery(elem).attr("unbxdParam_brand");

        Unbxd.addImpression(boxType, identifier);
    });

    boxElems = jQuery('[data-unbxdAttr="product"]');
    _.each(boxElems, function(elem){
        var boxType = jQuery(elem).attr("data-unbxdParam_boxtype");

        identifier = jQuery(elem).attr("data-unbxdParam_source_pid")
            || jQuery(elem).attr("data-unbxdParam_category")
            || jQuery(elem).attr("data-unbxdParam_brand");

        Unbxd.addImpression(boxType, identifier);
    });
}

/*
 * APIs
 */
Unbxd.track = function(type, params){
    if(type == 'search'){
        Unbxd.addSearch(params.query,params.autosuggestParams);
    }else if(type == 'browse'){
        Unbxd.addBrowse(params.category);
    }else if(type == 'widgetImpression'){
        Unbxd.addImpression(params.boxType, params.identifier);
    }else if(type == 'click'){
        Unbxd.addClick(params.pid, params.prank, params.boxType);
    }else if(type == 'addToCart'){
        Unbxd.addCart(params.pid);
    }else if(type == 'order'){
        Unbxd.addOrder(params.pid, params.qty, params.price);
    }else if(type == 'map_token'){
        Unbxd.mapToken(params.token);
    }
}
// ===================================

/*
 * Register a new search query.
 */
Unbxd.addSearch = function(query, autosuggest_data){
    if(autosuggest_data != undefined){
        Unbxd.push("search", {query : query, autosuggest_data : autosuggest_data});
        Unbxd.log("Pushed Autosuggest Hit");
    }
    else{
        Unbxd.push("search", {query: query});
    }
    Unbxd.log("Pushed Search Hit for query : " + query);
}

/*
 * Register a new browse query.
 */
Unbxd.addBrowse = function(path){
    Unbxd.push("browse", {query : path}, true);
    Unbxd.log("Pushed Browse Hit");
}

/*
 * Register a new product click. if query or queryType is null. it will go as anon click
 */
Unbxd.addClick = function(pid, prank, boxType){
    Unbxd.push("click", {pid : pid, pr : prank, box_type : boxType});
    Unbxd.log("Pushed Click");
}

/*
 * Register a new product cart. if query or queryType is null. it will go as anon cart
 */
Unbxd.addCart = function(pid){
    Unbxd.push("cart", {pid : pid});
    Unbxd.log("Pushed Add to Cart");
}

/*
 * Register a new order. if query or queryType is null. it will go as anon order
 */
Unbxd.addOrder = function(pid, qty, price){
    var ordersStr = Unbxd.readCookie('orders') || "";
    try{
        var orders = ordersStr.split(',');
        if(orders != null && orders.length > 0 && _.indexOf(orders, pid) != -1){
            return;
        }
    }catch (e){}

    ordersStr = ordersStr + "," + pid;
    Unbxd.setCookie('orders', ordersStr);

    Unbxd.push("order", {pid : pid, qty : qty, price : price}, true);
    Unbxd.log("Pushed Order");
}

/*
 * Maps a token with the uid
 */
Unbxd.mapToken = function(token){
    Unbxd.push("user_external_token", {external_token : token}, true);
    Unbxd.log("Mapped uid with : " + token);
}

/*
 * Register a Recommender Box Impression
 */
Unbxd.addImpression = function(boxType, identifier){
    Unbxd.boxes = Unbxd.boxes || [];

    if(boxType && Unbxd.boxes.indexOf(boxType) == -1){
        Unbxd.boxes.push(boxType);
        Unbxd.log("Found box : " + boxType);

        var path = Unbxd.getPathName(document.URL);
        Unbxd.push("impression", {box_type : boxType, path : path, identifier : identifier}, true);
        Unbxd.log("Pushed recommender impression");
    }
}

; // The Trailing semicolon is necessary. And there is a prize if you guess why :D
(new function(){
    // Prototype bug.
    var _array_tojson = Array.prototype.toJSON;
    JSON.u_stringify = function(value) {
        try{
            if(typeof Prototype !== 'undefined' && parseFloat(Prototype.Version.substr(0,3)) < 1.7 && typeof _array_tojson !== 'undefined'){
                delete Array.prototype.toJSON;
                var r = JSON.stringify(value);
                Array.prototype.toJSON = _array_tojson;
                return r;
            }
        }catch(ex){}

        return JSON.stringify(value);
    };

    var debugInfo = function(){
        if(window.jQuery){
            var category = Unbxd.resolveCategory();

            var searchBox = jQuery('[unbxdAttr="sq"]').size() || jQuery('[data-unbxdAttr="sq"]').size() == 1;
            var searchBtn = jQuery('[unbxdAttr="sq_bt"]').size() || jQuery('[data-unbxdAttr="sq_bt"]').size() == 1;
            var categoryPage = category != undefined && category != false;
            var products = jQuery('[unbxdAttr="product"]').size() || jQuery('[data-unbxdAttr="product"]').size();
            var addToCarts = jQuery('[unbxdAttr="AddToCart"]').size() || jQuery('[data-unbxdAttr="AddToCart"]').size();
            var orders = jQuery('[unbxdAttr="order"]').size() || jQuery('[data-unbxdAttr="order"]').size();

            var key = Unbxd.key();
            if(key == false) return;

            jQuery('body')
                .append(
                    "<div id='ubx-debug' style='position:fixed; right:0; top:10px; max-width:300px; background-color:rgba(69, 181, 193, 1); padding : 10px; z-index:99999999; color:#fff; font-size:13px;'>"
                    + "Unbxd Info : <a href='#' id='ubx-close'>Close</a>"
                    + "<ul style='padding:0 0 0 10px; margin:5px;'>"
                    + "<li>Site name : " + key + "</li>"
                    + "<li>Search Box present : " + searchBox + "</li>"
                    + "<li>Search Btn present : " + searchBtn + "</li>"
                    + "<li>Category Page : " + categoryPage + "</li>"
                    + "<li>Products : " + products  + "</li>"
                    + "<li>Add To Cart buttons : " + addToCarts + "</li>"
                    + "<li>Orders : " + orders + "</li>"
                    + "</ul>"
                    + "<hr>"
                    + "<div id='ubx-console'></div>"
                    + "</div>");

            jQuery('#ubx-close').click(function(){
                jQuery('#ubx-debug').hide();
            })
        }
    }

    // Requires jQuery
    var attachListener = function(selector, event, fn){
        if(window.jQuery){
            if(jQuery(document).delegate){
                jQuery(document).delegate(selector, event, fn);
            }else{
                jQuery(selector).live(event, fn);
            }
        }
    }

    // Requires jQuery
    var attachListeners = function(){
        /**
         * Event listeners. They are delegates put on document object.
         */

            // EventListener for Search Query tracking
        attachListener('[unbxdAttr="sq"]', "keydown", function(event) {
            if (event.which == 13) { // Enter key
                query = jQuery(this).val();
                Unbxd.addSearch(query);
            }
        });

        attachListener('[data-unbxdAttr="sq"]', "keydown", function(event) {
            if (event.which == 13) { // Enter key
                query = jQuery(this).val();
                Unbxd.addSearch(query);
            }
        });

        // Event Listener for Search Query (pressing search button)
        attachListener('[unbxdAttr="sq_bt"]', "mouseup", function() {
            query = jQuery('[unbxdAttr="sq"]').val();
            Unbxd.addSearch(query);
        });

        attachListener('[data-unbxdAttr="sq_bt"]', "mouseup", function() {
            query = jQuery('[data-unbxdAttr="sq"]').val();
            Unbxd.addSearch(query);
        });

        // Event Listener for Search Query (pressing search button)
        // <a href='' unbxdAttr="sq_link" unbxdParam_q="Sale">Sale</a>
        attachListener('[unbxdAttr="sq_link"]', "mouseup", function() {
            query = jQuery(this).attr('unbxdParam_q');
            Unbxd.addSearch(query);

            e.stopPropagation();
        });

        attachListener('[data-unbxdAttr="sq_link"]', "mouseup", function() {
            query = jQuery(this).attr('data-unbxdParam_q');
            Unbxd.addSearch(query);

            e.stopPropagation();
        });


        // Event Listener for Browse Hits
        var category = Unbxd.resolveCategory();
        if(category != undefined && category != false){
            Unbxd.addBrowse(category);
        }

        attachListener('[unbxdAttr="product"]', "mouseup", function() {
            var boxType = jQuery(this).attr("unbxdParam_boxtype");
            var pr = jQuery(this).attr("unbxdParam_pRank");
            var pid = jQuery(this).attr("unbxdParam_sku") || jQuery(this).attr("unbxdParam_pid");

            Unbxd.addClick(pid, pr, boxType);
        });

        attachListener('[data-unbxdAttr="product"]', "mouseup", function() {
            var boxType = jQuery(this).attr("data-unbxdParam_boxtype");
            var pr = jQuery(this).attr("data-unbxdParam_pRank");
            var pid = jQuery(this).attr("data-unbxdParam_sku") || jQuery(this).attr("data-unbxdParam_pid");

            Unbxd.addClick(pid, pr, boxType);
        });

        // EventListener for Add to Cart Metric
        attachListener('[unbxdAttr="AddToCart"]', "mouseup", function() {
            var pid = jQuery(this).attr("unbxdParam_sku") || jQuery(this).attr("unbxdParam_pid");
            Unbxd.addCart(pid);
        });

        attachListener('[data-unbxdAttr="AddToCart"]', "mouseup", function() {
            var pid = jQuery(this).attr("data-unbxdParam_sku") || jQuery(this).attr("data-unbxdParam_pid");
            Unbxd.addCart(pid);
        });

        var orderElems = jQuery('[unbxdAttr="order"]');
        _.each(orderElems, function(elem){
            var pid = jQuery(elem).attr("unbxdParam_sku") || jQuery(elem).attr("unbxdParam_pid");
            Unbxd.addOrder(pid);
        });

        var orderElems = jQuery('[data-unbxdAttr="order"]');
        _.each(orderElems, function(elem){
            var pid = jQuery(elem).attr("data-unbxdParam_sku") || jQuery(elem).attr("data-unbxdParam_pid");
            var qty = jQuery(elem).attr("data-unbxdParam_qty") || jQuery(elem).attr("data-unbxdParam_qty");
            var price = jQuery(elem).attr("data-unbxdParam_price") || jQuery(elem).attr("data-unbxdParam_price");
            Unbxd.addOrder(pid, qty, price);
        });
    }

    // Requires jQuery
    var init = function(){
        if(Unbxd.readCookie('info') === '1'){
            debugInfo();  // Requires jQuery
        }

        Unbxd.bootState = 3; //

        attachListeners(); // Requires jQuery

        Unbxd.bootState = 4; // Listeners attached. Complete

        Unbxd.gatherImpressions(); // Requires jQuery

        setInterval(function(){
            Unbxd.gatherImpressions(); // Requires jQuery
        }, 1000);
    }

    // ========================================================
    // Start booting

    if(Unbxd.bootState){
        Unbxd.log("Already initialized");
        return;
    }

    Unbxd.bootState = 1; // Just inside init

    /**
     * Init.
     */
    Unbxd.init();

    Unbxd.bootState = 2; // Cookies initialized

    // ===========================================================
    // Do Stuff which requires jQuery

    var fn = function(){
        if(typeof(jQuery) != 'undefined'){
            init();
        }else{
            setTimeout(function(){
                fn();
            }, 100);
        }
    }

    fn();

    // ============================================================
    // Do stuff which required full page load

    var runPostLoad = function(){
        Unbxd.log("Running post load");

        // Post load hooks. Some functionalities depend on this. Don't move.
        try{
            if(Unbxd.postLoad != undefined && typeof(Unbxd.postLoad) == 'function'){
                Unbxd.postLoad();
            }
        }catch(e){
            Unbxd.log(e);
        }
    }

    if(document && document.readyState && document.readyState === "complete"){
        runPostLoad();
    }else if (window.addEventListener) {
        window.addEventListener('load', runPostLoad, false);   // modern browsers
    } else if (window.attachEvent) {
        window.attachEvent("onload", runPostLoad);          // older versions of IE
    } else {
        // What the heck. We won't support it. Abort.
    }
});