(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("angular"));
	else if(typeof define === 'function' && define.amd)
		define(["angular"], factory);
	else {
		var a = typeof exports === 'object' ? factory(require("angular")) : factory(root["angular"]);
		for(var i in a) (typeof exports === 'object' ? exports : root)[i] = a[i];
	}
})(window, function(__WEBPACK_EXTERNAL_MODULE_angular__) {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/index.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/Measurer.ts":
/*!*************************!*\
  !*** ./src/Measurer.ts ***!
  \*************************/
/*! exports provided: Measurer */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Measurer", function() { return Measurer; });
var Measurer = /** @class */ (function () {
    function Measurer() {
    }
    Measurer.measureTextWidth = function (text, font) {
        var canvas = Measurer.canvas || (Measurer.canvas = document.createElement('canvas'));
        var context = canvas.getContext('2d');
        context.font = font;
        var metrics = context.measureText(text);
        return metrics.width;
    };
    Measurer.measureRoundedTextWidth = function (text, font) {
        var width = Measurer.measureTextWidth(text, font);
        return Math.floor(width) + 1;
    };
    return Measurer;
}());



/***/ }),

/***/ "./src/UiGridAutoFitColumnsDirective.ts":
/*!**********************************************!*\
  !*** ./src/UiGridAutoFitColumnsDirective.ts ***!
  \**********************************************/
/*! exports provided: UiGridAutoFitColumnsDirective */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "UiGridAutoFitColumnsDirective", function() { return UiGridAutoFitColumnsDirective; });
function UiGridAutoFitColumnsDirective(uiGridAutoFitColumnsService) {
    return {
        replace: true,
        priority: 0,
        require: '^uiGrid',
        scope: false,
        compile: function () {
            return {
                pre: function ($scope, $elm, $attrs, uiGridCtrl) {
                    uiGridAutoFitColumnsService.initializeGrid(uiGridCtrl.grid);
                }
            };
        }
    };
}
UiGridAutoFitColumnsDirective.$inject = ['uiGridAutoFitColumnsService'];


/***/ }),

/***/ "./src/UiGridAutoFitColumnsService.ts":
/*!********************************************!*\
  !*** ./src/UiGridAutoFitColumnsService.ts ***!
  \********************************************/
/*! exports provided: UiGridAutoFitColumnsService */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "UiGridAutoFitColumnsService", function() { return UiGridAutoFitColumnsService; });
/* harmony import */ var _Measurer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Measurer */ "./src/Measurer.ts");
/* harmony import */ var _UiGridMetrics__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UiGridMetrics */ "./src/UiGridMetrics.ts");


var UiGridAutoFitColumnsService = /** @class */ (function () {
    function UiGridAutoFitColumnsService($q) {
        this.$q = $q;
        this.gridMetrics = new _UiGridMetrics__WEBPACK_IMPORTED_MODULE_1__["UiGridMetrics"]();
    }
    UiGridAutoFitColumnsService.prototype.initializeGrid = function (grid) {
        grid.registerColumnBuilder(this.colAutoFitColumnBuilder.bind(this));
        grid.registerColumnsProcessor(this.columnsProcessor.bind(this), 60);
        UiGridAutoFitColumnsService.defaultGridOptions(grid.options);
    };
    UiGridAutoFitColumnsService.defaultGridOptions = function (gridOptions) {
        // true by default
        gridOptions.enableColumnAutoFit = gridOptions.enableColumnAutoFit !== false;
    };
    UiGridAutoFitColumnsService.prototype.colAutoFitColumnBuilder = function (colDef, col, gridOptions) {
        var promises = [];
        if (colDef.enableColumnAutoFit === undefined) {
            //TODO: make it as col.isResizable()
            if (UiGridAutoFitColumnsService.isResizable(colDef)) {
                colDef.enableColumnAutoFit = gridOptions.enableColumnAutoFit;
            }
            else {
                colDef.enableColumnAutoFit = false;
            }
        }
        return this.$q.all(promises);
    };
    UiGridAutoFitColumnsService.isResizable = function (colDef) {
        return !colDef.hasOwnProperty('width');
    };
    UiGridAutoFitColumnsService.prototype.columnsProcessor = function (renderedColumnsToProcess, rows) {
        var _this = this;
        if (!rows.length) {
            return renderedColumnsToProcess;
        }
        // TODO: respect existing colDef options
        // if (col.colDef.enableColumnAutoFitting === false) return;
        var optimalWidths = {};
        renderedColumnsToProcess.forEach(function (column) {
            if (column.colDef.enableColumnAutoFit) {
                var columnKey_1 = column.field || column.name;
                optimalWidths[columnKey_1] = _Measurer__WEBPACK_IMPORTED_MODULE_0__["Measurer"].measureRoundedTextWidth(column.displayName, _this.gridMetrics.getHeaderFont()) + _this.gridMetrics.getHeaderButtonsWidth();
                rows.forEach(function (row) {
                    var cellText = row.grid.getCellDisplayValue(row, column);
                    var currentCellWidth = _Measurer__WEBPACK_IMPORTED_MODULE_0__["Measurer"].measureRoundedTextWidth(cellText, _this.gridMetrics.getCellFont());
                    var optimalCellWidth = currentCellWidth > 300 ? 300 : currentCellWidth;
                    if (optimalCellWidth > optimalWidths[columnKey_1]) {
                        optimalWidths[columnKey_1] = optimalCellWidth;
                    }
                });
                column.colDef.width = optimalWidths[columnKey_1] + _this.gridMetrics.getPadding() + _this.gridMetrics.getBorder();
                column.updateColumnDef(column.colDef, false);
            }
        });
        return renderedColumnsToProcess;
    };
    UiGridAutoFitColumnsService.$inject = ['$q', '$filter', '$parse'];
    return UiGridAutoFitColumnsService;
}());



/***/ }),

/***/ "./src/UiGridMetrics.ts":
/*!******************************!*\
  !*** ./src/UiGridMetrics.ts ***!
  \******************************/
/*! exports provided: UiGridMetrics */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "UiGridMetrics", function() { return UiGridMetrics; });
/* harmony import */ var angular__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! angular */ "angular");
/* harmony import */ var angular__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(angular__WEBPACK_IMPORTED_MODULE_0__);

var UiGridMetrics = /** @class */ (function () {
    function UiGridMetrics() {
    }
    UiGridMetrics.prototype.getHeaderFont = function () {
        if (this.headerFont) {
            return this.headerFont;
        }
        var header = document.querySelector('.ui-grid-header-cell .ui-grid-cell-contents');
        if (!header) {
            throw new Error('not found: .ui-grid-header-cell .ui-grid-cell-contents');
        }
        var headerStyle = getComputedStyle(header);
        this.headerFont = UiGridMetrics.getFontStringFrom(headerStyle);
        return this.headerFont;
    };
    UiGridMetrics.prototype.getCellFont = function () {
        if (this.cellFont) {
            return this.cellFont;
        }
        var cell = document.querySelector('.ui-grid-cell > .ui-grid-cell-contents');
        if (!cell) {
            var element = document.createElement('div');
            element.className = 'ui-grid-cell-contents';
            element.style.cssFloat = 'left';
            angular__WEBPACK_IMPORTED_MODULE_0__["element"](document.body).append(element);
            var cellStyle_1 = getComputedStyle(element);
            this.cellFont = UiGridMetrics.getFontStringFrom(cellStyle_1);
            angular__WEBPACK_IMPORTED_MODULE_0__["element"](element).remove();
            return this.cellFont;
        }
        var cellStyle = getComputedStyle(cell);
        this.cellFont = UiGridMetrics.getFontStringFrom(cellStyle);
        return this.cellFont;
    };
    UiGridMetrics.prototype.getPadding = function () {
        if (this.padding) {
            return this.padding;
        }
        var header = document.querySelector('.ui-grid-header-cell .ui-grid-cell-contents');
        if (!header) {
            throw new Error('not found: .ui-grid-header-cell .ui-grid-cell-contents');
        }
        var _a = getComputedStyle(header), paddingLeft = _a.paddingLeft, paddingRight = _a.paddingRight;
        this.padding = parseInt(paddingLeft) + parseInt(paddingRight);
        return this.padding;
    };
    UiGridMetrics.prototype.getBorder = function () {
        if (this.border) {
            return this.border;
        }
        var header = document.querySelector('.ui-grid-header-cell');
        if (!header) {
            throw new Error('not found: .ui-grid-header-cell');
        }
        var borderRightWidth = getComputedStyle(header).borderRightWidth;
        this.border = parseInt(borderRightWidth);
        return this.border;
    };
    UiGridMetrics.prototype.getHeaderButtonsWidth = function () {
        // TODO: lets be more precise
        var HEADER_BUTTONS_WIDTH = 25;
        return HEADER_BUTTONS_WIDTH;
    };
    UiGridMetrics.getFontStringFrom = function (_a) {
        var fontStyle = _a.fontStyle, fontVariant = _a.fontVariant, fontWeight = _a.fontWeight, fontSize = _a.fontSize, fontFamily = _a.fontFamily;
        // in FF cssStyle.font may be '' so we need to collect it manually
        // font: [font-style||font-variant||font-weight] font-size [/line-height] font-family | inherit
        return fontStyle + " " + fontVariant + " " + fontWeight + " " + fontSize + " " + fontFamily;
    };
    return UiGridMetrics;
}());



/***/ }),

/***/ "./src/index.ts":
/*!**********************!*\
  !*** ./src/index.ts ***!
  \**********************/
/*! exports provided: default, Measurer, UiGridAutoFitColumnsDirective, UiGridAutoFitColumnsService, UiGridMetrics */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var angular__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! angular */ "angular");
/* harmony import */ var angular__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(angular__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _UiGridAutoFitColumnsService__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UiGridAutoFitColumnsService */ "./src/UiGridAutoFitColumnsService.ts");
/* harmony import */ var _UiGridAutoFitColumnsDirective__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UiGridAutoFitColumnsDirective */ "./src/UiGridAutoFitColumnsDirective.ts");
/* harmony import */ var _Measurer__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Measurer */ "./src/Measurer.ts");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Measurer", function() { return _Measurer__WEBPACK_IMPORTED_MODULE_3__["Measurer"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "UiGridAutoFitColumnsDirective", function() { return _UiGridAutoFitColumnsDirective__WEBPACK_IMPORTED_MODULE_2__["UiGridAutoFitColumnsDirective"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "UiGridAutoFitColumnsService", function() { return _UiGridAutoFitColumnsService__WEBPACK_IMPORTED_MODULE_1__["UiGridAutoFitColumnsService"]; });

/* harmony import */ var _UiGridMetrics__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./UiGridMetrics */ "./src/UiGridMetrics.ts");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "UiGridMetrics", function() { return _UiGridMetrics__WEBPACK_IMPORTED_MODULE_4__["UiGridMetrics"]; });




/* harmony default export */ __webpack_exports__["default"] = (angular__WEBPACK_IMPORTED_MODULE_0__["module"]('ui.grid.autoFitColumns', ['ui.grid'])
    .service('uiGridAutoFitColumnsService', _UiGridAutoFitColumnsService__WEBPACK_IMPORTED_MODULE_1__["UiGridAutoFitColumnsService"])
    .directive('uiGridAutoFitColumns', _UiGridAutoFitColumnsDirective__WEBPACK_IMPORTED_MODULE_2__["UiGridAutoFitColumnsDirective"])
    .name);






/***/ }),

/***/ "angular":
/*!**************************!*\
  !*** external "angular" ***!
  \**************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE_angular__;

/***/ })

/******/ });
});
//# sourceMappingURL=ui-grid.auto-fit-columns.umd.js.map