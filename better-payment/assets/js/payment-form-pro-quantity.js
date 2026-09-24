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

/***/ "./src/js/payment-form-pro-quantity.js"
/*!*********************************************!*\
  !*** ./src/js/payment-form-pro-quantity.js ***!
  \*********************************************/
() {

eval("{/**\n * Payment Form: the transaction details total on Pro layouts 4 and 5, which render\n * a quantity field (`.bp-custom-payment-amount-quantity`) beside the amount.\n *\n * Lite's better-payment.js writes the chosen amount into\n * `.bp-transaction-details-amount-text` and then triggers `better_payment_amount_changed`\n * on the document with that amount and its source: 'input' for the custom amount\n * field, 'radio' for a preset. This multiplies the total by the quantity.\n *\n * Pro ships this file as src/js/payment-form-quantity.js. Lite keeps a copy,\n * src/js/payment-form-pro-quantity.js. Lite: transitional copy for Better Payment\n * Pro <= 1.4.3; delete when ProCompatibility::MIN_PRO_VERSION >= 1.4.4. Pro copy:\n * better-payment-pro/src/js/payment-form-quantity.js. Slug: payment_form_pro_scripts.\n * Change both, or Pro's FrontendAssetsMirrorTest fails.\n */\n(function ($) {\n  $(document).on('better_payment_amount_changed', function (event, amount, source) {\n    const $quantity = $('.bp-custom-payment-amount-quantity');\n    if (!$quantity.length) {\n      return;\n    }\n    const quantity = source === 'radio' ? parseInt($quantity.val()) : parseFloat($quantity.val());\n    $('.bp-transaction-details-amount-text').text(amount * quantity);\n  });\n})(jQuery);\n\n//# sourceURL=webpack://better-payment/./src/js/payment-form-pro-quantity.js?\n}");

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/js/payment-form-pro-quantity.js"]();
/******/ 	
/******/ })()
;