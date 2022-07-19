define(["jquery"], function (t) {
        "use strict";
        var c = function () {
            this.tvc_autoevents = function (domain){         

//Newsman remarketing auto events REPLACEABLE

var ajaxurl = 'https://' + document.location.hostname + '/newsman/index/index?newsman=getCart.json';

//Newsman remarketing auto events REPLACEABLE

//Newsman remarketing auto events

var isProd = true;

let lastCart = sessionStorage.getItem('lastCart');
if (lastCart === null)
    lastCart = {};

var lastCartFlag = false;
var firstLoad = true;
var bufferedXHR = false;
var unlockClearCart = true;
var isError = false;
var documentComparer = document.location.hostname;
var documentUrl = document.URL;
var sameOrigin = (documentUrl.indexOf(documentComparer) !== -1);

let startTime, endTime;

function startTimePassed() {
    startTime = new Date();
};

startTimePassed();

function endTimePassed() {
    var flag = false;

    endTime = new Date();
    var timeDiff = endTime - startTime;

    timeDiff /= 1000;

    var seconds = Math.round(timeDiff);

    if (firstLoad)
        flag = true;

    if (seconds >= 5)
        flag = true;

    return flag;
}

if (sameOrigin) {
    NewsmanAutoEvents();
    setInterval(NewsmanAutoEvents, 5000);

    detectXHR();
}

function NewsmanAutoEvents() {

    if (!endTimePassed())
        return;

    if (isError && isProd == true) {
        console.log('newsman remarketing: an error occurred, set isProd = false in console, script execution stopped;');

        return;
    }

    let xhr = new XMLHttpRequest()

    if (bufferedXHR || firstLoad) {

        var timestamp = "?t=" + Date.now();

        xhr.open('GET', ajaxurl + timestamp, true);

        startTimePassed();

        xhr.onload = function() {

            if (xhr.status == 200 || xhr.status == 201) {

                try {
                    var response = JSON.parse(xhr.responseText);
                } catch (error) {
                    if (!isProd)
                        console.log('newsman remarketing: error occured json parsing response');

                    isError = true;

                    return;
                }

                lastCart = JSON.parse(sessionStorage.getItem('lastCart'));

                if (lastCart === null)
                    lastCart = {};

                //check cache
                if (lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined) {
                    if (JSON.stringify(lastCart) === JSON.stringify(response)) {
                        if (!isProd)
                            console.log('newsman remarketing: cache loaded, cart is unchanged');

                        lastCartFlag = true;
                    } else {
                        lastCartFlag = false;

                        if (!isProd)
                            console.log('newsman remarketing: cache loaded, cart is changed');
                    }
                }

                if (response.length > 0 && lastCartFlag == false) {

                    addToCart(response);

                }
                //send only when on last request, products existed
                else if (response.length == 0 && lastCart.length > 0 && unlockClearCart) {

                    clearCart();

                    if (!isProd)
                        console.log('newsman remarketing: clear cart sent');

                } else {

                    if (!isProd)
                        console.log('newsman remarketing: request not sent');

                }

                firstLoad = false;
                bufferedXHR = false;

            } else {
                if (!isProd)
                    console.log('newsman remarketing: response http status code is not 200');

                isError = true;
            }

        }

        xhr.send(null);

    } else {
        if (!isProd)
            console.log('newsman remarketing: !buffered xhr || first load');
    }

}

function clearCart() {

    _nzm.run('ec:setAction', 'clear_cart');
    _nzm.run('send', 'event', 'detail view', 'click', 'clearCart');

    sessionStorage.setItem('lastCart', JSON.stringify([]));

    unlockClearCart = false;

}

function addToCart(response) {

    _nzm.run('ec:setAction', 'clear_cart');
    _nzm.run('send', 'event', 'detail view', 'click', 'clearCart', null, _nzm.createFunctionWithTimeout(function() {

        for (var item in response) {

            _nzm.run('ec:addProduct',
                response[item]
            );

        }

        _nzm.run('ec:setAction', 'add');
        _nzm.run('send', 'event', 'UX', 'click', 'add to cart');

        sessionStorage.setItem('lastCart', JSON.stringify(response));
        unlockClearCart = true;

        if (!isProd)
            console.log('newsman remarketing: cart sent');

    }));

}

function detectXHR() {

    var proxied = window.XMLHttpRequest.prototype.send;
    window.XMLHttpRequest.prototype.send = function() {

        var pointer = this;
        var validate = false;
        var intervalId = window.setInterval(function() {

            if (pointer.readyState != 4) {
                return;
            }

            var _location = pointer.responseURL;

            //own request exclusion
            if (
                            pointer.responseURL.indexOf('getCart.json') >= 0 ||
                            //magento 2.x
                            pointer.responseURL.indexOf('/static/') >= 0 ||
                            pointer.responseURL.indexOf('/pub/static') >= 0 ||
                            pointer.responseURL.indexOf('/customer/section') >= 0 ||
                            //opencart 1
                            pointer.responseURL.indexOf('getCart=true') >= 0
            ) {
                validate = false;
            } else {
                if (_location.indexOf(window.location.origin) !== -1)
                    validate = true;
            }

            if (validate) {
                bufferedXHR = true;

                if (!isProd)
                    console.log('newsman remarketing: ajax request fired and catched from same domain');

                NewsmanAutoEvents();
            }

            clearInterval(intervalId);

        }, 1);

        return proxied.apply(this, [].slice.call(arguments));
    };

}

//Newsman remarketing auto events
            },
            this.tvc_get_impression = function (t) {
                var c = 0, e = Object.keys(t).length;
                for (var n in t) {
                    c++;
                    t[n].manufacturer_name;
           
                    _nzm.run('ec:addImpression', {
                        'id': t[n].tvc_i,
                        'name': t[n].tvc_name,
                        'category': t[n].tvc_c,
                        'price': t[n].tvc_p,
                        list: "Category Page",
                        position: parseInt(n) + 1
                    });

                }

                _nzm.run('send', 'pageview', jQuery(location).attr('pathname'), 'pageview', 'Category Page');

            }, this.tvc_impr_click = function (t) {              
            }, this.tvc_identify = function (t) {                              
            }, this.tvc_pro_detail = function (t) {
                
                _nzm.run('ec:addProduct', {
                    'id': t.tvc_i,
                    'name': t.tvc_name,
                    'category': t.tvc_c,
                    price: t.tvc_p,
                    list: "Product Page",
                });

                _nzm.run('ec:setAction', 'detail');
                _nzm.run('send', 'pageview');

            }, this.tvc_add_to_cart = function (t) {                
            }, this.tvc_remove_cart = function (t) {                
            }, this.tvc_transaction_call = function (t, c) {
                for (var e in t)_nzm.run("ec:addProduct", {
                    id: t[e].tvc_i,
                    name: t[e].tvc_name,
                    category: t[e].tvc_c,
                    price: t[e].tvc_p,
                    quantity: t[e].tvc_Qty
                });               

                _nzm.run('ec:setAction', 'purchase', {
                    'id': c.tvc_id,
                    'affiliation': c.tvc_affiliate,
                    'revenue': c.tvc_revenue,
                    'tax': c.tvc_tt,
                    'shipping': c.tvc_ts
                });

		        _nzm.run('send', 'pageview');
            }, this.add_product_checkout = function (t) {               
            }
        };
        return c
    }
);