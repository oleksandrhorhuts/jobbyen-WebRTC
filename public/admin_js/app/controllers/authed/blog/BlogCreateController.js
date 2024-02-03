/* Setup Home page controller */
angular.module('AdminApp').controller('BlogCreateController', function ($rootScope, $scope, $state, $http, bsLoadingOverlayService) {
    $scope.add_image = function () {
        $('#file-upload').click();
    }
    $scope.blog = {
        name: '',
        description: '',
    }
    $scope.blog_files = [];
    $scope.files = [];
    $scope.photofileChanged = function (e) {
        $scope.$evalAsync(($scope) => {
            console.log(e);
            console.log(e.length);

            for (var idx = 0; idx < e.length; idx++) {
                $scope.files.push(e[idx]);
                var reader = new FileReader();

                reader.onload = function (e) {
                    $scope.$evalAsync(($scope) => {
                        $scope.blog_files.push(e.target.result);
                    })
                }

                reader.readAsDataURL(e[idx]);
            }

            if (e.length) {
                console.log(e);
            } else {
            }
        })
    }
    $scope.remove_image = function (index) {
        $scope.blog_files.splice(index, 1);
        $scope.files.splice(index, 1);
    }
    $scope.clearFileSelection = function () {
        angular.element("input[id='file-upload']").val(null);
    }

    $scope.create_blog = function () {
        if ($scope.blog.name == '' || $scope.blog.description == '' || $scope.blog_files.length == 0) {
            return;
        }
        console.log($scope.blog);

        var submitBlogFormData = new FormData();
        submitBlogFormData.append('blog', JSON.stringify($scope.blog));
        for (var idx = 0; idx < $scope.files.length; idx++) {
            submitBlogFormData.append('upload_photo_file_' + idx, $scope.files[idx]);
        }
        submitBlogFormData.append('file_cnt', $scope.files.length);



        bsLoadingOverlayService.start();

        let token = $rootScope.admin_globals.adminUser.token;
        var request = new XMLHttpRequest();
        request.open("POST", "/admin-api/create_blog");
        request.setRequestHeader("Authorization", 'Bearer ' + token);
        request.onload = function () {
            if (request.readyState == request.DONE) {
                bsLoadingOverlayService.stop();
                if (request.status == 200) {
                    var response_data = JSON.parse(request.responseText);
                    if (response_data.result == 'success') {
                        $state.go('authed.blog.list');
                    } else {

                    }

                }
            }
        }
        request.send(submitBlogFormData);

    }
});
