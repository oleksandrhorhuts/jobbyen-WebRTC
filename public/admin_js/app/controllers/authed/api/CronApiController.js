/* Setup Home page controller */
angular.module('AdminApp').controller('CronApiController', function ($rootScope, $scope, $state, $http) {

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
                displayName: "Virksomehed navn",
                width: 200,
                field: "company_name"
            }, {
                displayName: "antal",
                width: 70,
                field: "job_count",
                cellTemplate: '<div class="ui-grid-cell-contents" ng-bind-html="row.entity.job_count"><div>'
            }, {
                displayName: "I dag",
                width: 100,
                field: "today_job_count",
                cellTemplate: '<div class="ui-grid-cell-contents" ng-bind-html="row.entity.today_job_count"><div>'
            }, {
                displayName: "Oprettet",
                name: 'created_at',
                width: 100,
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.created_at | amUtc | amDateFormat:"DD.MM.YYYY"}}</div>'
            }, {
                displayName: "Opdateret",
                name: 'updated_at',
                width: 150,
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.updated_at | amUtc | amDateFormat:"DD.MM.YYYY HH:mm:ss"}}</div>'
            }, {
                displayName: "nuværende trin",
                name: 'pagination',
                width: 150,
            }, {
                displayName: "Handling",
                enableSorting: !1,
                name: "action",
                width: '*',
                enableFiltering: false,
                cellTemplate: '<div class="ui-grid-cell-contents d-flex justify-content-end align-items-center"><i class="fa fa-refresh" title="Initialize" style="font-size:16px;cursor:pointer;" ng-click="grid.appScope.initialize_pagination(row.entity.id)"></i><i class="fa fa-play" title="Start" style="font-size:16px;cursor:pointer;margin-left:20px;" ng-click="grid.appScope.run(row.entity.id)"></i></div>',
            }],
            paginationPageSizes: [25, 40, 60],
            paginationPageSize: 40
        };

    }

    $scope.initialize_pagination = function (cron_index) {
        $http.post("/admin-api/update_initial_pagination", { id: cron_index }).success(function (data) {
            $scope.$evalAsync(($scope) => {
                $scope.gridOptions.data = data.crons;
                $scope.indeterminate = false;
            });
        });
    }

    $scope.refresh_all_steps = function () {
        $http.post("/admin-api/refresh_initial_pagination").success(function (data) {
            $scope.$evalAsync(($scope) => {
                $scope.gridOptions.data = data.crons;
                $scope.indeterminate = false;
            });
        });
    }

    $scope.run = function (cron_index) {
        $http.post("/admin-api/run_pagination", { id: cron_index }).success(function (data) {
            $scope.$evalAsync(($scope) => {
                $scope.gridOptions.data = data.crons;
                $scope.indeterminate = false;
            });
        });
    }

    $scope.rowDblClick = function (row) {
        // $state.go('authed.blog.detail', { blog_id: row.entity.id });
    }

    getCronApi();
    function getCronApi() {
        $http.get("/admin-api/get_cron_api").success(function (data) {
            console.log(data);
            $scope.$evalAsync(($scope) => {
                $scope.gridOptions.data = data;
                $scope.indeterminate = false;
            });
        });
    }

});
