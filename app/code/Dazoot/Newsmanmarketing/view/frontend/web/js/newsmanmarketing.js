define(["jquery"], function (t) {
        "use strict";
        var c = function () {
            this.tvc_autoevents = function (t){

            _nzm.run('require', 'ec');            

            var isProd = true;

            let lastCart = sessionStorage.getItem('lastCart');			
            if(lastCart === null)
                lastCart = {};			
    
            var lastCartFlag = false;
            var bufferedClick = false;
            var firstLoad = true;
            var bufferedXHR = false;
            var ajaxurl = '/newsman/index/index?newsman=getCart.json';	
                        
            NewsmanAutoEvents();
            setInterval(NewsmanAutoEvents, 5000);		
    
            detectXHR();
    
            function NewsmanAutoEvents(){							
    
                if(bufferedXHR || firstLoad)
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
                            _nzm.run( 'send', 'event', 'detail view', 'click', 'clearCart', null, _nzm.createFunctionWithTimeout(function() {												
    
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
    
                            }));										
    
                        }
                        else{
    
                            if(!isProd)
                                console.log('newsman remarketing: request not sent');
    
                        }
    
                        firstLoad = false;				
                        bufferedXHR = false;
                        
                    });
    
                }
    
            }
    
            function detectXHR() {	
                
                var proxied = window.XMLHttpRequest.prototype.send;
                window.XMLHttpRequest.prototype.send = function() {		
    
                    var pointer = this;
                    var validate = true;
                    var intervalId = window.setInterval(function(){
    
                            if(pointer.readyState != 4){
                                    return;
                            }
                            
                            var _location = pointer.getResponseHeader('access-control-allow-origin');				

                            if(pointer.responseURL.indexOf('getCart.json') >= 0 || pointer.responseURL.indexOf('/pub/static') >= 0 || pointer.responseURL.indexOf('/customer/section') >= 0)
                            {							
                                validate = false;												
                            }
                            else{
                                validate = true;
                            }
    
                            if(validate && !_location || _location == window.location.origin)
                            {
                                console.log(pointer.responseURL);

                                bufferedXHR = true;
    
                                if(!isProd)
                                    console.log('newsman remarketing: ajax request fired and catched from same domain');
    
                                NewsmanAutoEvents();
                            }
                
                            clearInterval(intervalId);
            
                    }, 1);
    
                    return proxied.apply(this, [].slice.call(arguments));
                };	
    
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