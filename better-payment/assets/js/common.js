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

eval(";\n(function ($) {\n  $(document).on('change', '.better-payment .payment-form-layout-3 .payment-method-checkbox input', function () {\n    if (this.checked) {\n      $('.better-payment .payment-form-layout-3 .payment-method-checkbox').removeClass('active');\n      $(this).parent().addClass(\"active\");\n      $('.better-payment .payment-form-layout-3 .payment-method-checkbox input').removeAttr('checked');\n      $(this).attr('checked', true);\n      let paypalBtnClassName = 'better-payment-paypal-bt';\n      let stripeBtnClassName = 'better-payment-stripe-bt';\n      let paystackBtnClassName = 'better-payment-paystack-bt';\n      let targetButtonClassName = $(this).hasClass('layout-payment-method-paypal') ? paypalBtnClassName : stripeBtnClassName;\n      targetButtonClassName = $(this).hasClass('layout-payment-method-paystack') ? paystackBtnClassName : targetButtonClassName;\n      $('.better-payment .' + paypalBtnClassName + ', .better-payment .' + stripeBtnClassName + ', .better-payment .' + paystackBtnClassName).addClass('is-hidden');\n      $('.better-payment .' + targetButtonClassName).removeClass('is-hidden');\n    }\n  });\n  $(document).on('change', '.better-payment .payment-form-layout-1 .payment-method-checkbox input, .better-payment .payment-form-layout-2 .payment-method-checkbox input, .better-payment .payment-method-item input', function (e) {\n    if (this.checked) {\n      let paypalBtnClassName = 'better-payment-paypal-bt';\n      let stripeBtnClassName = 'better-payment-stripe-bt';\n      let paystackBtnClassName = 'better-payment-paystack-bt';\n      let targetButtonClassName = $(this).hasClass('layout-payment-method-paypal') ? paypalBtnClassName : stripeBtnClassName;\n      targetButtonClassName = $(this).hasClass('layout-payment-method-paystack') ? paystackBtnClassName : targetButtonClassName;\n      $('.better-payment .' + paypalBtnClassName + ', .better-payment .' + stripeBtnClassName + ', .better-payment .' + paystackBtnClassName).addClass('is-hidden');\n      $('.better-payment .' + targetButtonClassName).removeClass('is-hidden');\n    }\n  });\n  $(document).on(\"click\", \".better-payment .bp-modal-button\", function (e) {\n    e.preventDefault();\n    let $this = $(this);\n    let modalWrap = $this.attr(\"data-targetwrap\");\n    let modalSelector = \".\" + modalWrap + \" .modal\";\n    $(modalSelector).addClass(\"is-active\");\n  });\n  $(document).on(\"click\", \".better-payment .modal-background, .better-payment .modal-close, .better-payment .delete, .better-payment .bp-modal .cancel-button\", function (e) {\n    e.preventDefault();\n    let $this = $(this);\n    let modalSelector = $this.closest(\".bp-modal .modal\");\n    $(modalSelector).removeClass(\"is-active\");\n  });\n  $(document).on(\"click\", \".better-payment .user-dashboard-sidebar .bp--sidebar-nav\", function (e) {\n    e.preventDefault();\n    let $this = $(this);\n    let tabCommonSelector = '.better-payment .user-dashboard-sidebar .bp--sidebar-nav';\n    let tabContentCommonSelector = '.better-payment .bp--tab-conetnt-wrapper';\n    let tabName = $this.data(\"tab\");\n    $(tabCommonSelector).removeClass('active');\n    $(tabContentCommonSelector).addClass('d-none').fadeOut();\n    $this.addClass('active');\n    $(`.better-payment .${tabName}-tab-wrapper`).fadeIn(500).removeClass(\"d-none\");\n\n    // Hide sidebar on mobile\n    if (window.innerWidth < 768) {\n      if ($(e.target).closest('.bp--sidebar-nav-list').length) {\n        hideDashboardSidebar();\n      }\n    }\n  });\n  $(document).on(\"click\", \".better-payment .bp-view_all-btn\", function (e) {\n    e.preventDefault();\n    $(`.better-payment .bp--sidebar-nav.subscriptions-tab`).click();\n  });\n  $('.better-payment .bp-dashboard-hamburger').on('click', function (e) {\n    $('.better-payment-user-dashboard-sidebar').fadeIn(500);\n    $('.better-payment .bp-overlay').fadeIn(500);\n  });\n  $('.better-payment .bp-overlay').on('click', function (e) {\n    // Overlay click\n    if (e.target !== '.better-payment-user-dashboard-sidebar') {\n      hideDashboardSidebar();\n    }\n  });\n  function hideDashboardSidebar() {\n    $('.better-payment-user-dashboard-sidebar').fadeOut(500);\n    $('.better-payment .bp-overlay').fadeOut();\n  }\n})(jQuery);\n\n//# sourceURL=webpack://better-payment/./src/js/common.js?");

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