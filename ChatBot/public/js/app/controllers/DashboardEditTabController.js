

/* Setup Home page controller */
angular.module('BotApp').controller('DashboardEditTabController', ['$rootScope', '$scope', '$state', '$http', '$window', '$cookies', '$timeout', 'API_URL', '$localStorage', 'ngDialog', function ($rootScope, $scope, $state, $http, $window, $cookies, $timeout, API_URL, $localStorage, ngDialog) {

    $scope.$on('$viewContentLoaded', function () {
        // initialize core components
    });

    let tab_id = $state.params.tab_id || 0;

    let tabs = JSON.parse($localStorage.tabs);


    $scope.tab = [];
    $scope.element = [];
    $scope.question_nodes = [];
    $scope.reply_nodes = [];
    $scope.tab_clone = [];
    if (tabs.find(x => x.index == tab_id)) {
        angular.copy(tabs.find(x => x.index == tab_id), $scope.tab);
        angular.copy(tabs.find(x => x.index == tab_id), $scope.tab_clone);
        angular.copy($scope.tab.question_nodes, $scope.question_nodes);
        angular.copy($scope.tab.reply_nodes, $scope.reply_nodes);
    }


    $scope.edit_tab_title = function () {
        $scope.title = $scope.tab.title;
        var newClassDialog = ngDialog.open({
            template: 'js/app/dialogs/title.html',
            closeByDocument: false,
            closeByEscape: false,
            showClose: false,
            controller: 'TabTitleDlgCtrl',
            className: 'ngdialog-theme-default',
            width: '500px',
            scope: $scope
        });
        newClassDialog.closePromise.then(function (data) {
            if (data && data.value && data.value.result) {
                $scope.$evalAsync(($scope) => {
                    var tab = $rootScope.tabs.find(x => x.index == $scope.tab.index);
                    tab.title = data.value.result;
                    $scope.tab.title = data.value.result;
                    $localStorage.tabs = JSON.stringify($rootScope.tabs);
                })
            }
        });
    }

    $scope.add_text_node = function () {
        let node = {
            'key': parseInt($scope.question_nodes.length + 1),
            'type': 0,
            'content': '',
        }
        $scope.question_nodes.push(node);

    }
    $scope.add_input_node = function () {
        let node = {
            'key': parseInt($scope.question_nodes.length + 1),
            'type': 1,
            'content': '',
        }
        $scope.question_nodes.push(node);
        angular.copy($scope.question_nodes, $scope.tab.question_nodes);
        var tab = $rootScope.tabs.find(x => x.index == $scope.tab.index);
        tab.question_nodes = $scope.tab.question_nodes;
        $localStorage.tabs = JSON.stringify($rootScope.tabs);
    }
    $scope.add_reply_node = function () {
        let node = {
            'content': '',
            'go_to': '',
        }
        $scope.reply_nodes.push(node);
    }
    $scope.refresh_tab = function (tab_index) {
        $scope.$evalAsync(($scope) => {
            var tab = $rootScope.tabs.find(x => x.index == tab_index);
            if (tab) {
                tab.question_nodes = [];
                tab.reply_nodes = [];
                $localStorage.tabs = JSON.stringify($rootScope.tabs);
                setTimeout(() => {
                    location.reload();
                }, 100);
            }
        })
    }
    $scope.removeQuestionNode = function (key) {
        $scope.$evalAsync(($scope) => {
            var question_node = $scope.question_nodes.find(x => x.key == key);
            var tab = $rootScope.tabs.find(x => x.index == tab_id);
            if (question_node) {
                $scope.question_nodes.splice($scope.question_nodes.indexOf(question_node), 1);
                tab.question_nodes = $scope.question_nodes;
                setTimeout(() => {
                    $localStorage.tabs = JSON.stringify($rootScope.tabs);
                }, 100);
            }
        })
    }
    $scope.save_tab = function (tab_index) {
        $scope.$evalAsync(($scope) => {
            $localStorage.tabs = JSON.stringify($rootScope.tabs);
            setTimeout(() => {
                location.reload();
            }, 100);
        })
    }
    $scope.delete_tab = function (tab_index) {
        $scope.$evalAsync(($scope) => {
            var tab = $rootScope.tabs.find(x => x.index == tab_index);
            if (tab) {
                $rootScope.tabs.splice($rootScope.tabs.indexOf(tab), 1);
                $localStorage.tabs = JSON.stringify($rootScope.tabs);
                setTimeout(() => {
                    location.href = '/';
                }, 100);
            }
        })
    }
}])
    .controller('TabTitleDlgCtrl', ($scope, ngDialog) => {
        var id = ngDialog.getOpenDialogs()[0];
        $scope.cancel = function () {
            ngDialog.close();
        }
        $scope.save = () => {
            if ($scope.title == '' || $scope.title == null || $scope.title == undefined) {
                return;
            }
            ngDialog.close(id, { result: $scope.title });
        }
    })