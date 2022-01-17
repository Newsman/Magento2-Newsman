define(["jquery"], function (t) {
        "use strict";
        var c = function () {
            this.tvc_autoevents = function (t){

            _nzm.run('require', 'ec');

            var isProd = false;
            let lastCart = sessionStorage.getItem('lastCart');		

            if(lastCart === null)
                lastCart = {};	

            let lastCartFlag = false;
            let bufferedClick = false;
            let firstLoad = true;

            NewsmanAutoEvents();	
            setInterval(NewsmanAutoEvents, 5000);
            
            BufferClick();

            function NewsmanAutoEvents(){		
                var ajaxurl = '/newsman/index/index?newsman=getCart.json';
                if(bufferedClick || firstLoad)
                {
                    jQuery.post(ajaxurl, {  
                    post: true,
                    }, function (response) {				
                        lastCart = JSON.parse(sessionStorage.getItem('lastCart'));						
                        if(lastCart === null)
                            lastCart = {};	
                        //check cache
                        if(lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined)
                        {				
                            if(JSON.stringify(lastCart) === JSON.stringify(response))
                            {
                                if(!isProd)
                                    console.log('newsman remarketing: cache loaded, cart is unchanged');
                                lastCartFlag = true;					
                            }
                            else{
                                lastCartFlag = false;
                            }
                        }			
                        if(response.length > 0 && lastCartFlag == false)
                        {
                            _nzm.run('ec:setAction', 'clear_cart');
                            _nzm.run('send', 'event', 'detail view', 'click', 'clearCart');	
                            for (var item in response) {				
                                _nzm.run( 'ec:addProduct', 
                                    response[item]
                                );				
                            }	
                            
                            _nzm.run( 'ec:setAction', 'add' );
                            _nzm.run( 'send', 'event', 'UX', 'click', 'add to cart' );
                            sessionStorage.setItem('lastCart', JSON.stringify(response));					
                            if(!isProd)
                                console.log('newsman remarketing: cart sent');				
                        }
                        else{
                            if(!isProd)
                                console.log('newsman remarketing: request not sent');
                        }
                        firstLoad = false;
                        bufferedClick = false;
                        
                    });
                }
            }

            function BufferClick(){
                window.onclick = function (e) {
                    const origin = ['a', 'input', 'span', 'i', 'button'];
        
                    var click = e.target.localName;			
                    if(!isProd)
                        console.log('newsman remarketing element clicked: ' + click);
                    for (const element of origin) {
                        if(click == element)
                            bufferedClick = true;
                    }
                }
            }

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
                jQuery(".product-item-photo, .product-item-link").on("click", function () {
                    var c = jQuery(this).siblings().find(".price-final_price").attr("data-product-id");
                    for (var e in t) {
     
                        _nzm.run('ec:addProduct', {
                            'id': t[e].tvc_i,
                            'name': t[e].tvc_name,
                            'category': t[e].tvc_c,
                            'position': parseInt(e)
                        });
                    }

                    _nzm.run('send', 'event', 'UX', 'click', 'view product');
                })
            }, this.tvc_identify = function (t) {
               
                function wait_to_load_and_identify() {
                    if (typeof _nzm.get_tracking_id === 'function') {
                        if (_nzm.get_tracking_id() == '') {
                            _nzm.identify({email: "", first_name: "", last_name: ""});
                        }
                    } else {
                        setTimeout(function () {
                            wait_to_load_and_identify()
                        }, 50)
                    }
                }

                wait_to_load_and_identify();

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
                /*Obsolete
                jQuery("#product-addtocart-button").on("click", function () {

                    var variationBool = false;
                    var variationCount = false;	

                    jQuery('#product_addtocart_form input[type=radio]').each(function(element, item) {
                        variationCount = true;
                        if(jQuery(this).is(':checked'))
                        {
                            variationBool = true;
                        }

                    });
                
                    if(variationCount == true)
                    {
                        if(variationBool == false)
                        {		
                            return;
                        }		
                    }

                    _nzm.run('ec:addProduct', {
                        id: t.tvc_i,
                        name: t.tvc_name,
                        category: t.tvc_c,
                        price: t.tvc_p,
                        quantity: jQuery("#qty").val()
                    });

                    _nzm.run('ec:setAction', 'add');
                    _nzm.run('send', 'event', 'UX', 'click', 'add to cart');
                    _nzm.run('send', 'pageview');            
                })
                */
            }, this.tvc_remove_cart = function (t) {
                /*
                jQuery(".action-delete").on("click", function () {
                    var c = jQuery(this).attr("data-post");
                    c = jQuery.parseJSON(c);
                    var e = jQuery(".input-text.qty").val();

                    for (var n in t) {

                        _nzm.run('ec:addProduct', {
                            'id': t[n].tvc_i,
                            'quantity': e
                        });
                    }

                    _nzm.run('ec:setAction', 'remove');
                    _nzm.run('send', 'event', 'UX', 'click', 'remove from cart');
	            _nzm.run('send', 'pageview');
                })
                */
            }, this.tvc_transaction_call = function (t, c) {
                for (var e in t)_nzm.run("ec:addProduct", {
                    id: t[e].tvc_i,
                    name: t[e].tvc_name,
                    category: t[e].tvc_c,
                    price: t[e].tvc_p,
                    quantity: t[e].tvc_Qty
                });               

                _nzm.run('ec:setAction', 'purchase', {
                    'id': c.tvc_i,
                    'affiliation': c.tvc_affiliate,
                    'revenue': c.tvc_revenue,
                    'tax': c.tvc_tt,
                    'shipping': c.tvc_ts
                });
		_nzm.run('send', 'pageview');
            }, this.add_product_checkout = function (t) {

                for (var c in t) {
                    _nzm.run("ec:addProduct", {
                        id: t[c].tvc_i,
                        name: t[c].tvc_name,
                        category: t[c].tvc_c,
                        price: t[c].tvc_p,
                        quantity: t[c].tvc_qty
                    });
                }

                _nzm.run('ec:setAction', 'checkout');
                _nzm.run('send', 'pageview');

            }
        };
        return c
    }
);