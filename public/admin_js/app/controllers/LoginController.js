/* Setup Profile page controller */
angular.module('AdminApp').controller('LoginController', ['$location', '$rootScope', '$scope', '$state', '$sce', '$http', '$window', '$cookies', '$timeout', 'Auth', '$localStorage', function ($location, $rootScope, $scope, $state, $sce, $http, $window, $cookies, $timeout, Auth, $localStorage) {
    function successAuth(res) {

        if(res.token !=''){
            $rootScope.admin_globals = {
                adminUser: {
                    token: res.token,
                    logged_in: true,
                }
            };
            var cookieExp = new Date();
            cookieExp.setMinutes(cookieExp.getMinutes() + 720000);
            $cookies.putObject('admin_globals', $rootScope.admin_globals, { expires: cookieExp });
    
    
            $localStorage.token = res.token;
            $state.go('authed.dashboard');
        }
        
    }
    $scope.authenticate = function () {
        //
        var formData = {
            email: $scope.user_email,
            password: $scope.user_pwd
        }
        Auth.signin(formData, successAuth, function () {
            $rootScope.error = 'Invalid credentials';
        })
        //$state.go('authed.dashboard');
    }
    $scope.token = $localStorage.token;
    $scope.tokenClaims = Auth.getTokenClaims();

    $scope.submit_form = function () {
        if ($scope.user_email != '' && $scope.user_pwd != '' && $scope.user_pwd != undefined) {
            $scope.authenticate();
        }

        if ($scope.user_email != '' && ($scope.user_pwd == '' || $scope.user_pwd != undefined)) {
            $('#admin_pwd_input').focus();
            return;
        }
    }

}]);
