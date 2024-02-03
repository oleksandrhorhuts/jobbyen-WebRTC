/* Setup Home page controller */
angular.module('AdminApp').controller('JobSeekerTransactionDetailController', function ($rootScope, $scope, $state, $http) {
    let trans_id = $state.params.trans_id;

    $http.get('/admin-api/get_transaction_detail/' + trans_id).then(function (response) {
        $scope.$evalAsync(($scope) => {
            if (response.data.result == 'success') {
                $scope.transaction = response.data.invoice;
                $scope.email = response.data.user_email;
                $scope.name = response.data.user_name;
                $scope.company = response.data.company;

            } else {

            }
        })
    });

    $scope.get_current_price = function (plan) {
        if (plan == 1) {
            return '695';
        } else if (plan == 2) {
            return '1495';
        } else {

        }
    }
    $scope.get_package = function (plan) {
        if (plan == 1) {
            return 'Basic pakke';
        } else if (plan == 2) {
            return 'Pro Pakke';
        } else {

        }
    }
});
