/* Setup Home page controller */
angular.module('AdminApp').controller('CompanyListController', function ($rootScope, $scope, $state, $http) {

    drawGrid();
    function drawGrid() {

        $scope.gridOptions = {
            enableRowHeaderSelection: !1,
            multiSelect: !1,
            customRow: !0,
            rowTemplate: '<div ng-dblclick="grid.appScope.rowDblClick(row)" ng-repeat="col in colContainer.renderedColumns track by col.colDef.name" class="ui-grid-cell" ui-grid-cell ng-class="{\'ui-grid-cell-standby\' : row.entity.standby == 1}"></div>',
            enableFiltering: !0,
            columnDefs: [{
                displayName: "",
                width: 10,
                name: "hired_status",
                cellTemplate: '<div class="status-col"><div class="status" ng-class="{\'bg-success\' : row.entity.cv_ready == 0, \'bg-danger\' : row.entity.cv_ready == 1}" ></div></div>',
                headerCellTemplate: "<div></div>"
            }, {
                width: 50,
                displayName: "#",
                field: "company_id",
                enableFiltering: false
            }, {
                displayName: "",
                width: 30,
                name: "company_logo",
                cellTemplate: '<div class="ui-grid-cell-contents"><img ng-src="/images/company_logo/{{row.entity.company_logo}}.png" ng-if="row.entity.company_logo" style="width:20px;height:20px;border-radius:100%;" /></div>',
                enableFiltering: false,
            }, {
                displayName: "mail",
                width: 200,
                field: "user.email"
            }, {
                displayName: "Virk Email",
                width: 200,
                field: "company_email"
            }, {
                displayName: "Virk CVR",
                width: 100,
                field: "company_cvr"
            }, {
                displayName: "Virk Name",
                width: 180,
                field: "company_name",
            }, {
                displayName: "City",
                width: 150,
                field: "company_location.name",
            }, {
                displayName: "Phone",
                width: 100,
                field: "company_phone",
            }, {
                displayName: "job ads",
                width: 80,
                field: "company_city",
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.user.company_job.length}}</div>'
            }, {
                displayName: "messages",
                width: 80,
                field: "company_city",
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.user.message.length}}</div>'
            }, {
                displayName: "videointerview",
                width: 80,
                field: "company_city",
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.user.video_call.length}}</div>'
            }, {
                displayName: "Pakke",
                width: 100,
                field: "package",
                cellTemplate: "/admin_js/app/templates/company_package.html"
            }, {
                displayName: "Last Activity",
                width: 100,
                field: "company_city",
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.last_login | amDateFormat:"DD.MM.YYYY HH:mm:ss"}}</div>'
            }, {
                displayName: "Created",
                name: 'created_at',
                width: '100',
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.created_at | amUtc | amLocal | amDateFormat:"DD.MM.YYYY"}}</div>'
            }, {
                displayName: "Permission",
                enableSorting: !1,
                name: "action",
                width: '200',
                enableFiltering: false,
                cellTemplate: '<div class="ui-grid-cell-contents d-flex justify-content-end align-items-center"><i class="fa fa-check" title="Refresh-kandidat" style="font-size:16px;margin-right:5px;cursor:pointer;" ng-click="grid.appScope.close_permission(row.entity.user.id)" ng-if="row.entity.user.user_level == 1"></i><i class="fa fa-close" title="StandBy-kandidat" style="font-size:16px;margin-right:5px;cursor:pointer;" ng-click="grid.appScope.make_permission(row.entity.user.id)" ng-if="row.entity.user.user_level == 0"></i></div>',
            }],
            paginationPageSizes: [25, 40, 60],
            paginationPageSize: 40
        };

    }

    $scope.make_permission = function (user_id) {
        $http.post("/admin-api/make_permission", { user_id: user_id }).success(function (data) {
            $scope.$evalAsync(($scope) => {
                $scope.gridOptions.data = data;
            });
        });
    }

    $scope.close_permission = function (user_id) {
        $http.post("/admin-api/close_permission", { user_id: user_id }).success(function (data) {
            $scope.$evalAsync(($scope) => {
                $scope.gridOptions.data = data;
            });
        });
    }

    $scope.rowDblClick = function (row) {
        $state.go('authed.company.detail', { company_id: row.entity.company_id });
    }

    $http.get("/admin-api/get_companies").success(function (data) {

        $scope.$evalAsync(($scope) => {

            $scope.gridOptions.data = data;
            $scope.indeterminate = false;
        });
    });
});
