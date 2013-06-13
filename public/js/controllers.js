tekapoAngularApp.controller('ForgotPasswordModal', ['$scope', '$http', function (scope, http) {

    scope.success = null;
    scope.message = "";
    scope.opts = {
        backdropFade: false,
        dialogFade: false
    };

    scope.open = function () {
        scope.shouldBeOpen = true;
    };

    scope.close = function () {
        scope.shouldBeOpen = false;
    };

    scope.send = function () {
        return http.post('/users/forgotpass/email/' + scope.forgot_pass_email).then(function (response) {
            scope.message = response.data.message;
            scope.success = response.data.status != "error";
        });
    }
}]);


/**
 * Main App Controller that holds all others
 */
tekapoAngularApp.controller('AppCtrl', [
    '$scope',
    '$location',
    '$routeParams',
    '$http',

    'DataService',
    'DisplayFlagsService',

    function (scope, loc, rp, http, ds, dfs) {

        scope.isCollapsed = false;

        scope.doSearch = function (q) {
            if (q != '') {
                loc.path('/q/' + q);
            }
        }

        scope.DataService = ds;
        scope.DisplayFlags = dfs;
        scope.cart = ds.cart;

        if (loc.path() == '/cart' || rp.searchTerm != undefined) {
            dfs.showSearchBar = true;
        }

        if (rp.searchTerm != undefined) {
            ds.term = rp.searchTerm;
            scope.products = http.get('/search/main?q=' + ds.term).then(function (response) {
                return response.data.result;
            });
        }

        scope.resendActivationEmail = function (id) {
            http.post('/ajaxuserbase/resendactivation', {id: id}).then(function (response) {
                if (response.data.status == 'success') {
                    alert("Successfully resent activation email. Give it a couple minutes if it hasn't arrived yet, and check your spambox.");
                    var key = "hidden" + id;
                    scope[key] = true;
                } else {
                    alert("Unable to send activation email. Please contact support.");
                }
            });
        }

        scope.imageModalOpen = function (imagePath) {
            scope.shouldBeOpen = true;
            if (imagePath != undefined) {
                scope.imagePath = imagePath;
            }
        };

        scope.imageModalClose = function () {
            scope.shouldBeOpen = false;
            scope.imagePath = '';
        };

        scope.deleteImage = function (type, hash, id) {
            if (confirm('Are you sure? The image is permanently removed from the server!')) {
                http.post('/ajaximage/deleteimage', {type: type, hash: hash, id: id}).then(function (response) {
                    if (response.data.status == 'success') {
                        removeElementById(hash);
                    } else {
                        alert(response.data.message);
                    }
                });
            }
        }

        // use routing to pick the selected product
        /*
         if (routeParams.productSku != null) {
         scope.product = scope.store.getProduct(routeParams.productSku);
         }*/

    }]);

tekapoAngularApp.controller('EmailsController', ['$scope', 'UserService', function (scope, userService) {

    scope.showNewEmail = false;
    scope.newEmail = function () {
        scope.showNewEmail = true;
    }
    scope.cancelNewEmail = function () {
        scope.showNewEmail = false;
    }

    scope.reload = function () {
        scope.emailsForUser = userService.emailsForUser();
    }
    scope.makeDefault = function (id) {
        if (userService.makeEmailDefault(id)) {
            scope.reload();
        } else {
            alert("There was an error! Please contact support with following message: error_saving_contact_default");
        }
    }
    scope.delete = function (id) {
        var message = "Are you sure you want to delete this address? This action cannot be undone!";
        if (confirm(message)) {
            if (userService.deleteEmail(id)) {
                removeElementById("contactentry" + id);
                scope.reload();
            } else {
                alert("There was an error! Please contact support with following message: error_deleting_contact_" + id);
            }
        }
    }
    /*
     scope.showNewAddress = false;
     scope.newAddress = function () {
     scope.showNewAddress = true;
     }
     scope.cancelNewAddress = function () {
     scope.showNewAddress = false;
     }*/
    scope.reload();
}]);

tekapoAngularApp.controller('AddressesGridCtrl', ['$scope', 'GeoService', 'AddressService', function (scope, gs, as) {
    scope.addressesForUser = as.addresses();
    scope.reloadAddresses = function () {
        scope.addressesForUser = as.addresses();
    }
    scope.makeDefault = function (id) {
        if (as.makeDefault(id)) {
            scope.addressesForUser = as.addresses();
        } else {
            alert("There was an error! Please contact support with following message: error_saving_addresses_default");
        }
    }
    scope.deleteAddress = function (id) {
        var message = "Are you sure you want to delete this address? This action cannot be undone!";
        if (confirm(message)) {
            if (as.deleteAddress(id)) {
                removeElementById("addressCard" + id);
                scope.addressesForUser = as.addresses();
            } else {
                alert("There was an error! Please contact support with following message: error_deleting_addresses_" + id);
            }
        }
    }
    scope.showNewAddress = false;
    scope.newAddress = function () {
        scope.showNewAddress = true;
    }
    scope.cancelNewAddress = function () {
        scope.showNewAddress = false;
    }

}]);