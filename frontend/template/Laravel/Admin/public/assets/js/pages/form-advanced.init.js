/******/ (function(modules) { // webpackBootstrap
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
/******/ 	__webpack_require__.p = "/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 22);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./resources/js/pages/form-advanced.init.js":
/*!**************************************************!*\
  !*** ./resources/js/pages/form-advanced.init.js ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
Template Name: Skote - Responsive Bootstrap 4 Admin Dashboard
Author: Themesbrand
Version: 2.0
Website: https://themesbrand.com/
Contact: themesbrand@gmail.com
File: Form Advanced Js File
*/
!function ($) {
  "use strict";

  var AdvancedForm = function AdvancedForm() {};

  AdvancedForm.prototype.init = function () {
    // Select2
    $(".select2").select2({
      width: '100%'
    });
    $(".select2-limiting").select2({
      maximumSelectionLength: 2
    });
    $(".select2-search-disable").select2({
      minimumResultsForSearch: Infinity,
      width: '100%'
    });
    $('.select2-ajax').select2({
      ajax: {
        url: "https://api.github.com/search/repositories",
        dataType: 'json',
        delay: 250,
        data: function data(params) {
          return {
            q: params.term,
            // search term
            page: params.page
          };
        },
        processResults: function processResults(data, params) {
          // parse the results into the format expected by Select2
          // since we are using custom formatting functions we do not need to
          // alter the remote JSON data, except to indicate that infinite
          // scrolling can be used
          params.page = params.page || 1;
          return {
            results: data.items,
            pagination: {
              more: params.page * 30 < data.total_count
            }
          };
        },
        cache: true
      },
      placeholder: 'Search for a repository',
      minimumInputLength: 1,
      templateResult: formatRepo,
      templateSelection: formatRepoSelection
    });

    function formatRepo(repo) {
      if (repo.loading) {
        return repo.text;
      }

      var $container = $("<div class='select2-result-repository clearfix'>" + "<div class='select2-result-repository__avatar'><img src='" + repo.owner.avatar_url + "' /></div>" + "<div class='select2-result-repository__meta'>" + "<div class='select2-result-repository__title'></div>" + "<div class='select2-result-repository__description'></div>" + "<div class='select2-result-repository__statistics'>" + "<div class='select2-result-repository__forks'><i class='fa fa-flash'></i> </div>" + "<div class='select2-result-repository__stargazers'><i class='fa fa-star'></i> </div>" + "<div class='select2-result-repository__watchers'><i class='fa fa-eye'></i> </div>" + "</div>" + "</div>" + "</div>");
      $container.find(".select2-result-repository__title").text(repo.full_name);
      $container.find(".select2-result-repository__description").text(repo.description);
      $container.find(".select2-result-repository__forks").append(repo.forks_count + " Forks");
      $container.find(".select2-result-repository__stargazers").append(repo.stargazers_count + " Stars");
      $container.find(".select2-result-repository__watchers").append(repo.watchers_count + " Watchers");
      return $container;
    }

    function formatRepoSelection(repo) {
      return repo.full_name || repo.text;
    }

    function formatState(state) {
      if (!state.id) {
        return state.text;
      }

      var baseUrl = "/assets/images/flags/select2";
      var $state = $('<span><img src="' + baseUrl + '/' + state.element.value.toLowerCase() + '.png" class="img-flag" /> ' + state.text + '</span>');
      return $state;
    }

    ;
    $(".select2-templating").select2({
      templateResult: formatState
    }); //colorpicker start

    $('.colorpicker-default').colorpicker({
      format: 'hex'
    });
    $('.colorpicker-rgba').colorpicker();
    $('#colorpicker-horizontal').colorpicker({
      color: "#88cc33",
      horizontal: true
    });
    $('#colorpicker-inline').colorpicker({
      color: '#DD0F20',
      inline: true,
      container: true
    }); // Time Picker

    $('#timepicker').timepicker({
      icons: {
        up: 'mdi mdi-chevron-up',
        down: 'mdi mdi-chevron-down'
      }
    });
    $('#timepicker2').timepicker({
      showMeridian: false,
      icons: {
        up: 'mdi mdi-chevron-up',
        down: 'mdi mdi-chevron-down'
      }
    });
    $('#timepicker3').timepicker({
      minuteStep: 15,
      icons: {
        up: 'mdi mdi-chevron-up',
        down: 'mdi mdi-chevron-down'
      }
    }); //Bootstrap-TouchSpin

    var defaultOptions = {}; // touchspin

    $('[data-toggle="touchspin"]').each(function (idx, obj) {
      var objOptions = $.extend({}, defaultOptions, $(obj).data());
      $(obj).TouchSpin(objOptions);
    });
    $("input[name='demo3_21']").TouchSpin({
      initval: 40,
      buttondown_class: "btn btn-primary",
      buttonup_class: "btn btn-primary"
    });
    $("input[name='demo3_22']").TouchSpin({
      initval: 40,
      buttondown_class: "btn btn-primary",
      buttonup_class: "btn btn-primary"
    });
    $("input[name='demo_vertical']").TouchSpin({
      verticalbuttons: true
    }); //Bootstrap-MaxLength

    $('input#defaultconfig').maxlength({
      warningClass: "badge badge-info",
      limitReachedClass: "badge badge-warning"
    });
    $('input#thresholdconfig').maxlength({
      threshold: 20,
      warningClass: "badge badge-info",
      limitReachedClass: "badge badge-warning"
    });
    $('input#moreoptions').maxlength({
      alwaysShow: true,
      warningClass: "badge badge-success",
      limitReachedClass: "badge badge-danger"
    });
    $('input#alloptions').maxlength({
      alwaysShow: true,
      warningClass: "badge badge-success",
      limitReachedClass: "badge badge-danger",
      separator: ' out of ',
      preText: 'You typed ',
      postText: ' chars available.',
      validate: true
    });
    $('textarea#textarea').maxlength({
      alwaysShow: true,
      warningClass: "badge badge-info",
      limitReachedClass: "badge badge-warning"
    });
    $('input#placement').maxlength({
      alwaysShow: true,
      placement: 'top-left',
      warningClass: "badge badge-info",
      limitReachedClass: "badge badge-warning"
    });
  }, //init
  $.AdvancedForm = new AdvancedForm(), $.AdvancedForm.Constructor = AdvancedForm;
}(window.jQuery), //Datepicker
function ($) {
  "use strict";

  $.AdvancedForm.init();
}(window.jQuery);
$(function () {
  'use strict';

  var $date = $('.docs-date');
  var $container = $('.docs-datepicker-container');
  var $trigger = $('.docs-datepicker-trigger');
  var options = {
    show: function show(e) {
      console.log(e.type, e.namespace);
    },
    hide: function hide(e) {
      console.log(e.type, e.namespace);
    },
    pick: function pick(e) {
      console.log(e.type, e.namespace, e.view);
    }
  };
  $date.on({
    'show.datepicker': function showDatepicker(e) {
      console.log(e.type, e.namespace);
    },
    'hide.datepicker': function hideDatepicker(e) {
      console.log(e.type, e.namespace);
    },
    'pick.datepicker': function pickDatepicker(e) {
      console.log(e.type, e.namespace, e.view);
    }
  }).datepicker(options);
  $('.docs-options, .docs-toggles').on('change', function (e) {
    var target = e.target;
    var $target = $(target);
    var name = $target.attr('name');
    var value = target.type === 'checkbox' ? target.checked : $target.val();
    var $optionContainer;

    switch (name) {
      case 'container':
        if (value) {
          value = $container;
          $container.show();
        } else {
          $container.hide();
        }

        break;

      case 'trigger':
        if (value) {
          value = $trigger;
          $trigger.prop('disabled', false);
        } else {
          $trigger.prop('disabled', true);
        }

        break;

      case 'inline':
        $optionContainer = $('input[name="container"]');

        if (!$optionContainer.prop('checked')) {
          $optionContainer.click();
        }

        break;

      case 'language':
        $('input[name="format"]').val($.fn.datepicker.languages[value].format);
        break;
    }

    options[name] = value;
    $date.datepicker('reset').datepicker('destroy').datepicker(options);
  });
  $('.docs-actions').on('click', 'button', function (e) {
    var data = $(this).data();
    var args = data.arguments || [];
    var result;
    e.stopPropagation();

    if (data.method) {
      if (data.source) {
        $date.datepicker(data.method, $(data.source).val());
      } else {
        result = $date.datepicker(data.method, args[0], args[1], args[2]);

        if (result && data.target) {
          $(data.target).val(result);
        }
      }
    }
  });
});

/***/ }),

/***/ 22:
/*!********************************************************!*\
  !*** multi ./resources/js/pages/form-advanced.init.js ***!
  \********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! F:\wamp\www\themesbrand\Backend\Skote-Laravel+HTML\Skote_v2.0.0\Laravel\Skote-Laravel-Vertical\resources\js\pages\form-advanced.init.js */"./resources/js/pages/form-advanced.init.js");


/***/ })

/******/ });