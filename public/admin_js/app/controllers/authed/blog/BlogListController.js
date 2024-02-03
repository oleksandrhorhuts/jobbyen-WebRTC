/* Setup Home page controller */
angular.module('AdminApp').controller('BlogListController', function ($rootScope, $scope, $state, $http) {

    drawGrid();
    function drawGrid() {

        $scope.gridOptions = {
            enableRowHeaderSelection: !1,
            multiSelect: !1,
            customRow: !0,
            rowTemplate: '<div ng-dblclick="grid.appScope.rowDblClick(row)" ng-repeat="col in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ui-grid-cell ng-class="{\'ui-grid-cell-standby\' : row.entity.standby == 1}"></div>',
            enableFiltering: !0,
            columnDefs: [{
                width: 50,
                displayName: "#",
                field: "id",
                enableFiltering: false
            }, {
                displayName: "Navn",
                width: 200,
                field: "name"
            }, {
                displayName: "description",
                width: 180,
                field: "description",
                cellTemplate: '<div class="ui-grid-cell-contents" ng-bind-html="row.entity.description"><div>'
            }, {
                displayName: "Created",
                name: 'created_at',
                width: '*',
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.created_at | amUtc | amLocal | amDateFormat:"DD.MM.YYYY"}}</div>'
            }, {
                displayName: "Standby eller slet",
                enableSorting:!1,
                name: "action",
                enableFiltering: false,
                cellTemplate: '<div class="ui-grid-cell-contents d-flex justify-content-end align-items-center"><i class="fa fa-trash" title="Slet" style="font-size:16px;cursor:pointer;" ng-click="grid.appScope.remove_blog(row.entity.id)"></i></div>',
            }],
            paginationPageSizes: [25, 40, 60],
            paginationPageSize: 40
        };

    }

    $scope.remove_blog = function(blog_id){
        $http.post("/admin-api/delete_blog", { blog_id: blog_id}).success((data) => {
            $scope.$evalAsync(($scope) => {

                if (data.result == 'success') {
                    getBlog();
                }

            });
        });
    }


    $scope.rowDblClick = function (row) {
        $state.go('authed.blog.detail', { blog_id: row.entity.id });
    }

    getBlog();
    function getBlog(){
        $http.get("/admin-api/get_blog").success(function (data) {

            $scope.$evalAsync(($scope) => {
    
                $scope.gridOptions.data = data;
                $scope.indeterminate = false;
            });
        });
    }
    
});
