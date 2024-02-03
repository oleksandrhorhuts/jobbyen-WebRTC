/* Setup Home page controller */
angular.module('AdminApp').controller('JobReportController', function ($rootScope, $scope, $state, $http, SweetAlert, ngDialog, $window) {

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
                displayName: "Job ID",
                width: 180,
                field: "job_id",
                cellTemplate: '<div class="ui-grid-cell-contents"><a href="javascript:;" ng-click="grid.appScope.redirect_job(row.entity.job)">{{row.entity.job_id}}</a></div>'

            }, {
                displayName: "email",
                width: 180,
                field: "email",
            }, {
                displayName: "Rapport Beskrivelse",
                width: 500,
                field: "report_description",
            }, {
                displayName: "Opret dato",
                name: 'created_at',
                width: '150',
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.created_at | amUtc | amLocal | amDateFormat:"DD.MM.YYYY"}}</div>'
            }, {
                displayName: "Standby eller slet",
                enableSorting: !1,
                name: "action",
                enableFiltering: false,
                cellTemplate: '<div class="ui-grid-cell-contents d-flex justify-content-end align-items-center"><i class="fa fa-eye" title="See details" style="font-size:16px;cursor:pointer;" ng-click="grid.appScope.view_detail(row.entity)"></i></div>',
            }],
            paginationPageSizes: [25, 40, 60],
            paginationPageSize: 40
        };

    }

    getJobReport();
    function getJobReport() {
        $http.get("/admin-api/get_job_reports").success(function (data) {
            $scope.$evalAsync(($scope) => {
                $scope.gridOptions.data = data;
                $scope.indeterminate = false;
            });
        });
    }

    $scope.redirect_job = function (job) {
        console.log(job);
        var url = '/job/' + job.id + '/' + job.seo;
        $window.open(url, '_blank');
    }

    $scope.view_detail = function (entity) {

        $scope.selected_job = angular.copy(entity);
        var newClassDialog = ngDialog.open({
            template: 'admin_js/app/dialogs/job-report.html',
            closeByDocument: false,
            closeByEscape: false,
            controller: 'JobReportViewDlgCtrl',
            className: 'ngdialog-theme-default',
            width: '700px',
            scope: $scope
        });
        newClassDialog.closePromise.then(function (data) {
            if (data && data.value) {
            }
        });
    }

}).controller('JobReportViewDlgCtrl', ["$scope", "ngDialog", "$http", "bsLoadingOverlayService", function ($scope, ngDialog, $http, bsLoadingOverlayService) {
    var id = ngDialog.getOpenDialogs()[0];

    $scope.selected_job.report_option = JSON.parse($scope.selected_job.report_option);
    $scope.action = 'Update';

}])