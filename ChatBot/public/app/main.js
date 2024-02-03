
/* Adult App */
var BotApp = angular.module("BotApp", [
    "ui.router",
    "ui.bootstrap",
    "oc.lazyLoad",
    "ngCookies",
    'ngStorage',
    'ngSanitize',
    'ngDialog'
]);
BotApp.constant('API_URL', 'http://192.168.2.7');
/* Configure ocLazyLoader(refer: https://github.com/ocombe/ocLazyLoad) */
BotApp.config(['$ocLazyLoadProvider', function ($ocLazyLoadProvider) {
    $ocLazyLoadProvider.config({
        // global configs go here
    });
}]);

//AngularJS v1.3.x workaround for old style controller declarition in HTML
BotApp.config(['$controllerProvider', function ($controllerProvider) {
    // this option might be handy for migrating old apps, but please don't use it
    // in new ones!
    $controllerProvider.allowGlobals();
}]);

BotApp.factory('Auth', ['$http', '$localStorage', 'API_URL', '$cookies', '$rootScope', function ($http, $localStorage, API_URL, $cookies, $rootScope) {
    function urlBase64Decode(str) {
        var output = str.replace('-', '+').replace('_', '/');
        switch (output.length % 4) {
            case 0:
                break;
            case 2:
                output += '==';
                break;
            case 3:
                output += '=';
                break;
            default:
                throw 'Illegal base64url string!';
        }
        return window.atob(output);
    }
    function getClaimsFromToken() {
        var token = $localStorage.token;
        var user = {};
        if (typeof token !== 'undefined') {
            var encoded = token.split('.')[1];
            user = JSON.parse(urlBase64Decode(encoded));
        }
        return user;
    }
    var tokenClaims = getClaimsFromToken();
    return {
        signup: function (data, success, error) {
            // $http.post(urls.BASE + '/signup', data).success(success).error(error)
        },
        signin: function (data, success, error) {
            $http.post(API_URL + '/api/main_login', data).success(success).error(error)
        },
        can_signin: function (data, success, error) {
            $http.post(API_URL + '/api/candidate_login', data).success(success).error(error)
        },
        logout: function (success) {
            tokenClaims = {};
            delete $localStorage.token;
            $rootScope.globals = {};
            $cookies.remove('globals');
            location.href = '/';
        },
        getTokenClaims: function () {
            return tokenClaims;
        }
    };


}]);

/* Setup App Main Controller */
BotApp.controller('AppController', ['$scope', '$rootScope', '$http', '$location', 'Auth', function ($scope, $rootScope, $http, $location, Auth) {

    $scope.authed = false;

    $scope.logout = function () {
    }
    $scope.$on('$viewContentLoaded', () => {
    });


    $rootScope.$on('$stateChangeSuccess', function (event) {
        // Layout.setAngularJsSidebarMenuActiveLink('match', null, event.currentScope.$state); // activate selected link in the sidebar menu
    });



}]);

BotApp.controller('FooterController', ['$scope', '$location', '$rootScope', '$state', function ($scope, $location, $rootScope, $state) {
    $scope.$on('$includeContentLoaded', function () {
    });

}]);
/* Setup Rounting For All Pages */
BotApp.config(['$stateProvider', '$urlRouterProvider', '$locationProvider', '$ocLazyLoadProvider', '$httpProvider', function ($stateProvider, $urlRouterProvider, $locationProvider, $ocLazyLoadProvider, $httpProvider) {
    // Redirect any unmatched url

    $urlRouterProvider.otherwise("/dashboard");
    $ocLazyLoadProvider.config({
        debug: true
    });

    $stateProvider
        // Dashboard
        .state('dashboard', {
            url: "/dashboard",
            templateUrl: "js/app/views/dashboard.html",
            data: { pageTitle: 'Dashboard', pageType: 'guest' },
            controller: "DashboardController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'BotApp',
                        files: [
                            'js/app/controllers/DashboardController.js'
                        ]
                    });
                }]
            }
        })
        .state('dashboard.tab-edit', {
            url: "/tab-edit/:tab_id",
            templateUrl: "js/app/views/dashboard-tab-edit.html",
            data: { pageTitle: 'Tab-edit', pageType: 'guest' },
            controller: "DashboardEditTabController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'BotApp',
                        files: [
                            'js/app/controllers/DashboardEditTabController.js'
                        ]
                    });
                }]
            }
        })
        .state('dashboard.tab-create', {
            url: "/tab-create",
            templateUrl: "js/app/views/dashboard-tab-create.html",
            data: { pageTitle: 'Tab-create', pageType: 'guest' },
            controller: "DashboardCreateTabController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'BotApp',
                        files: [
                            'js/app/controllers/DashboardCreateTabController.js'
                        ]
                    });
                }]
            }
        })
}]);
BotApp.directive('pauseOnClose', function () {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            element.on('hidden.bs.modal', function (e) {
                // Find elements by video tag
                var nodesArray = [].slice.call(document.querySelectorAll("video"));
                // Loop through each video element 
                angular.forEach(nodesArray, function (obj) {
                    // Apply pause to the object
                    obj.pause();
                });
            });
        }
    }
});
/* Init global settings and run the app */
BotApp.run(["$rootScope", "$state", "$cookies", "$location", "$http", "$injector", "$localStorage", function ($rootScope, $state, $cookies, $location, $http, $injector, AuthenticationService, $localStorage) {

    $rootScope.$state = $state; // state to be accessed from view

    $rootScope.globals = $cookies.getObject('globals') || {};

    $rootScope.$on('$stateChangeStart', function (event, toState, toParams) {
        var currentState = toState;

    });

    $rootScope.$on('$locationChangeSuccess', (event, newUrl, oldUrl) => {

    });

}]);

BotApp.directive('mainTab', function (API_URL, $state, $rootScope) {
    return {
        restrict: 'E',
        replace: true,
        templateUrl: 'js/app/directives/main-tab.html',
        scope: {
            tab: '=tab'
        },
        controller: function ($scope, $http) {
            $scope.goto_tab = function (index) {
                $scope.selectedIndex = index;
                $state.go('dashboard.tab-edit', { tab_id: index });
            }
        },
        link: function (scope) {

        }
    }
});

BotApp.directive('newTab', function (API_URL, $state, $rootScope) {
    return {
        restrict: 'E',
        replace: true,
        templateUrl: 'js/app/directives/new-tab.html',
        scope: {
        },
        controller: function ($scope, $http) {
            $scope.make_new_tab = function () {
                $state.go('dashboard.tab-create');
            }
        },
        link: function (scope) {

        }
    }
});

BotApp.directive('questionNode', function (API_URL, $state, $sce, $rootScope, $localStorage) {
    return {
        restrict: 'E',
        replace: true,
        templateUrl: 'js/app/directives/questionNode.html',
        scope: {
            node: '=node',
            tab: '=tab',
            key: '=key'
        },
        controller: function ($scope, $http) {
            $rootScope.tabs = JSON.parse($localStorage.tabs);
            $scope.save_content = function () {
                var tab = $rootScope.tabs.find(x => x.index == $scope.tab.index);
                console.log($scope.key);
                let current_node = tab.question_nodes[$scope.key];
                console.log(current_node);
                if (!current_node) {
                    let node = {
                        'key': parseInt(tab.question_nodes.length + 1),
                        'type': $scope.node.type,
                        'content': $scope.node.content,
                    }
                    tab.question_nodes.push(node);
                } else {
                    var idx = tab.question_nodes.indexOf(current_node);
                    tab.question_nodes[idx].content = $scope.node.content;
                }
                $localStorage.tabs = JSON.stringify($rootScope.tabs);
            }

            $scope.remove_node = function (key) {
                // $scope.$evalAsync(($scope) => {
                //     var tab = $rootScope.tabs.find(x => x.index == $scope.tab.index);
                //     var question_node = tab.question_nodes.find(x => x.key == key);
                //     if (question_node) {
                //         tab.question_nodes.splice(tab.question_nodes.indexOf(question_node), 1);
                //         $scope.tab.question_nodes.splice($scope.tab.question_nodes.indexOf(question_node), 1);
                //     }

                //     setTimeout(() => {
                //         $localStorage.tabs = JSON.stringify($rootScope.tabs);
                //     }, 200);
                // })

                $scope.$parent.removeQuestionNode(key);


            }
        },
        link: function (scope) {

        }
    }
});
BotApp.directive('replyNode', function (API_URL, $state, $rootScope, $localStorage) {
    return {
        restrict: 'E',
        replace: true,
        templateUrl: 'js/app/directives/replyNode.html',
        scope: {
            node: '=node',
            tab: '=tab',
            key: '=key'
        },
        controller: function ($scope, $http) {
            $rootScope.tabs = JSON.parse($localStorage.tabs);
            $scope.selectTab = $scope.node.go_to;
            $scope.tabs = JSON.parse($localStorage.tabs);

            $scope.saveContent = function () {
                var tab = $rootScope.tabs.find(x => x.index == $scope.tab.index);
                var tab_index = $rootScope.tabs.indexOf(tab);

                if ($scope.node.content != '') {
                    var created_tab = $rootScope.tabs.find(x => x.key == tab_index + '-' + $scope.key);
                    if (!created_tab) {

                        if ($rootScope.tabs.length) {
                            var idx = parseInt($rootScope.tabs[$rootScope.tabs.length - 1].index) + 1
                        } else {
                            var idx = parseInt($rootScope.tabs.length + 1);
                        }

                        let new_tab = {
                            'title': $scope.node.content,
                            'index': idx,
                            'question_nodes': [
                                {
                                    'type': 0,
                                    'content': $scope.node.content,
                                }
                            ],
                            'key': tab_index + '-' + $scope.key,
                            'reply_nodes': []
                        }
                        $rootScope.tabs.push(new_tab);
                        $scope.tabs = $rootScope.tabs;
                    } else {
                        created_tab.title = $scope.node.content;
                        created_tab.question_nodes[0].content = $scope.node.content;
                    }
                }





                let current_node = tab.reply_nodes[$scope.key];
                if (!current_node) {
                    let node = {
                        'content': $scope.node.content,
                        'go_to': $scope.node.go_to
                    }
                    tab.reply_nodes.push(node);
                } else {
                    var idx = tab.reply_nodes.indexOf(current_node);
                    tab.reply_nodes[idx].content = $scope.node.content;
                }
                $localStorage.tabs = JSON.stringify($rootScope.tabs);
            }

            $scope.changeGo = function () {
                var tab = $rootScope.tabs.find(x => x.index == $scope.tab.index);
                let current_node = tab.reply_nodes[$scope.key];
                if (current_node) {
                    var idx = tab.reply_nodes.indexOf(current_node);
                    tab.reply_nodes[idx].go_to = $scope.selectTab;
                }
                $localStorage.tabs = JSON.stringify($rootScope.tabs);
            }
            $scope.remove_node = function () {
                $scope.node = {};
                var tab = $rootScope.tabs.find(x => x.index == $scope.tab.index);
                tab.reply_nodes.splice($scope.key, 1);
                $localStorage.tabs = JSON.stringify($rootScope.tabs);
                setTimeout(() => {
                    location.reload();
                }, 500);
            }
        },
        link: function (scope) {

        }
    }
});
BotApp.directive('contenteditable', ['$sce', function ($sce) {
    return {
        restrict: 'A', // only activate on element attribute
        require: '?ngModel', // get a hold of NgModelController
        link: function (scope, element, attrs, ngModel) {
            if (!ngModel) return; // do nothing if no ng-model

            // Specify how UI should be updated
            ngModel.$render = function () {
                console.log(ngModel);
                element.html($sce.getTrustedHtml(ngModel.$viewValue || ''));
            };

            // Listen for change events to enable binding
            element.on('blur keyup change', function () {
                scope.$evalAsync(read);
            });
            read(); // initialize

            // Write data to the model
            function read() {
                var html = element.html();
                // When we clear the content editable the browser leaves a <br> behind
                // If strip-br attribute is provided then we strip this out
                if (attrs.stripBr && html === '<br>') {
                    html = '';
                }
                ngModel.$setViewValue(html);
            }
        }
    };
}]);