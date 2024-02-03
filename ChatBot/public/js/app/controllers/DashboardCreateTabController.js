/* Setup Home page controller */
angular.module('BotApp').controller('DashboardCreateTabController', ['$rootScope', '$scope', '$state', '$http', '$window', '$cookies', '$timeout', 'API_URL', '$localStorage', function ($rootScope, $scope, $state, $http, $window, $cookies, $timeout, API_URL, $localStorage) {

    $scope.$on('$viewContentLoaded', function () {
        // initialize core components
    });

    $scope.save = function () {
        if ($scope.tab_title == undefined) {
            return;
        }
        if($rootScope.tabs.length){
            var tab_index = parseInt($rootScope.tabs[$rootScope.tabs.length - 1].index) + 1
        } else {
            var tab_index = parseInt($rootScope.tabs.length + 1);
        }
        
        
        let new_tab = {
            'title': $scope.tab_title,
            'index': tab_index,
            'question_nodes': [
                {
                    'type': 0,
                    'content': '',
                }
            ],
            'key' : tab_index,
            'reply_nodes': []
        }
        $scope.tab_title = '';
        $rootScope.tabs.push(new_tab);



        $localStorage.tabs = JSON.stringify($rootScope.tabs);


        setTimeout(()=>{
            $state.go('dashboard.tab-edit', { tab_id: tab_index });
        }, true);
    }
}]);
