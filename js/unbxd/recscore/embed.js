if (typeof Unbxd === "undefined")
    window.Unbxd = {};

Unbxd.loadJScript = function(library, url, callback){
    if( typeof window[library] !== "undefined"){
        callback(window[library]);
        return false;
    }

    var script = document.createElement("script")
    script.type = "text/javascript";
    if (script.readyState){  //IE
        script.onreadystatechange = function(){
            if (script.readyState == "loaded" ||
                script.readyState == "complete"){
                script.onreadystatechange = null;
                callback(window[library]);
            }
        };
    } else {  //Others
        script.onload = function(){
            callback(window[library]);
        };
    }
    script.src = url;
    document.getElementsByTagName("head")[0].appendChild(script);
};


var widgetRendrer = function ($) {
    jQuery.noConflict();
    var key = function () {
        if (typeof UnbxdKey != "undefined" && UnbxdKey != "") {
            return UnbxdKey
        }
        if (typeof UnbxdSiteName != "undefined" && UnbxdSiteName != "") {
            return UnbxdSiteName
        }
        return false
    };

    function decode(s) {
        return decodeURIComponent(s.replace(/\+/g, " "))
    }

    function decodeAndParse(s) {
        if (s.indexOf('"') === 0) {
            s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, "\\")
        }
        s = decode(s);
        return s
    }

    var readCookie = function (key) {
        var cookies = document.cookie.split("; ");
        var result = key ? undefined : {};
        for (var i = 0, l = cookies.length; i < l; i++) {
            var parts = cookies[i].split("=");
            var name = decode(parts.shift());
            var cookie = parts.join("=");
            if (key && key === name) {
                try {
                    result = decodeAndParse(cookie);
                    break
                } catch (e) {
                }
            }
        }
        return result
    };
    var widgetsLoaded = false;
    var render = function () {
        if (key() == false)
            return;
        var uid = readCookie("unbxd.userId");
        var unbxd_recommender_url = "//apac-recommendations.unbxdapi.com/";
        if (window.UnbxdMode && window.UnbxdMode == "local") {
            var unbxd_recommender_url = "/"
        }
        var appendCurrency = function (path) {
            if (typeof UnbxdWidgetsConf != "undefined" && UnbxdWidgetsConf.currency) {
                path = path + "&currency=" + UnbxdWidgetsConf.currency
            }
            return path
        };
        var appendUid = function (path) {
            if (uid && uid != "") {
                path = path + "&uid=" + uid
            }
            return path
        };
        var getWidth = function () {
            if (typeof window.innerWidth == "number") {
                return window.innerWidth
            } else if (document.documentElement && document.documentElement.clientWidth) {
                return document.documentElement.clientWidth
            }
            return screen.width
        };
        var load = function (id, path) {
            path = appendCurrency(path);
            path = appendUid(path);
            path = path + "&screenWidth=" + getWidth();
            urlpath = getBaseUrl() + 'unbxd/widget' + path;
            $.ajax({
                async:true,
                url: urlpath,
                type: "GET",
                dataType: "html",
                success: function(data) {
                    $(id).html(data);
                },
                error: function (xhr, status) {
                }
            });
        };
        var tryToRender = function (id, url) {
            if (typeof jQuery != "undefined" && jQuery(id).length > 0) {
                load(id, url);
                widgetsLoaded = true
            } else {
                setTimeout(function () {
                    tryToRender(id, url)
                }, 100)
            }
        };
        if (uid && uid != "") {
            tryToRender("#unbxd_recently_viewed", "?widgetType=recently-viewed&uid=" + uid + "&cont=unbxd_recently_viewed");
            tryToRender("#unbxd_recommended_for_you", "?widgetType=recommend&uid=" + uid + "&cont=unbxd_recommended_for_you");
            tryToRender("#unbxd_cart_recommendations", "?widgetType=cart-recommend&uid" + uid + "&cont=unbxd_cart_recommendations")
        } else {
            tryToRender("#unbxd_recommended_for_you", "?widgetType=recommend&cont=unbxd_recommended_for_you")
        }
        if (typeof UnbxdWidgetsConf != "undefined" && UnbxdWidgetsConf.pid) {
            var pid = UnbxdWidgetsConf.pid;
            tryToRender("#unbxd_also_viewed", "?widgetType=also-viewed&pid=" + pid + "&cont=unbxd_also_viewed");
            tryToRender("#unbxd_also_bought", "?widgetType=also-bought&pid=" + pid + "&cont=unbxd_also_bought");
            tryToRender("#unbxd_more_like_these", "?widgetType=more-like-these&pid=" + pid + "&cont=unbxd_more_like_these");
            tryToRender("#unbxd_pdp_top_sellers", "?widgetType=pdp-top-sellers&pid=" + pid + "&cont=unbxd_pdp_top_sellers")
        }
        tryToRender("#unbxd_top_sellers", "?widgetType=top-sellers&cont=unbxd_top_sellers");
        if (typeof UnbxdWidgetsConf != "undefined" && UnbxdWidgetsConf.category) {
            var category = UnbxdWidgetsConf.category;
            tryToRender("#unbxd_category_top_sellers", "?widgetType=category-top-sellers&category=" + category + "&cont=unbxd_category_top_sellers")
        }
        if (typeof UnbxdWidgetsConf != "undefined" && UnbxdWidgetsConf.brand) {
            var brand = UnbxdWidgetsConf.brand;
            tryToRender("#unbxd_brand_top_sellers", "?widgetType=brand-top-sellers&brand=" + brand + "&cont=unbxd_brand_top_sellers")
        }
    };
    render();
    Unbxd.refreshWidgets = function () {
        if (!widgetsLoaded)
            return;
        render()
    };
    setInterval(function () {
        if (Unbxd.gatherImpressions != undefined && Unbxd.bootState == 4)
            Unbxd.gatherImpressions()
    }, 1e3)
};

function unbxdOnLoad() {
    jQueryBaseUrlElement = document.getElementById('jQueryBaseUrl');
    if(jQueryBaseUrlElement != null) {
        jQueryBaseUrl = jQueryBaseUrlElement.value;
    } else{
        jQueryBaseUrl = "//code.jquery.com/jquery-1.8.0.min.js";
    }
    Unbxd.loadJScript('jQuery', jQueryBaseUrl, widgetRendrer)
}
if (window.addEventListener) {
    window.addEventListener('load', unbxdOnLoad, false);
} else {
    window.attachEvent('onload', unbxdOnLoad);
}