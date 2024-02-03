/* Setup Home page controller */
angular.module('AdminApp').controller('CompanyDetailController', function ($rootScope, $scope, $state, $http) {

    $scope.company_id = $state.params.company_id;

    $http.get("/admin-api/get_company_detail/" + $scope.company_id).success(function (data) {
        $scope.$evalAsync(($scope) => {
            $scope.company = data;
        });
    });

    $scope.get_cv_ready = function (cv_ready) {
        if (cv_ready) {
            return 'Ja';
        } else {
            return 'Nej';
        }
    }

    $scope.downloadFile = function (file_name) {
        var link = document.createElement('a');
        link.download = 'virksomhed-video-resume.mp4';
        link.href = '/images/company_video/' + file_name;
        link.click();
    }
});
