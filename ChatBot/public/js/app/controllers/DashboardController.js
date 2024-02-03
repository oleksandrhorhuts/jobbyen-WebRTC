/* Setup Home page controller */
angular.module('BotApp').controller('DashboardController', ['$rootScope', '$scope', '$state', '$http', '$window', '$cookies', '$timeout', 'API_URL', '$localStorage', function ($rootScope, $scope, $state, $http, $window, $cookies, $timeout, API_URL, $localStorage) {


    console.log($localStorage.tabs);
    if ($localStorage.tabs == undefined) {
        $rootScope.tabs = [];
    } else {
        $rootScope.tabs = JSON.parse($localStorage.tabs);
        console.log($rootScope.tabs);
    }
    $scope.generate = function () {
        $.ajax({
            url: 'create-code',
            type: 'POST',
            data: JSON.stringify($rootScope.tabs),
            contentType: 'application/json;charset=utf-8',
            dataType: 'json',
            success: function (data) {
                console.log(data);
            }
        })
    }
    $scope.simulate = function () {
        $window.open('/emulate');
    }
    $scope.init = function(){
    	$localStorage.tabs = null;
    	setTimeout(()=>{
    		location.href = '/';
    	}, 500);
    	
    }
    $scope.$on('$viewContentLoaded', function () {
        // initialize core components
    });
}]);
