
// create a data service that provides a store and a shopping cart that
// will be shared by all views (instead of creating fresh ones for each view).
tekapoAngularApp.factory("DataService", function () {

    // create shopping cart
    var myCart = new shoppingCart("TekapoCart");

    // enable PayPal checkout
    // note: the second parameter identifies the merchant; in order to use the
    // shopping cart with PayPal, you have to create a merchant account with
    // PayPal. You can do that here:
    // https://www.paypal.com/webapps/mpp/merchant
    //myCart.addCheckoutParameters("PayPal", "bernardo.castilho-facilitator@gmail.com");

    // enable Google Wallet checkout
    // note: the second parameter identifies the merchant; in order to use the
    // shopping cart with Google Wallet, you have to create a merchant account with
    // Google. You can do that here:
    // https://developers.google.com/commerce/wallet/digital/training/getting-started/merchant-setup
    /*
     myCart.addCheckoutParameters("Google", "500640663394527",
     {
     ship_method_name_1: "UPS Next Day Air",
     ship_method_price_1: "20.00",
     ship_method_currency_1: "USD",
     ship_method_name_2: "UPS Ground",
     ship_method_price_2: "15.00",
     ship_method_currency_2: "USD"
     }
     );
     */
    // return data object with store and cart
    return {
        //store: myStore,
        term: '',
        showSearchBar: false,
        cart: myCart
    };
});

tekapoAngularApp.factory("DisplayFlagsService", function () {
    return {
        showSearchBar: false
    };
});

/**
 * The search service maintains state of the currently searched term
 */
tekapoAngularApp.factory('GeoService', ['$http', function (http) {
    return {
        'countries': function () {
            return http.get("/ajaxgeo?q=countries").then(function (response) {
                return response.data;
            });
        },
        'cities': function (countryId) {
            return http.get("/ajaxgeo?q=cities&cid=" + countryId).then(function (response) {
                return response.data;
            });
        },
        'residence_types': function (countryId) {
            return http.get("/ajaxgeo?q=residence_types").then(function (response) {
                return response.data;
            });
        }
    }
}]);

/**
 * The search service maintains state of the currently searched term
 */
tekapoAngularApp.factory('AddressService', ['$http', function (http) {
    return {
        'addresses': function () {
            return http.get("/users/ajaxgetaddresses").then(function (response) {
                return response.data.result;
            });
        },
        'address': function (id) {
            return http.get("/users/ajaxgetaddresses?id=" + id).then(function (response) {
                return response.data.result;
            });
        },
        'makeDefault': function (id) {
            return http.post("/users/ajaxmakedefaultaddress", {id: id}).then(function (response) {
                return response.data.status == 'success';
            });
        },
        'deleteAddress': function (id) {
            return http.post("/users/ajaxdeleteaddress", {id: id}).then(function (response) {
                return response.data.status == 'success';
            });
        }
    }
}]);

/**
 * User service
 */
tekapoAngularApp.factory('UserService', ['$http', function (http) {
    return {
        'emailsForUser': function () {
            return http.get("/users/ajaxgetemails").then(function (response) {
                return response.data.result;
            });
        },
        'email': function (id) {
            return http.get("/users/ajaxgetemail?id="+id).then(function (response) {
                return response.data.result;
            });
        }
    }
}]);
