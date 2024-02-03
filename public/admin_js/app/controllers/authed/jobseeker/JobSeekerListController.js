/* Setup Home page controller */
angular.module('AdminApp').controller('JobSeekerListController', function ($rootScope, $scope, $state, $http, SweetAlert) {

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
                field: "id",
                enableFiltering: false
            }, {
                displayName: "",
                width: 30,
                name: "avatar",
                cellTemplate: '<div class="ui-grid-cell-contents"><img ng-src="/images/photos/{{row.entity.avatar}}.png" ng-if="row.entity.avatar" style="width:20px;height:20px;border-radius:100%;" /></div>',
                enableFiltering: false,
            }, {
                displayName: "Email",
                width: 200,
                field: "email"
            }, {
                displayName: "Name",
                width: 180,
                field: "name",
            }, {
                displayName: "Phone",
                width: 100,
                field: "phone",
            }, {
                displayName: "CV created",
                width: 80,
                field: "name",
                cellTemplate: '<div class="ui-grid-cell-contents"><span ng-if="row.entity.cv_ready == 1">Ja</span><span ng-if="row.entity.cv_ready == 0">Nej</span></div>',
                enableFiltering: false
            }, {
                displayName: "Last activity",
                width: 200,
                field: "name",
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.last_login | amDateFormat:"DD.MM.YYYY HH:mm:ss"}}</div>'
            }, {
                displayName: "Package",
                width: 170,
                name: 'package',
                cellTemplate: "/admin_js/app/templates/package.html"
            }, {
                displayName: "Created Account",
                name: 'created_at',
                width: '100',
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.created_at | amUtc | amLocal | amDateFormat:"DD.MM.YYYY"}}</div>'
            }, {
                displayName: "Standby eller slet",
                enableSorting: !1,
                name: "action",
                enableFiltering: false,
                cellTemplate: '<div class="ui-grid-cell-contents d-flex justify-content-end align-items-center"><i class="fa fa-refresh" title="Refresh-kandidat" style="font-size:16px;margin-right:5px;cursor:pointer;" ng-click="grid.appScope.refresh(row.entity.id)" ng-if="row.entity.standby == 1"></i><i class="fa fa-power-off" title="StandBy-kandidat" style="font-size:16px;margin-right:5px;cursor:pointer;" ng-click="grid.appScope.standby(row.entity.id)" ng-if="row.entity.standby == 0"></i><i class="fa fa-trash" title="Slet søger" style="font-size:16px;cursor:pointer;" ng-click="grid.appScope.remove_seeker(row.entity.id)"></i></div>',
            }],
            paginationPageSizes: [25, 40, 60],
            paginationPageSize: 40
        };

    }


    $scope.refresh = function(seeker_id){
        SweetAlert.swal({
            title: "Vil du standby denne jobsøgende?",
            text: "",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ja",
            cancelButtonText: "Nej",
            closeOnConfirm: false
        },
            function (isConfirm) {
                if (isConfirm) {
                    $http.post("/admin-api/refresh_job_seeker", { seeker_id: seeker_id }).success(function (data) {
                        $scope.$evalAsync(($scope) => {
                            if (data.result == 'success') {
                                SweetAlert.swal("Succes!", "", "success");
                                $scope.gridOptions.data = data.seekers;
                            } else {
                                SweetAlert.swal("Succes!", "", "success");
                            }
                        });
                    });
                } else {

                }

            });
    }

    $scope.standby = function (seeker_id) {
        SweetAlert.swal({
            title: "Vil du fjerne standby for denne jobsøgende?",
            text: "",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ja",
            cancelButtonText: "Nej",
            closeOnConfirm: false
        },
            function (isConfirm) {
                if (isConfirm) {
                    $http.post("/admin-api/standby_job_seeker", { seeker_id: seeker_id }).success(function (data) {
                        $scope.$evalAsync(($scope) => {
                            if (data.result == 'success') {
                                SweetAlert.swal("Succes!", "", "success");
                                $scope.gridOptions.data = data.seekers;
                            } else {
                                SweetAlert.swal("Succes!", "", "success");
                            }
                        });
                    });
                } else {

                }

            });
    }
    $scope.remove_seeker = function (seeker_id) {

        SweetAlert.swal({
            title: "Vil du slette denne jobsøgende?",
            text: "",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ja",
            cancelButtonText: "Nej",
            closeOnConfirm: false
        },
            function (isConfirm) {
                if (isConfirm) {
                    $http.post("/admin-api/delete_job_seeker", { seeker_id: seeker_id }).success(function (data) {
                        $scope.$evalAsync(($scope) => {
                            if (data.result == 'success') {
                                SweetAlert.swal("Slettet!", "", "success");
                                $scope.gridOptions.data = data.seekers;
                            } else {
                                SweetAlert.swal("Slettet!", "", "success");
                            }
                        });
                    });
                } else {

                }

            });
    }
    $scope.rowDblClick = function (row) {
        console.log(row);
        $state.go('authed.jobseeker.detail', { user_id: row.entity.id });
    }



    $http.get("/admin-api/get_job_seekers").success(function (data) {
        $scope.$evalAsync(($scope) => {
            $scope.gridOptions.data = data;
            $scope.indeterminate = false;
        });
    });
});
