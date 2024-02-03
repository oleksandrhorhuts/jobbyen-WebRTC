/* Setup Home page controller */
angular.module('AdminApp').controller('BlogDetailController', function ($rootScope, $scope, $state, $http, bsLoadingOverlayService) {

    $scope.blog_id = $state.params.blog_id;


    $scope.blog_files = [];
    $scope.files = [];

    $http.get("/admin-api/get_blog_detail/" + $scope.blog_id).success(function (data) {
        $scope.$evalAsync(($scope) => {
            $scope.blog = data;

            for (var idx = 0; idx < $scope.blog.detail.length; idx++) {
                $scope.blog_files.push({ src: $scope.blog.detail[idx].blog_file_name, type: 0 });
                $scope.files.push({ file: null });
            }

        });
    });

    $scope.add_image = function () {
        $('#file-upload').click();
    }
    $scope.photofileChanged = async function (e) {

        if (e.length) {
            for (var idx = 0; idx < e.length; idx++) {
                var reader = new FileReader();
                reader.onload = (read_handle) => {
                    console.log(read_handle);
                    $scope.$evalAsync(($scope) => {
                        $scope.blog_files.push({ src: read_handle.target.result, type: 1 });
                    })
                }

                await reader.readAsDataURL(e[idx]);
                $scope.files.push({ file: e[idx] });
            }
        } else {
        }
    }

    $scope.remove_image = function (index, type) {
        console.log(index);
        if (type == 0) {

            $http.post("/admin-api/delete_blog_file", { blog_id: $scope.blog_id, blog_name: $scope.blog_files[index].src }).success((data) => {
                $scope.$evalAsync(($scope) => {

                    if (data.result == 'success') {
                        $scope.blog_files.splice(index, 1);
                        $scope.files.splice(index, 1);
                    }

                });
            });
        } else {
            $scope.blog_files.splice(index, 1);
            $scope.files.splice(index, 1);
        }

        console.log($scope.files);

    }

    $scope.update_blog = function () {


        if ($scope.blog.name == '' || $scope.blog.description == '' || $scope.blog_files.length == 0) {
            return;
        }

        console.log($scope.files);

        $scope.tmpFiles = [];
        for (var _idx = 0; _idx < $scope.files.length; _idx++) {
            if ($scope.files[_idx].file != null) {
                $scope.tmpFiles.push($scope.files[_idx].file);
            }
        }

        var submitBlogFormData = new FormData();

        submitBlogFormData.append('blog_id', $scope.blog_id);
        submitBlogFormData.append('blog', JSON.stringify($scope.blog));
        for (var idx = 0; idx < $scope.tmpFiles.length; idx++) {
            submitBlogFormData.append('upload_photo_file_' + idx, $scope.tmpFiles[idx]);
        }
        submitBlogFormData.append('file_cnt', $scope.tmpFiles.length);



        bsLoadingOverlayService.start();

        let token = $rootScope.admin_globals.adminUser.token;
        var request = new XMLHttpRequest();
        request.open("POST", "/admin-api/update_blog");
        request.setRequestHeader("Authorization", 'Bearer ' + token);
        request.onload = function () {
            if (request.readyState == request.DONE) {
                bsLoadingOverlayService.stop();
                if (request.status == 200) {
                    var response_data = JSON.parse(request.responseText);
                    if (response_data.result == 'success') {
                        location.reload();
                    } else {

                    }

                }
            }
        }
        request.send(submitBlogFormData);
    }
});
