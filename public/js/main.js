var tekapoAngularApp = angular.module('tekapoAngular', ['ui.bootstrap']);

/**
 * Define routes
 * */
tekapoAngularApp.config(['$routeProvider', '$locationProvider', function (routeProvider, locationProvider) {
    routeProvider
        .when('/',
        {
            templateUrl: 'root.html',
            controller: 'AppCtrl'
        })
        .when('/q/:searchTerm',
        {
            templateUrl: 'results.html',
            controller: 'AppCtrl'
        })
        .when('/cart',
        {
            templateUrl: 'shoppingcarttpl.html',
            controller: 'AppCtrl'
        })
        .otherwise({
            template: '&nbsp;',
            controller: function (l, w) {
                w.location.href = l.path();
            },
            resolve: {
                'l': '$location',
                'w': '$window'
            }
        })
    ;
    //locationProvider.html5Mode(true).hashPrefix('!');
}]);
