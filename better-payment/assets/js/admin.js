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

/***/ "./src/js/admin.js"
/*!*************************!*\
  !*** ./src/js/admin.js ***!
  \*************************/
() {

eval("{/**\n * Front-end helpers shared by the user-dashboard templates.\n *\n * This file used to drive the PHP admin: settings tabs and save, the\n * transactions list (filters, dropdowns, import/export, delete,\n * mark-as-completed), the onboarding progress bar and the quick-start video.\n * That admin is React now and none of its markup renders anywhere, so only what\n * live templates still use remains:\n *  - copy-to-clipboard on transaction ids and emails (the user-dashboard\n *    templates, the rows bp-dashboard-pagination.js builds, Pro's subscriptions tab);\n *  - the global toastr options (front-end payment toasts go through them).\n *\n * The jQuery UI datepicker on `.bp-datepicker` belonged to Pro's analytics-reports\n * tab and moved to Pro (src/js/user-dashboard.js); Lite's copy for older Pro is\n * src/js/user-dashboard-pro.js (Classes\\ProUserDashboardAssets).\n *\n * Enqueued on every front-end request as `bp-admin-settings` (Admin\\Menu).\n */\n(function ($) {\n  $(document).ready(function () {\n    'use strict';\n\n    // Copy Transaction Id\n    $(document).on('click', '.bp-copy-clipboard', bpTransactionIdCopy);\n    function bpTransactionIdCopy() {\n      let bpTxnCounter = $(this).attr('data-bp_txn_counter');\n      let node = 'bp_copy_clipboard_input_' + bpTxnCounter;\n      let nodeInfo = 'bp_copy_clipboard_info_' + bpTxnCounter;\n      node = document.getElementById(node);\n      if (document.body.createTextRange) {\n        const range = document.body.createTextRange();\n        range.moveToElementText(node);\n        range.select();\n        document.execCommand('copy');\n      } else if (window.getSelection) {\n        const selection = window.getSelection();\n        const range = document.createRange();\n        range.selectNodeContents(node);\n        selection.removeAllRanges();\n        selection.addRange(range);\n        document.execCommand('copy');\n        selection.removeAllRanges();\n      } else {\n        console.warn('Could not select text in node: Unsupported browser.');\n      }\n      $('.bp-copy-clipboard').attr('title', 'Copy').css('color', '#2a3256');\n      $(this).attr('title', 'Copied!').css('color', '#6e58f7');\n      $('#' + nodeInfo).removeClass('is-hidden').fadeIn().fadeOut(3000);\n    }\n\n    // Copy Email\n    $(document).on('click', '.bp-email-copy-clipboard', bpEmailCopy);\n    function bpEmailCopy() {\n      let bpTxnCounter = $(this).attr('data-bp_txn_counter');\n      let node = 'bp_email_copy_clipboard_input_' + bpTxnCounter;\n      let nodeInfo = 'bp_email_copy_clipboard_info_' + bpTxnCounter;\n      node = document.getElementById(node);\n      if (document.body.createTextRange) {\n        const range = document.body.createTextRange();\n        range.moveToElementText(node);\n        range.select();\n        document.execCommand('copy');\n      } else if (window.getSelection) {\n        const selection = window.getSelection();\n        const range = document.createRange();\n        range.selectNodeContents(node);\n        selection.removeAllRanges();\n        selection.addRange(range);\n        document.execCommand('copy');\n        selection.removeAllRanges();\n      } else {\n        console.warn('Could not select text in node: Unsupported browser.');\n      }\n      $('.bp-email-copy-clipboard').attr('title', 'Copy').css('color', '#2a3256');\n      $(this).attr('title', 'Copied!').css('color', '#6e58f7');\n      $('#' + nodeInfo).removeClass('is-hidden').fadeIn().fadeOut(3000);\n    }\n\n    //Helper Functions Starts\n    function toasterOptions() {\n      toastr.options = {\n        timeOut: '2000',\n        toastClass: 'font-size-md',\n        positionClass: 'toast-top-center',\n        showMethod: 'slideDown',\n        hideMethod: 'slideUp'\n      };\n    }\n    toasterOptions();\n    //Helper Functions Ends\n  });\n})(jQuery);\n\n//# sourceURL=webpack://better-payment/./src/js/admin.js?\n}");

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/js/admin.js"]();
/******/ 	
/******/ })()
;