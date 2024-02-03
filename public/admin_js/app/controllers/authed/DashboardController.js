/* Setup Home page controller */
angular.module('AdminApp').controller('DashboardController', function ($rootScope, $scope, $state, bsLoadingOverlayService, $http) {

    $(window).bind("resize", function () {
        console.log($(this).width());
        
        if ($(this).width() < 769) {
            $('body').addClass('body-small')
        } else {
            $('body').removeClass('body-small')
        }
    });

    bsLoadingOverlayService.start();
    $http.get("/admin-api/get_statistics").success(function (data) {
        $scope.$evalAsync(($scope) => {
            $scope.dashboard = data;
            bsLoadingOverlayService.stop();
        });
    });
});
