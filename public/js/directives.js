tekapoAngularApp.directive('error', ['$rootScope', function (rootScope) {
    return {
        restrict: "E",
        template: "<div class='alert alert-error' ng-show='isError'></div>",
        link: function (scope) {
            rootScope.$on('$routeChangeError', function (event, current, previous, rejection) {
                scope.isError = true;
            });
        }
    }
}]);

/**
 * Searchbox directive, including autocomplete to a remote server
 */
tekapoAngularApp.directive('searchbox', ['$http', 'limitToFilter', function (http, limitToFilter) {
    return {
        restrict: 'E',
        templateUrl: 'searchboxtpl.html',
        scope: {
            ac: '@',
            limit: '@',
            ctrlfn: '&'
        },
        link: function (scope, element, attrs) {

            if (attrs.ac != undefined) {
                attrs.ac = attrs.ac.replace(/\/?$/, '/');
                if (attrs.limit == undefined) {
                    attrs.limit = 15;
                }
                scope.autocomplete = function (inputValue) {
                    return http.get(attrs.ac + "?q=" + inputValue).then(function (response) {
                        return limitToFilter(response.data, parseInt(attrs.limit, 10));
                    });
                }
            } else {
                alert("Cannot execute. Every searchbox must have an ac attribute");
            }
        }
    }
}]);

tekapoAngularApp.directive('autoFillableField', function () {
    return {
        restrict: "A",
        require: "?ngModel",
        link: function (scope, element, attrs, ngModel) {
            return false;
            setInterval(function () {
                if (!(element.val() == '' && ngModel.$pristine)) {
                    scope.$apply(function () {
                        ngModel.$setViewValue(element.val());
                    });
                }
            }, 300);
        }
    };
});


/**
 * Address directive, including fetching of cities and countries
 */
tekapoAngularApp.directive('address', ['GeoService', '$http', 'AddressService', function (gs, http, as) {
    return {
        restrict: 'E',
        templateUrl: 'address.html',
        scope: {
            entryid: "="
        },
        transclude: true,
        link: function (scope, element, attrs) {

            scope.emptyForm = {
                first_name: '',
                last_name: '',
                country_id: '',
                city: '',
                street: '',
                phone: '',
                zip: '',
                additional_info: '',
                residence_type: ''
            }

            /*
             attrs.$observe( 'entryid', function ( val ) {
             data = as.address(val);
             console.log(val);
             data.then(function(response){
             scope.newAddressForm = scope.emptyForm = response;
             scope.cities = gs.cities(response.country_id);
             });
             });
             */

            scope.countries = gs.countries();
            scope.residence_types = gs.residence_types();

            if (scope.entryid != undefined && parseInt(scope.entryid, 10) > 0) {
                data = as.address(scope.entryid);
                data.then(function (response) {
                    scope.newAddressForm = response;
                    if (response) {
                        scope.cities = gs.cities(response.country_id);

                        for (var key in response) {
                            scope.emptyForm[key] = response[key];
                        }
                    }

                });
            }

            scope.resetForm = function () {
                for (var key in scope.emptyForm) {
                    scope.newAddressForm[key] = scope.emptyForm[key];
                }
            }

            scope.saveAddress = function () {
                http.post('/users/ajaxsaveaddress', scope.newAddressForm).then(function (response) {
                    if (response.data.status == 'success') {
                        // @todo Try to get rid of $parent
                        scope.$parent.reloadAddresses();
                        scope.$parent.showNewAddress = false;
                        scope.resetForm();
                        scope.editing = false;

                    }
                });
            }
            scope.editAddress = function (bool) {
                scope.editing = bool;
            }
            scope.cancelEditing = function () {
                scope.editAddress(false);
                scope.resetForm();
            }
            scope.$watch('newAddressForm.country_id', function (v) {
                if (v != undefined) {
                    scope.cities = gs.cities(v);
                }
            });
        }
    }
}]);

tekapoAngularApp.directive('onKeyup', function () {
    var keys = {
        'backspace': 8,
        'tab': 9,
        'enter': 13,
        'esc': 27,
        'space': 32,
        'pageup': 33,
        'pagedown': 34,
        'end': 35,
        'home': 36,
        'left': 37,
        'up': 38,
        'right': 39,
        'down': 40,
        'insert': 45,
        'delete': 46
    }
    return function (scope, elm, attrs) {
        function applyKeyup() {
            scope.$apply(attrs.do);
        };
        var triggerKeys = scope.$eval(attrs.onKeyup);
        if (triggerKeys == undefined) {
            triggerKeys = [attrs.onKeyup];
        }
        elm.bind("keyup", function (event) {
            if (!triggerKeys || triggerKeys.length == 0) {
                applyKeyup();
            } else {
                angular.forEach(triggerKeys, function (key) {
                    if (key == event.which || keys[key] == event.which) {
                        applyKeyup();
                    }
                });
            }
        });
    };
});


tekapoAngularApp.directive('contactentry', ['$http', function (scope) {
    return {
        restrict: 'E',
        templateUrl: 'contactTpl.html',
        scope: {
            input: "="
        },
        transclude: true,
        link: function (scope, element, attrs) {

            scope.entry = {};

            if (scope.input) {
                scope.entryExists = true;
                for (var key in scope.input) {
                    scope.entry[key] = scope.input[key];
                }
            }

            scope.delete = function() {
                if (confirm('Are you sure? The next contact will automatically become your main, so make sure you have access to it before you do this!')) {
                    scope.$parent.delete(scope.input.id);
                }
            }

            scope.save = function () {

                console.log(scope.entry);

                /*
                 http.post('/users/ajaxsaveaddress', scope.newAddressForm).then(function (response) {
                 if (response.data.status == 'success') {
                 // @todo Try to get rid of $parent
                 scope.$parent.reloadAddresses();
                 scope.$parent.showNewAddress = false;
                 scope.resetForm();
                 scope.editing = false;

                 }
                 });*/
            }

        }
    }
}]);

/*
 tekapoAngularApp.directive('editableList', ['$http', function(scope) {
 return {
 restrict: 'E',
 templateUrl: 'editableList.html',
 scope: {
 entryid: "="
 },
 transclude: true,
 link: function (scope, element, attrs) {

 }
 }
 }]);*/