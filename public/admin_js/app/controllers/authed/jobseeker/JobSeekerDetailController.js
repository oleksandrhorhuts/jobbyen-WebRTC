/* Setup Home page controller */
angular.module('AdminApp').controller('JobSeekerDetailController', function ($rootScope, $scope, $state, $http) {

    $scope.user_id = $state.params.user_id;

    $http.get("/admin-api/get_jobseeker_detail/" + $scope.user_id).success(function (data) {
        $scope.$evalAsync(($scope) => {
            $scope.jobseeker = data;
        });
    });

    $scope.get_cv_ready = function(cv_ready){
        if(cv_ready){
            return 'Ja';
        } else {
            return 'Nej';
        }
    }

    $scope.downloadFile = function(file_name, file_ext, file_path){
        var link = document.createElement('a');
        link.download = file_name;
        link.href = '/images/documents/' + file_path;
        link.click();
    }
    $scope.get_document_extension = function(ext){
        if(ext == 'pdf'){
            return 'fa-file-pdf-o';
        } else {
            return 'fa-file-word-o';
        }
    }
});
