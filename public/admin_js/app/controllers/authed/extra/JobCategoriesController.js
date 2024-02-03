/* Setup Home page controller */
angular.module('AdminApp').controller('JobCategoriesController', function ($rootScope, $scope, $state, $http, SweetAlert, ngDialog) {

    drawGrid();
    function drawGrid() {

        $scope.gridOptions = {
            enableRowHeaderSelection: !1,
            multiSelect: !1,
            customRow: !0,
            rowTemplate: '<div ng-dblclick="grid.appScope.update(row)" ng-repeat="col in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ui-grid-cell ng-class="{\'ui-grid-cell-standby\' : row.entity.standby == 1}"></div>',
            enableFiltering: !0,
            columnDefs: [{
                width: 50,
                displayName: "#",
                field: "id",
                enableFiltering: false
            }, {
                displayName: "Name",
                width: 180,
                field: "name",
            }, {
                displayName: "Update",
                enableSorting: !1,
                name: "action",
                enableFiltering: false,
                cellTemplate: '<div class="ui-grid-cell-contents d-flex justify-content-end align-items-center"><i class="fa fa-edit" title="edit-job-category" style="font-size:16px;margin-right:5px;cursor:pointer;" ng-click="grid.appScope.update(row)"></i></div>',
            }],
            paginationPageSizes: [25, 40, 60],
            paginationPageSize: 40
        };

    }

    $scope.update = function (row) {
        $scope.selected_category = angular.copy(row.entity);
        var newClassDialog = ngDialog.open({
            template: 'admin_js/app/dialogs/job-category.html',
            closeByDocument: false,
            closeByEscape: false,
            controller: 'JobCategoryDlgCtrl',
            className: 'ngdialog-theme-default',
            width: '700px',
            scope: $scope
        });
        newClassDialog.closePromise.then(function (data) {
            if (data && data.value) {
                getJobCategories();
            }
        });
    }
    $scope.create_category = function () {
        var newClassDialog = ngDialog.open({
            template: 'admin_js/app/dialogs/job-category.html',
            closeByDocument: false,
            closeByEscape: false,
            controller: 'JobNewCategoryDlgCtrl',
            className: 'ngdialog-theme-default',
            width: '700px',
            scope: $scope
        });
        newClassDialog.closePromise.then(function (data) {
            if (data && data.value) {
                getJobCategories();
            }
        });
    }

    getJobCategories();
    function getJobCategories() {
        $http.get("/admin-api/get_job_categories").success(function (data) {
            $scope.$evalAsync(($scope) => {
                $scope.gridOptions.data = data;
                $scope.indeterminate = false;
            });
        });
    }

})
    .controller('JobCategoryDlgCtrl', ["$scope", "ngDialog", "$http", "bsLoadingOverlayService", function ($scope, ngDialog, $http, bsLoadingOverlayService) {
        var id = ngDialog.getOpenDialogs()[0];
        $scope.action = 'Update';

        $scope.update = () => {
            if ($scope.selected_category.name == '' || $scope.selected_category.name == undefined) {
                return;
            }
            $http.post("/admin-api/update_category", { id: $scope.selected_category.id, name: $scope.selected_category.name }).success((data) => {
                $scope.$evalAsync(($scope) => {
                    if (data.result == 'success') {
                        ngDialog.close(id, {});
                    }
                });
            });
        }
    }])
    .controller('JobNewCategoryDlgCtrl', ["$scope", "ngDialog", "$http", "bsLoadingOverlayService", function ($scope, ngDialog, $http, bsLoadingOverlayService) {
        var id = ngDialog.getOpenDialogs()[0];

        $scope.selected_category = {
            name : ''
        };

        $scope.action = 'Opret';
        $scope.update = () => {
            if ($scope.selected_category.name == '' || $scope.selected_category.name == undefined) {
                return;
            }
            $http.post("/admin-api/create_category", { name: $scope.selected_category.name }).success((data) => {
                $scope.$evalAsync(($scope) => {
                    if (data.result == 'success') {
                        ngDialog.close(id, {});
                    } else if(data.result == 'error'){
                        alert('duplicated');
                    } else {

                    }
                });
            });
        }
    }]);