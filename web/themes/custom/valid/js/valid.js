/**
 * @file
 *   JS behaviors for the validd theme.
 */
(function ($, Drupal) {

  Drupal.behaviors.valid = {
    attach: function (context, settings) {
      var treeviewMenu = $('.app-menu', context);

      // Toggle Sidebar.
      $('[data-toggle="sidebar"]', context).click(function(event) {
        event.preventDefault();
        $('.app').toggleClass('sidenav-toggled');
      });

      // Set any link that has a href that matches any part of the beginning
      // of the active path.
      $('ul.app-menu a').each(function() {
        if (window.location.pathname.startsWith($(this).attr('href'))) {
          // Add an active class to it.
          $(this).addClass('active');

          // Check if it has a parent and expand it.
          $(this).parents('li.treeview').addClass('is-expanded');
        }
      });

      // Set any link that has a href that is an exact match of the active path.
      /*
      var activeMenuLink = $('ul.app-menu a[href^="' + window.location.pathname + '"]:first', context);

      // Check if an active menu link was found.
      if (activeMenuLink.length) {
        // Add an active class to it.
        $(activeMenuLink).addClass('active');

        // Check if it has a parent and expand it.
        $(activeMenuLink).parents('li.treeview').addClass('is-expanded');
      }
      */

      // Activate sidebar treeview toggle
      $("[data-toggle='treeview']", context).click(function(event) {
        event.preventDefault();
        if (!$(this).parent().hasClass('is-expanded')) {
          treeviewMenu.find("[data-toggle='treeview']").parent().removeClass('is-expanded');
        }
        $(this).parent().toggleClass('is-expanded');
      });

      // Set initial active toggle
      $("[data-toggle='treeview.'].is-expanded", context).parent().toggleClass('is-expanded');

      // Activate bootstrip tooltips
      $("[data-toggle='tooltip']", context).tooltip();
    }
  };

})(jQuery, Drupal);
