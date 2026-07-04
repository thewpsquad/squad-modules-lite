/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./admin/common/admin-notices.ts":
/*!***************************************!*\
  !*** ./admin/common/admin-notices.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _home_alamin_Sites_wp_products_development_wp_content_plugins_squad_modules_src_admin_common_notices_review_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./admin/common/notices/review.ts */ "./admin/common/notices/review.ts");
/* harmony import */ var _home_alamin_Sites_wp_products_development_wp_content_plugins_squad_modules_src_admin_common_notices_pro_activation_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./admin/common/notices/pro-activation.ts */ "./admin/common/notices/pro-activation.ts");
/* harmony import */ var _home_alamin_Sites_wp_products_development_wp_content_plugins_squad_modules_src_admin_common_notices_discount_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./admin/common/notices/discount.ts */ "./admin/common/notices/discount.ts");




/***/ }),

/***/ "./admin/common/common.ts":
/*!********************************!*\
  !*** ./admin/common/common.ts ***!
  \********************************/
/***/ (() => {



/***/ }),

/***/ "./admin/common/notices/discount.ts":
/*!******************************************!*\
  !*** ./admin/common/notices/discount.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);


jquery__WEBPACK_IMPORTED_MODULE_0___default()($ => {
  // Collect Plugin backend settings for Free and Pro from Admin Object.
  const settings = window.DiviSquadExtra || {};
  const bannerRoot = $('.divi-squad-notice div.divi-squad-banner.divi-squad-success-banner.welcome-discount');

  // search the banner when it exists.
  if (!!bannerRoot.length && !!settings.rest_api_wp) {
    const api = settings.rest_api_wp;

    /**
     * Send request to the backend server.
     *
     * @param {string}  requestType    - Request type.
     * @param {boolean} isBannerRemove - Remove banner.
     *
     * @return {void}
     */
    const sendRequest = (requestType, isBannerRemove = false) => {
      if (isBannerRemove) {
        bannerRoot.remove();
      }

      // Check if the request type exists.
      if (!Object.prototype.hasOwnProperty.call(api.routes, requestType)) {
        return;
      }

      // send request to the backed server.
      void _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
        path: `${api.namespace}${api.routes[requestType].root}`
      });
      // .then( ( response ) => window.console.log( response ) )
      // .catch( ( error ) => window.console.log( error ) );
    };

    // Add click event to the dismiss button.
    bannerRoot.find('button.notice-dismiss').on('click', function () {
      sendRequest('NoticeDiscountDone', true);
      return true;
    });
  }
});

/***/ }),

/***/ "./admin/common/notices/pro-activation.ts":
/*!************************************************!*\
  !*** ./admin/common/notices/pro-activation.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
// External dependencies


jquery__WEBPACK_IMPORTED_MODULE_0___default()($ => {
  // Collect Plugin backend settings for Free and Pro from Admin Object.
  const settings = window.DiviSquadExtra || {};
  const bannerRoot = $('.divi-squad-notice div.divi-squad-banner.pro-activation-notice');

  // search the banner when it exists.
  if (!!bannerRoot.length && !!settings.rest_api_wp) {
    const api = settings.rest_api_wp;

    /**
     * Send request to the backend server.
     *
     * @param {string}  requestType    - Request type.
     * @param {boolean} isBannerRemove - Remove banner.
     *
     * @return {void}
     */
    const sendRequest = (requestType, isBannerRemove = false) => {
      if (isBannerRemove) {
        bannerRoot.remove();
      }

      // Check if the request type exists.
      if (!Object.prototype.hasOwnProperty.call(api.routes, requestType)) {
        return;
      }

      // send request to the backed server.
      void _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
        path: `${api.namespace}${api.routes[requestType].root}`
      });
      // .then( ( response ) => window.console.log( response ) )
      // .catch( ( error ) => window.console.log( error ) );
    };

    // I don't want to provide any review for this plugin.
    bannerRoot.find('button.notice-dismiss').on('click', function () {
      sendRequest('NoticeProActivationClose', true);
      return true;
    });
  }
});

/***/ }),

/***/ "./admin/common/notices/review.ts":
/*!****************************************!*\
  !*** ./admin/common/notices/review.ts ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
// External dependencies


jquery__WEBPACK_IMPORTED_MODULE_0___default()($ => {
  // Collect Plugin backend settings for Free and Pro from Admin Object.
  const BackendSettings = window.DiviSquadExtra || {};
  const bannerRoot = $('.divi-squad-notice div.divi-squad-banner.divi-squad-review-banner');

  // search the banner when it exists.
  if (!!bannerRoot.length && !!BackendSettings.rest_api_wp) {
    const api = BackendSettings.rest_api_wp;

    /**
     * Send request to the backend server.
     *
     * @param {string}  requestType    - Request type.
     * @param {boolean} isBannerRemove - Remove banner.
     *
     * @return {void}
     */
    const sendRequest = (requestType, isBannerRemove = false) => {
      if (isBannerRemove) {
        bannerRoot.remove();
      }

      // Check if the request type exists.
      if (!Object.prototype.hasOwnProperty.call(api.routes, requestType)) {
        return;
      }

      // send request to the backed server.
      void _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()({
        path: `${api.namespace}${api.routes[requestType].root}`
      });
      // .then( ( response ) => window.console.log( response ) )
      // .catch( ( error ) => window.console.log( error ) );
    };

    // Remind me again next week
    bannerRoot.find('a.divi-squad-notice-close').on('click', function (event) {
      sendRequest('NoticeReviewNextWeek', true);
      event.preventDefault();
    });

    // I already review the plugin.
    bannerRoot.find('a.divi-squad-notice-already').on('click', function (event) {
      sendRequest('NoticeReviewDone', true);
      event.preventDefault();
    });

    // Save yes response for review.
    bannerRoot.find('a.divi-squad-notice-action-button').on('click', () => {
      sendRequest('NoticeReviewDone', true);
      return true;
    });

    // I ask support for this plugin.
    bannerRoot.find('a.support').on('click', function () {
      sendRequest('NoticeReviewAskSupportCount', true);
      return true;
    });

    // I don't want to provide any review for this plugin.
    bannerRoot.find('button.notice-dismiss').on('click', function () {
      sendRequest('NoticeReviewCloseCount', true);
      return true;
    });
  }
});

/***/ }),

/***/ "./admin/core/styles/common.scss":
/*!***************************************!*\
  !*** ./admin/core/styles/common.scss ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/***/ ((module) => {

"use strict";
module.exports = window["jQuery"];

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["apiFetch"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!******************************!*\
  !*** ./admin/core/common.ts ***!
  \******************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _home_alamin_Sites_wp_products_development_wp_content_plugins_squad_modules_src_admin_core_styles_common_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./admin/core/styles/common.scss */ "./admin/core/styles/common.scss");
/* harmony import */ var _home_alamin_Sites_wp_products_development_wp_content_plugins_squad_modules_src_admin_common_common_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./admin/common/common.ts */ "./admin/common/common.ts");
/* harmony import */ var _home_alamin_Sites_wp_products_development_wp_content_plugins_squad_modules_src_admin_common_common_ts__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_home_alamin_Sites_wp_products_development_wp_content_plugins_squad_modules_src_admin_common_common_ts__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _home_alamin_Sites_wp_products_development_wp_content_plugins_squad_modules_src_admin_common_admin_notices_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./admin/common/admin-notices.ts */ "./admin/common/admin-notices.ts");



})();

/******/ })()
;
//# sourceMappingURL=core-common.js.map