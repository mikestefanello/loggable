/**
 * @file
 *   JS behaviors for the chart js integration.
 */
(function ($, Drupal) {

  Drupal.behaviors.chartJs = {
    attach: function (context, settings) {
      // Iterate the charts.
      $.each(settings.chartJs, function(id, config) {
        // Select the chart canvas.
        var canvas = $('canvas#' + id, context);

        // Check if the chart canvas is present.
        if (canvas.length) {
          // Activate the chart.
          var ctx = canvas.get(0).getContext("2d");
          new Chart(ctx, config);
        }
      });
    }
  };

})(jQuery, Drupal);
