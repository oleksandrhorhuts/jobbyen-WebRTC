/* Setup Profile page controller */
angular.module('AdminApp').controller('AuthedController', function ($rootScope, $scope, $state, $http, $sce, $window, $cookies, $timeout, $interval, Auth) {
    $scope.detectMob = function(){
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            return true;
        } else {
            return false;
        }
    }
    $scope.logout = function(){
        Auth.main_logout();
    }
    $scope.remove_sidebar = function(){
        if($('body').hasClass('mini-navbar')){
            $('body').removeClass('mini-navbar');
        }
    }
    
    $(document).ready(function () {
        $('.navbar-minimalize').on('click', function () {
            $("body").toggleClass("mini-navbar");
            SmoothlyMenu();
    
        });
        
        function SmoothlyMenu() {
            if (!$('body').hasClass('mini-navbar') || $('body').hasClass('body-small')) {
                // Hide menu in order to smoothly turn on when maximize menu
                $('#side-menu').hide();
                // For smoothly turn on menu
                setTimeout(
                    function () {
                        $('#side-menu').fadeIn(400);
                    }, 200);
            } else if ($('body').hasClass('fixed-sidebar')) {
                $('#side-menu').hide();
                setTimeout(
                    function () {
                        $('#side-menu').fadeIn(400);
                    }, 100);
            } else {
                // Remove all inline style from jquery fadeIn function to reset menu state
                $('#side-menu').removeAttr('style');
            }
        }
        if ($(this).width() < 769) {
            $('body').addClass('body-small')
        } else {
            $('body').removeClass('body-small')
        }
    
        // MetisMenu
        $('#side-menu').metisMenu();
    });
    $(window).bind("resize", function () {
        if ($(this).width() < 769) {
            $('body').addClass('body-small')
        } else {
            $('body').removeClass('body-small')
        }
    });
});
