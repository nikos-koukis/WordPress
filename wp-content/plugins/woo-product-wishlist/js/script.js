jQuery(document).ready(function ($) {
   
    $('.wishlist').click(function () {
     
        var self = $(this);
        var admin_url = $(this).attr('data-url');
        var login_url = $('.login-page-url').text();

        
        var product_id = $(this).attr('data-product-id');
        
        if (self.hasClass('active')) {
            $.ajax({
                type: "POST", // use $_POST method to submit data
                url: admin_url, // where to submit the data
                data: {
                    action: 'aceww_remove_wishlist',
                    product_id: product_id
                },
                success: function (data) {
                    self.removeClass('active');
                    $.ajax({
                        type: "POST", // use $_POST method to submit data
                        url: admin_url, // where to submit the data
                        data: {
                            action: 'aceww_update_menu_item'
                        },
                        success: function (data) {
                            // console.log(data);
                            var data = data.substring(0, data.length - 1);
                            $('#site-navigation').find('.menu-item-wishlist .dropdown-content table').html(data);
                           // $('#site-navigation').append(data);
                        }
                    });
                },
                error: function (errorThrown) {
                    // console.log(errorThrown); // error
                }
            });
        }

        else {
            $.ajax({
                type: "POST", // use $_POST method to submit data
                url: admin_url, // where to submit the data
                data: {
                    action: 'aceww_add_wishlist',
                    product_id: product_id
                },
                success: function (data) {
                    
                    if (data == 0) {
                        window.location.replace(login_url);
                    }
                    else {
                        self.addClass('active');
                        
                        $.ajax({
                            type: "POST", // use $_POST method to submit data
                            url: admin_url, // where to submit the data
                            data: {
                                action: 'aceww_update_menu_item'
                            },
                            success: function (data) {
                                // console.log(data);
                                var data = data.substring(0, data.length - 1);
                                $('#site-navigation').find('.menu-item-wishlist .dropdown-content table').html(data);
                            }
                        });
                    }                        
                },
                error: function (errorThrown) {
                    // console.log(errorThrown); // error
                }
            });
        }

    });
    $(document).on('click', '.cart_check', function () {
       
        var admin_url = $('.admin-url').text();
       
        jQuery.ajax({
            type: "POST", // use $_POST method to submit data
            url: admin_url, // where to submit the data
            data: {
                action: 'aceww_cart_checkbox'
            },
            success: function (data) {
                console.log(data);
                jQuery.ajax({
                    type: "POST", // use $_POST method to submit data
                    url: admin_url, // where to submit the data
                    data: {
                        action: 'aceww_wishlist_frontend_content'
                    },
                    success: function (data) {
                        jQuery('.woocommerce-MyAccount-content').html(data);
                    }
                });

            },
            error: function (errorThrown) {
                // console.log(errorThrown); // error
            }
        });

    });
    $(document).on('click', '.add-to-cart', function (e) {
        e.preventDefault();
       
        var self = $(this);
        var url = self.attr('href');
        console.log(url);
        var product_id = self.attr('data-product_id');
        if ($('.cart_check').is(':checked') == false) {
            remove_product_from_wishlist(product_id,url);
        }
        else{
            window.location.replace(url);
        }

    });


    function remove_product_from_wishlist(product_id,url=null) {
        var admin_url = $('.admin-url').text();
        jQuery.ajax({
            type: "POST", // use $_POST method to submit data
            url: admin_url, // where to submit the data
            data: {
                action: 'aceww_remove_wishlist',
                product_id: product_id
            },
            success: function (data) {
                console.log(data);
                // self.removeClass('active')
                if(url){
                    window.location.replace(url);
                }
                jQuery.ajax({
                    type: "POST", // use $_POST method to submit data
                    url: admin_url, // where to submit the data
                    data: {
                        action: 'aceww_wishlist_frontend_content'
                    },
                    success: function (data) {
                        jQuery('.woocommerce-MyAccount-content').html(data);
                        window.location.replace(url);
                    }
                });
                // ;
                jQuery.ajax({
                    type: "POST", // use $_POST method to submit data
                    url: admin_url, // where to submit the data
                    data: {
                        action: 'aceww_update_menu_item'
                    },
                    success: function (data) {
                        // console.log(data);
                        var data = data.substring(0, data.length - 1);
                        $('#site-navigation').find('.menu-item-wishlist .dropdown-content table').html(data);
                    }
                });
            },

        });
    }

    $(document).on('click', '.single_remove', function () {
        // alert('hii');
        var self = $(this);
        var product_id = self.attr('data-product_id');
        remove_product_from_wishlist(product_id);

    });

    $(document).on('click', '.remove-all', function () {
        // alert('hii');
        var admin_url = $('.admin-url').text();
        $.ajax({
            type: "POST", // use $_POST method to submit data
            url: admin_url, // where to submit the data
            data: {
                action: 'aceww_remove_all'
            },
            success: function (data) {
                console.log(data);
                // self.removeClass('active');
                jQuery.ajax({
                    type: "POST", // use $_POST method to submit data
                    url: admin_url, // where to submit the data
                    data: {
                        action: 'aceww_wishlist_frontend_content'
                    },
                    success: function (data) {
                        jQuery('.woocommerce-MyAccount-content').html(data);
                    }
                });
                $.ajax({
                    type: "POST", // use $_POST method to submit data
                    url: admin_url, // where to submit the data
                    data: {
                        action: 'aceww_update_menu_item'
                    },
                    success: function (data) {
                        //console.log(data);
                        var data = data.substring(0, data.length - 1);
                        $('#site-navigation').find('.menu-item-wishlist .dropdown-content table').html(data);
                    }
                });
            },
            error: function (errorThrown) {
                // console.log(errorThrown); // error
            }
        });
    });

    $(document).on('click', '.get_notification', function () {
        // alert('hii');
        var admin_url = $('.admin-url').text();
        $.ajax({
            type: "POST", // use $_POST method to submit data
            url: admin_url, // where to submit the data
            data: {
                action: 'aceww_get_notification'
            },
            success: function (data) {
                console.log(data);
                jQuery.ajax({
                    type: "POST", // use $_POST method to submit data
                    url: admin_url, // where to submit the data
                    data: {
                        action: 'aceww_wishlist_frontend_content'
                    },
                    success: function (data) {
                        jQuery('.woocommerce-MyAccount-content').html(data);
                    }
                });
            },
            error: function (errorThrown) {
                // console.log(errorThrown); // error
            }
        });
    });
});



