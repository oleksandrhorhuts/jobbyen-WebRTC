
/* Job App */
var AdminApp = angular.module("AdminApp", [
    'ui.select',
    "ui.router",
    "ui.bootstrap",
    'bsLoadingOverlay',
    "oc.lazyLoad",
    "ngCookies",
    'ngStorage',
    'ngSanitize',
    'ngRoute',
    'ngDialog',
    'ui.grid',
    'ui.grid.resizeColumns',
    'ui.grid.selection',
    'ui.grid.pagination',
    'ui.grid.grouping',
    'ui.grid.expandable',
    'ui.grid.edit',
    'ui.grid.rowEdit',
    'ui.grid.cellNav',
    'ui.grid.saveState',
    'ui.grid.autoResize',
    'ui.grid.autoFitColumns',
    'ui.grid.exporter',
    'angularMoment',
    'oitozero.ngSweetAlert',
    'textAngular'
]);
AdminApp.constant('API_URL', '');
AdminApp.config(['$ocLazyLoadProvider', function ($ocLazyLoadProvider) {
    $ocLazyLoadProvider.config({
        // global configs go here
    });
}]);

function LoadInfo() {
    this.delay = 0;
    this.minDuration = 0;
    this.message = 'Indlæser';
    this.backdrop = true;
    this.promise = null;
}
AdminApp.factory('Auth', ['$http', '$localStorage', 'API_URL', '$cookies', '$rootScope', function ($http, $localStorage, API_URL, $cookies, $rootScope) {
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

        },
        signin: function (data, success, error) {
            $http.post(API_URL + '/admin/main_login', data).success(success).error(error)
        },
        main_logout: function (success) {
            tokenClaims = {};
            delete $localStorage.token;
            $rootScope.admin_globals = {};
            $cookies.remove('admin_globals');
            location.href = '/admin';
        },
        getTokenClaims: function () {
            return tokenClaims;
        }
    };


}]);
/* Setup App Main Controller */
AdminApp.controller('AppController', function ($scope, $rootScope, $http, $location, Auth, $window) {
    $scope.authed = false;
    $scope.$on('$viewContentLoaded', () => {
    });
    $rootScope.$on('$stateChangeSuccess', function (event) {
        $window.scrollTo(0, 0);
    });
});

AdminApp.config(['ngDialogProvider', function (ngDialogProvider) {
    ngDialogProvider.setDefaults({
        className: 'ngdialog-theme-default',
        plain: false,
        showClose: true,
        closeByDocument: true,
        closeByEscape: true,
        appendTo: false,
        preCloseCallback: function () {
            console.log('default pre-close callback');
        }
    });
}]);


/* Setup Rounting For All Pages */
AdminApp.config(function ($stateProvider, $urlRouterProvider, $locationProvider, $ocLazyLoadProvider, $httpProvider, $routeProvider) {
    // Redirect any unmatched url

    $urlRouterProvider.otherwise("/login");
    $ocLazyLoadProvider.config({
        debug: true
    });
    $stateProvider
        // Dashboard
        .state('home', {
            url: "/home",
            templateUrl: "admin_js/app/views/home.html",
            data: { pageTitle: 'Home', pageType: 'guest' },
            controller: "HomeController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/HomeController.js'
                        ]
                    });
                }]
            }
        })
        .state('login', {
            url: "/login",
            templateUrl: "admin_js/app/views/login.html",
            data: { pageTitle: 'Login', pageType: 'guest' },
            controller: "LoginController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/LoginController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed', {
            url: "/authed",
            templateUrl: "admin_js/app/views/authed/authed.html",
            data: { pageTitle: 'Authed', pageType: 'authed' },
            controller: "AuthedController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/AuthedController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.dashboard', {
            url: "/dashboard",
            templateUrl: "admin_js/app/views/authed/dashboard.html",
            data: { pageTitle: 'dashboard', pageType: 'authed' },
            controller: "DashboardController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/DashboardController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.jobseeker', {
            url: "/jobseeker",
            templateUrl: "admin_js/app/views/authed/jobseeker/jobseeker.html",
            data: { pageTitle: 'jobseeker', pageType: 'authed' }
        })
        .state('authed.jobseeker.create', {
            url: "/create",
            templateUrl: "admin_js/app/views/authed/jobseeker/create.html",
            data: { pageTitle: 'jobseeker', pageType: 'authed' },
            controller: "JobSeekerCreateController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/jobseeker/JobSeekerCreateController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.jobseeker.list', {
            url: "/list",
            templateUrl: "admin_js/app/views/authed/jobseeker/list.html",
            data: { pageTitle: 'jobseeker', pageType: 'authed' },
            controller: "JobSeekerListController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/jobseeker/JobSeekerListController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.jobseeker.detail', {
            url: "/detail/:user_id",
            templateUrl: "admin_js/app/views/authed/jobseeker/detail.html",
            data: { pageTitle: 'jobseeker', pageType: 'authed' },
            controller: "JobSeekerDetailController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/jobseeker/JobSeekerDetailController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.company', {
            url: "/company",
            templateUrl: "admin_js/app/views/authed/company/company.html",
            data: { pageTitle: 'company', pageType: 'authed' }
        })
        .state('authed.company.create', {
            url: "/create",
            templateUrl: "admin_js/app/views/authed/company/create.html",
            data: { pageTitle: 'company', pageType: 'authed' },
            controller: "CompanyCreateController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/company/CompanyCreateController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.company.list', {
            url: "/list",
            templateUrl: "admin_js/app/views/authed/company/list.html",
            data: { pageTitle: 'company', pageType: 'authed' },
            controller: "CompanyListController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/company/CompanyListController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.company.detail', {
            url: "/detail/:company_id",
            templateUrl: "admin_js/app/views/authed/company/detail.html",
            data: { pageTitle: 'company', pageType: 'authed' },
            controller: "CompanyDetailController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/company/CompanyDetailController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.transaction', {
            url: "/transaction",
            templateUrl: "admin_js/app/views/authed/transaction/transaction.html",
            data: { pageTitle: 'transaction', pageType: 'authed' }
        })
        .state('authed.transaction.employer', {
            url: "/employer",
            templateUrl: "admin_js/app/views/authed/transaction/employer.html",
            data: { pageTitle: 'transaction', pageType: 'authed' },
            controller: "EmployerTransactionController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/transaction/EmployerTransactionController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.transaction.employer-detail', {
            url: "/employer-detail/:trans_id",
            templateUrl: "admin_js/app/views/authed/transaction/employer-detail.html",
            data: { pageTitle: 'transaction', pageType: 'authed' },
            controller: "EmployerTransactionDetailController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/transaction/EmployerTransactionDetailController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.transaction.jobseeker', {
            url: "/jobseeker",
            templateUrl: "admin_js/app/views/authed/transaction/jobseeker.html",
            data: { pageTitle: 'transaction', pageType: 'authed' },
            controller: "JobSeekerTransactionController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/transaction/JobSeekerTransactionController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.transaction.jobseeker-detail', {
            url: "/jobseeker-detail/:trans_id",
            templateUrl: "admin_js/app/views/authed/transaction/jobseeker-detail.html",
            data: { pageTitle: 'transaction', pageType: 'authed' },
            controller: "JobSeekerTransactionDetailController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/transaction/JobSeekerTransactionDetailController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.blog', {
            url: "/blog",
            templateUrl: "admin_js/app/views/authed/blog/blog.html",
            data: { pageTitle: 'blog', pageType: 'authed' }
        })
        .state('authed.blog.create', {
            url: "/create",
            templateUrl: "admin_js/app/views/authed/blog/create.html",
            data: { pageTitle: 'blog', pageType: 'authed' },
            controller: "BlogCreateController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/blog/BlogCreateController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.blog.list', {
            url: "/list",
            templateUrl: "admin_js/app/views/authed/blog/list.html",
            data: { pageTitle: 'blog', pageType: 'authed' },
            controller: "BlogListController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/blog/BlogListController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.blog.detail', {
            url: "/detail/:blog_id",
            templateUrl: "admin_js/app/views/authed/blog/detail.html",
            data: { pageTitle: 'blog', pageType: 'authed' },
            controller: "BlogDetailController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/blog/BlogDetailController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.cronapi', {
            url: "/cronapi",
            templateUrl: "admin_js/app/views/authed/api/cronapi.html",
            data: { pageTitle: 'blog', pageType: 'authed' },
            controller: "CronApiController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/api/CronApiController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.job_categories', {
            url: "/job_categories",
            templateUrl: "admin_js/app/views/authed/extra/job_categories.html",
            data: { pageTitle: 'extra', pageType: 'authed' },
            controller: "JobCategoriesController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/extra/JobCategoriesController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.skills', {
            url: "/skills",
            templateUrl: "admin_js/app/views/authed/extra/skills.html",
            data: { pageTitle: 'extra', pageType: 'authed' },
            controller: "SkillController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/extra/SkillController.js'
                        ]
                    });
                }]
            }
        })
        .state('authed.job_report', {
            url: "/job-report",
            templateUrl: "admin_js/app/views/authed/report/job.html",
            data: { pageTitle: 'report', pageType: 'authed' },
            controller: "JobReportController",
            resolve: {
                deps: ['$ocLazyLoad', function ($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'AdminApp',
                        files: [
                            'admin_js/app/controllers/authed/report/JobReportController.js'
                        ]
                    });
                }]
            }
        })

    $httpProvider.interceptors.push(['$q', '$location', '$localStorage', '$cookies', '$rootScope', function ($q, $location, $localStorage, $cookies, $rootScope) {
        return {
            'request': function (config) {
                config.headers = config.headers || {};
                $rootScope.admin_globals = $cookies.getObject('admin_globals') || {};
                if ($rootScope.admin_globals.adminUser) {
                    let token = $rootScope.admin_globals.adminUser.token;
                    if (token) {
                        config.headers.Authorization = 'Bearer ' + token;
                    }
                }
                return config;
            },
            'responseError': function (response) {
                if (response.status === 401 || response.status === 403) {
                    $rootScope.admin_globals = {};
                    $cookies.remove('admin_globals');
                    $location.path('/login');
                }
                return $q.reject(response);
            }
        };
    }]);
});
AdminApp.directive('pauseOnClose', function () {
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
AdminApp.run(function ($rootScope, $state, $cookies, $location, $http, $injector, $localStorage, bsLoadingOverlayService, $sce) {
    bsLoadingOverlayService.setGlobalConfig({
        templateUrl: $sce.trustAsResourceUrl('admin_js/app/templates/loading-overlay-template.html')
    });
    $rootScope.$state = $state; // state to be accessed from view
    $rootScope.admin_globals = $cookies.getObject('admin_globals') || {};


    $rootScope.$on('$stateChangeStart', function (event, toState, toParams) {
        var currentState = toState;
        $rootScope.admin_globals = $cookies.getObject('admin_globals') || {};
        if ($rootScope.admin_globals.adminUser) {
            let token = $rootScope.admin_globals.adminUser.token;
            if (token == null) {
                if (toState.data.pageType == 'guest') {

                } else {
                    $location.path("/login");
                }
            } else {
            }
        } else {
            if (toState.data.pageType == 'guest') {
            } else {
                $location.path("/login");
            }

        }
    });

    $rootScope.$on('$locationChangeSuccess', (event, newUrl, oldUrl) => {

        let res = newUrl.split("/");
        $rootScope.admin_globals = $cookies.getObject('admin_globals') || {};
        if ($rootScope.admin_globals.adminUser) {
            let token = $rootScope.admin_globals.adminUser.token;
            if (token == null) {
                if (res[res.length - 1] == 'admin' || res[res.length - 2] == 'customer') {

                } else {
                    $state.go("login");
                }
            } else {
            }
        } else {

            if (res[res.length - 1] == 'admin' || res[res.length - 2] == 'customer') {

            } else {
                $state.go("login");
            }

        }


    });

});


AdminApp.directive('ngEnter', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if (event.which === 13) {
                scope.$apply(function () {
                    scope.$eval(attrs.ngEnter, { 'event': event });
                });

                event.preventDefault();
            }
        });
    };
});


AdminApp.directive('decimals', function (API_URL, $state) {
    return {
        restrict: "A",
        require: "?ngModel",
        scope: {
            decimals: "@",
            decimalPoint: "@"
        },
        link: function (scope, element, attr, ngModel) {
            var decimalCount = parseInt(scope.decimals) || 2,
                decimalPoint = scope.decimalPoint || ".";


            ngModel.$render = function () {
                null != ngModel.$modelValue && ngModel.$modelValue >= 0 && ("number" == typeof decimalCount ? element.val(ngModel.$modelValue.toFixed(decimalCount).toString().replace(".", ",")) : element.val(ngModel.$modelValue.toString().replace(".", ",")))
            }, ngModel.$parsers.unshift(function (newValue) {
                if ("number" == typeof decimalCount) {
                    var floatValue = parseFloat(newValue.replace(",", "."));
                    return 0 === decimalCount ? parseInt(floatValue) : parseFloat(floatValue.toFixed(decimalCount))
                }
                return parseFloat(newValue.replace(",", "."))
            }), element.on("change", function (e) {
                var floatValue = parseFloat(element.val().replace(",", "."));
                if (!isNaN(floatValue) && "number" == typeof decimalCount)
                    if (0 === decimalCount) element.val(parseInt(floatValue));
                    else {
                        var strValue = floatValue.toFixed(decimalCount);
                        element.val(strValue.replace(".", decimalPoint))
                    }
            })
        }
    }
});





AdminApp.directive('currency', function (API_URL, $state) {
    function link(scope, element, attrs, ngModelCtrl) {
        scope.currencies = [{
            currencyId: 1,
            name: "DKK",
            code: "DKK",
            symbol: "kr."
        }, {
            currencyId: 2,
            name: "EUR",
            code: "EUR",
            symbol: "€"
        }]
    }
    return {
        restrict: "E",
        replace: !0,
        template: '<div><ui-select ng-model="$parent.ngModel" ng-required="$parent.ngRequired" search-enabled="true" ng-cloak ng-disabled="$parent.ngDisabled" on-select="onSelect({$item: $item, $model: $model})"><ui-select-match placeholder="Ingen valgt">{{$select.selected.name}}</ui-select-match><ui-select-choices repeat="currency in currencies | filter: $select.search"><span ng-bind-html="currency.name | highlight: $select.search"></span><br/></ui-select-choices></ui-select></div>',
        link: link,
        scope: {
            ngModel: "=?",
            ngRequired: "=?",
            ngDisabled: "=?"
        }
    }
});

AdminApp.filter('timespan', function () {
    function filterFilter(params) {
        params = void 0 === params || null === params ? "" : params;
        var hours = null,
            minutes = null,
            value = null;
        return params = parseInt(params || 0), hours = Math.floor(params / 1e3 / 60 / 60), minutes = params / 1e3 / 60 % 60, value = (hours < 10 ? "0" + hours : hours) + ":" + (minutes < 10 ? "0" + minutes : minutes)
    }
    return filterFilter
});



AdminApp.factory('weekSelector', ['$rootScope', '$http', '$state', '$cookies', '$timeout', 'API_URL', function ($rootScope, $http, $state, $cookies, $timeout, API_URL) {
    function getWeekday(d, addDays) {
        var da = moment(d).isoWeekday(addDays);
        return da
    }

    function init() {
        self.weekStartDate || (self.weekStartDate = getWeekday(moment(), 1), self.setFromDate(self.weekStartDate))
    }
    var self = this;
    return self.setFromDate = function (date) {
        date = date.utc().startOf("isoweek"), self.weekStartDate = date, self.weekDays = {
            monday: new Date(getWeekday(date, 1)),
            tuesday: new Date(getWeekday(date, 2)),
            wednesday: new Date(getWeekday(date, 3)),
            thursday: new Date(getWeekday(date, 4)),
            friday: new Date(getWeekday(date, 5)),
            saturday: new Date(getWeekday(date, 6)),
            sunday: new Date(getWeekday(date, 7))
        }, self.weeknumber = parseInt(moment(date).isoWeek()), self.week = {
            weekNumber: moment(date).isoWeek(),
            start: new Date(getWeekday(date, 1)),
            end: new Date(getWeekday(date, 7))
        }, self.day = parseInt(date.date()), self.month = parseInt(moment(date).month() + 1), self.year = parseInt(moment(date).year())
    }, self.setFromWeekNumberAndYear = function (weekNumber, year) {
        self.weekStartDate = moment.utc(year + "W" + weekNumber, "YYYY{W}WW"), self.setFromDate(self.weekStartDate)
    }, self.nextWeek = function () {
        self.weekStartDate = moment(self.weekStartDate).add(7, "days"), self.setFromDate(self.weekStartDate)
    }, self.previousWeek = function () {
        self.weekStartDate = moment(self.weekStartDate).subtract(7, "days"), self.setFromDate(self.weekStartDate)
    }, self.weeksInYear = function () {
        for (var numberOfWeeks = Math.max(moment(new Date(self.year, 11, 31)).isoWeek(), moment(new Date(self.year, 11, 24)).isoWeek()), weeklist = [], i = 1; i <= numberOfWeeks; i++) weeklist.push(parseInt(i));
        return weeklist
    }, self.getYears = function () {
        for (var startYear = parseInt(moment().year(2e3).year()), endYear = parseInt(moment().add(10, "year").year()), years = []; endYear >= startYear;) years.push(startYear++);
        return years
    }, init(), self
}]);

AdminApp.directive('datepickerZone', function (API_URL, weekSelector, $state, $stateParams, $location) {
    function link(scope, element, attrs, ctrl) {
        ctrl.$formatters.push(function (value) {
            if (value) {
                var date = new Date(Date.parse(value));
                return date = new Date(date.getTime())
            }
        }), ctrl.$parsers.push(function (value) {
            if (!value) return value;
            var date = new Date(value.getTime());
            return date
        })
    }
    return {
        restrict: "A",
        priority: 1,
        require: "ngModel",
        link: link
    }
});

AdminApp.directive("csDateConverter", function () {

    var linkFunction = function (scope, element, attrs, ngModelCtrl) {

        ngModelCtrl.$parsers.push(function (datepickerValue) {
            //convert the date as per your specified format
            var date = moment(datepickerValue, scope.format)

            //convert it to the format recognized by datepicker
            return date.format("YYYY-MM-DD");
        });
    };

    return {
        scope: { format: '=' },
        restrict: "A",
        require: "ngModel",
        link: linkFunction
    };
});

AdminApp.filter('textOrNumber', function ($filter) {
    return function (input, fractionSize) {
        if (isNaN(input)) {
            return input;
        } else {
            return $filter('number')(input, fractionSize);
        };
    };
});

AdminApp.filter('characters', function ($filter) {
    return function (input, chars, breakOnWord) {
        if (isNaN(chars)) return input;
        if (chars <= 0) return "";
        if (input && input.length > chars) {
            if (input = input.substring(0, chars), breakOnWord)
                for (;
                    " " === input.charAt(input.length - 1);) input = input.substr(0, input.length - 1);
            else {
                var lastspace = input.lastIndexOf(" ");
                lastspace !== -1 && (input = input.substr(0, lastspace))
            }
            return input + "…"
        }
        return input
    }
});

AdminApp.filter('datetoms', function ($filter) {
    function filterFilter(params) {
        params = void 0 === params || null === params ? "" : params;
        var parsedDate = Date.parse(params);


        console.log(new Date(parsedDate));
        return parsedDate ? new Date(parsedDate).getTime() : 0
    }
    return filterFilter
});
AdminApp.directive('icheck', ['$timeout', '$parse', function ($timeout, $parse) {
    return {
        restrict: 'A',
        require: '?ngModel',
        link: function (scope, element, attr, ngModel) {
            $timeout(function () {
                var value = attr.value;

                function update(checked) {
                    if (attr.type === 'radio') {
                        ngModel.$setViewValue(value);
                    } else {
                        ngModel.$setViewValue(checked);
                    }
                }

                $(element).iCheck({
                    checkboxClass: attr.checkboxClass || 'icheckbox_square-green',
                    radioClass: attr.radioClass || 'iradio_square-green'
                }).on('ifChanged', function (e) {
                    scope.$apply(function () {
                        update(e.target.checked);
                    });
                });

                scope.$watch(attr.ngChecked, function (checked) {
                    if (typeof checked === 'undefined') checked = !!ngModel.$viewValue;
                    update(checked)
                }, true);

                scope.$watch(attr.ngModel, function (model) {
                    $(element).iCheck('update');
                }, true);

            })
        }
    }
}]);

AdminApp.controller('AddDayEventCtrl', ($scope, ngDialog, $http, API_URL) => {
    var id = ngDialog.getOpenDialogs()[0];

    $scope.save_event = () => {
        ngDialog.close(id, { selectedEvent: $scope.current_event });
    }

})
AdminApp.filter('round', function () {
    /* Use this $filter to round Numbers UP, DOWN and to his nearest neighbour.
       You can also use multiples */

    /* Usage Examples:
        - Round Nearest: {{ 4.4 | round }} // result is 4
        - Round Up: {{ 4.4 | round:'':'up' }} // result is 5
        - Round Down: {{ 4.6 | round:'':'down' }} // result is 4
        ** Multiples
        - Round by multiples of 10 {{ 5 | round:10 }} // result is 10
        - Round UP by multiples of 10 {{ 4 | round:10:'up' }} // result is 10
        - Round DOWN by multiples of 10 {{ 6 | round:10:'down' }} // result is 0
    */
    return function (value, mult, dir) {
        dir = dir || 'nearest';
        mult = mult || 1;
        value = !value ? 0 : Number(value);
        if (dir === 'up') {
            return Math.ceil((value + Number.EPSILON) * 100) / 100;
        } else if (dir === 'down') {
            return Math.floor((value + Number.EPSILON) * 100) / 100;
        } else {
            return Math.round((value + Number.EPSILON) * 100) / 100;
        }
    };
});