/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/common.js":
/*!**************************!*\
  !*** ./src/js/common.js ***!
  \**************************/
/***/ (() => {

eval(";\n(function ($) {\n  $(document).on('change', '.better-payment .payment-form-layout-3 .payment-method-checkbox input', function () {\n    if (this.checked) {\n      $('.better-payment .payment-form-layout-3 .payment-method-checkbox').removeClass('active');\n      $(this).parent().addClass(\"active\");\n      $('.better-payment .payment-form-layout-3 .payment-method-checkbox input').removeAttr('checked');\n      $(this).attr('checked', true);\n      let paypalBtnClassName = 'better-payment-paypal-bt';\n      let stripeBtnClassName = 'better-payment-stripe-bt';\n      let paystackBtnClassName = 'better-payment-paystack-bt';\n      let targetButtonClassName = $(this).hasClass('layout-payment-method-paypal') ? paypalBtnClassName : stripeBtnClassName;\n      targetButtonClassName = $(this).hasClass('layout-payment-method-paystack') ? paystackBtnClassName : targetButtonClassName;\n      $('.better-payment .' + paypalBtnClassName + ', .better-payment .' + stripeBtnClassName + ', .better-payment .' + paystackBtnClassName).addClass('is-hidden');\n      $('.better-payment .' + targetButtonClassName).removeClass('is-hidden');\n    }\n  });\n  $(document).on('change', '.better-payment .payment-form-layout-1 .payment-method-checkbox input, .better-payment .payment-form-layout-2 .payment-method-checkbox input, .better-payment .payment-method-item input', function (e) {\n    if (this.checked) {\n      let paypalBtnClassName = 'better-payment-paypal-bt';\n      let stripeBtnClassName = 'better-payment-stripe-bt';\n      let paystackBtnClassName = 'better-payment-paystack-bt';\n      let targetButtonClassName = $(this).hasClass('layout-payment-method-paypal') ? paypalBtnClassName : stripeBtnClassName;\n      targetButtonClassName = $(this).hasClass('layout-payment-method-paystack') ? paystackBtnClassName : targetButtonClassName;\n      $('.better-payment .' + paypalBtnClassName + ', .better-payment .' + stripeBtnClassName + ', .better-payment .' + paystackBtnClassName).addClass('is-hidden');\n      $('.better-payment .' + targetButtonClassName).removeClass('is-hidden');\n    }\n  });\n  $(document).on(\"click\", \".better-payment .bp-modal-button\", function (e) {\n    e.preventDefault();\n    let $this = $(this);\n    let modalWrap = $this.attr(\"data-targetwrap\");\n    let modalSelector = \".\" + modalWrap + \" .modal\";\n    $(modalSelector).addClass(\"is-active\");\n  });\n  $(document).on(\"click\", \".better-payment .modal-background, .better-payment .modal-close, .better-payment .delete, .better-payment .bp-modal .cancel-button\", function (e) {\n    e.preventDefault();\n    let $this = $(this);\n    let modalSelector = $this.closest(\".bp-modal .modal\");\n    $(modalSelector).removeClass(\"is-active\");\n  });\n})(jQuery);\n\n//# sourceURL=webpack://better-payment/./src/js/common.js?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/js/common.js"]();
/******/ 	
/******/ })()
;