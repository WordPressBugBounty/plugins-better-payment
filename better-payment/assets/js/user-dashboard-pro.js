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

/***/ "./src/js/user-dashboard-pro.js"
/*!**************************************!*\
  !*** ./src/js/user-dashboard-pro.js ***!
  \**************************************/
() {

eval("{/**\n * User Dashboard: the behaviour of the cards only Pro renders.\n *\n * - the jQuery UI datepicker on the analytics report's custom date range fields\n *   (`.bp-datepicker`);\n * - closing that date range modal (`.bp-modal`) from its background, close and\n *   cancel buttons;\n * - \"View All\" on the recurring and split subscription cards, which opens the\n *   Subscriptions tab.\n *\n * Everything binds on document ready, after the synchronous handlers of any script\n * on the page: an older Lite still handles \"View All\" in its common.js, and the\n * default-prevented check below then keeps the tab from being opened twice.\n *\n * Pro ships this file as src/js/user-dashboard.js. Lite keeps a copy,\n * src/js/user-dashboard-pro.js. Lite: transitional copy for Better Payment Pro <= 1.4.3;\n * delete when ProCompatibility::MIN_PRO_VERSION >= 1.4.4. Pro copy:\n * better-payment-pro/src/js/user-dashboard.js. Slug: user_dashboard_pro_scripts.\n * Change both, or Pro's FrontendAssetsMirrorTest fails.\n */\n(function ($) {\n  $(document).ready(function () {\n    if ($.fn.datepicker) {\n      $('.bp-datepicker').datepicker({\n        dateFormat: 'dd-mm-yy'\n      });\n    }\n    $(document).on('click', '.better-payment .modal-background, .better-payment .modal-close, .better-payment .delete, .better-payment .bp-modal .cancel-button', function (e) {\n      e.preventDefault();\n      $(this).closest('.bp-modal .modal').removeClass('is-active');\n    });\n    $(document).on('click', '.better-payment .bp-view_all-btn', function (e) {\n      if (e.isDefaultPrevented()) {\n        return;\n      }\n      e.preventDefault();\n      $('.better-payment .bp--sidebar-nav.subscriptions-tab').trigger('click');\n    });\n  });\n})(jQuery);\n\n//# sourceURL=webpack://better-payment/./src/js/user-dashboard-pro.js?\n}");

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/js/user-dashboard-pro.js"]();
/******/ 	
/******/ })()
;