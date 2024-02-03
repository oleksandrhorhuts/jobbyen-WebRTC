/* Setup Home page controller */
angular.module('AdminApp').controller('EmployerTransactionController', function ($rootScope, $scope, $state, $http, bsLoadingOverlayService) {
    bsLoadingOverlayService.start();
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
                displayName: "Name",
                width: 150,
                field: "name"
            }, {
                displayName: "Description",
                width: 250,
                field: "description",
            }, {
                displayName: "Total",
                width: 100,
                field: "total",
                enableFiltering: false,
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.total | currency}}</div>'
            }, {
                displayName: "Status",
                width: 100,
                field: "total",
                cellTemplate: "/admin_js/app/templates/paid_status.html"
            }, {
                displayName: "Created",
                name: 'created_at',
                width: '150',
                enableFiltering: false,
                cellTemplate: '<div class="ui-grid-cell-contents">{{row.entity.created_at | amUtc | amLocal | amDateFormat:"DD.MM.YYYY HH:mm:ss"}}</div>'
            }, {
                displayName: "",
                name: "action",
                enableSorting: !1,
                enableFiltering: false,
                width: '*',
                cellTemplate: '<div class="ui-grid-cell-contents d-flex justify-content-end"><button type="button" class="btn btn-primary" style="display:flex;align-items:center;padding:0px 8px;margin-right:5px;" ui-sref="authed.transaction.employer-detail({trans_id: row.entity.id})" title="Se faktura"><i class="fa fa-eye" style="font-size:12px;"></i></button><button type="button" class="btn btn-primary" style="display:flex;align-items:center;padding:0px 8px;margin-right:5px;" ng-click="grid.appScope.view_pdf(row.entity.id)" title="Generere pdf"><i class="fa fa-file-pdf-o" style="font-size:10px;"></i></button><button type="button" class="btn btn-primary" style="display:flex;align-items:center;padding:0px 8px;margin-right:5px;" ng-click="grid.appScope.download(row.entity.pdf_path)" title="Download faktura" ng-if="row.entity.pdf_path"><i class="fa fa-download" style="font-size:10px;"></i></button></div>'
            }],
            paginationPageSizes: [25, 40, 60],
            paginationPageSize: 40
        };

    }


    $scope.rowDblClick = function (row) {
        console.log(row);
    }

    $scope.view_pdf = function (invoice_id) {
        bsLoadingOverlayService.start();
        $http.post("/admin-api/make_invoice_pdf", { invoice_id: invoice_id, type: 0 }).success(function (data) {
            $scope.$evalAsync(($scope) => {
                bsLoadingOverlayService.stop();
                var link = document.createElement('a');
                link.download = 'invoice.pdf';
                link.href = '/images/invoice_pdf/' + data.pdf_url;
                link.click();
            });
        });
    }

    $http.get("/admin-api/get_employer_transactions").success(function (data) {

        $scope.$evalAsync(($scope) => {

            $scope.gridOptions.data = data;
            $scope.indeterminate = false;
            bsLoadingOverlayService.stop();
        });
    });
});
