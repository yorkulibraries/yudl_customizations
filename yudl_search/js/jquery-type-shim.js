/**
 * @file
 * Restores jQuery.type(), removed in jQuery 4.
 *
 * jquery-ui-slider-pips (used by the facets range slider) still calls
 * $.type(), so without this the year facet slider loses its labels.
 */
(function ($) {
  if (typeof $.type === 'function') {
    return;
  }

  const class2type = {};
  'Boolean Number String Function Array Date RegExp Object Error Symbol BigInt'
    .split(' ')
    .forEach((name) => {
      class2type[`[object ${name}]`] = name.toLowerCase();
    });

  $.type = function (obj) {
    if (obj == null) {
      return `${obj}`;
    }
    return typeof obj === 'object' || typeof obj === 'function'
      ? class2type[Object.prototype.toString.call(obj)] || 'object'
      : typeof obj;
  };
})(jQuery);
