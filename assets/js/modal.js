(function ($, __) {
  //setup cart items in localStorage to exclude from recommendation
  function lpr_cart_items() {
    $.ajax({
      method: "GET",
      url: lc_ajax_modal.url,
      data: {
        action: "lc_get_cart_items",
        nonce: lc_ajax_modal.nonce,
      },
    }).done(function (data) {
      localStorage.setItem("lpr_cart_items", data);
    });
  }

  lpr_cart_items();

  $(document.body).on(
    "added_to_cart removed_from_cart wc_fragments_refreshed",
    function () {
      lpr_cart_items();
    }
  );

  //modal plugin
  $.fn.lprModal = function (options) {
    var settings = $.extend(
      {
        action: "open", // option for modal open or close default: open
      },
      options
    );

    var that = this;

    // modal overlay
    var overlay = $('<div class="lpr-modal-overlay show"></div>');

    // open modal
    function openModal() {
      $('body').trigger('before_open_lpr_modal'); // event before modal open

      var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;

      that.addClass("show");

      $("body").css("paddingRight", scrollbarWidth);

      $("body").addClass("lpr-modal-opened").prepend(overlay);

      setTimeout(function () {
        that.addClass("fadeIn");
        overlay.addClass("fadeIn");
      }, 10);

      return false;
    }

    // close modal
    function closeModal() {
      that.removeClass("fadeIn");
      $(".lpr-modal-overlay").addClass("fadeIn");

      setTimeout(function () {
        that.removeClass("show");
        $(".lpr-modal-overlay").remove();
        $("body").css("paddingRight", 0);
        $("body").removeClass("lpr-modal-opened");
      }, 200);

      $('body').trigger('after_close_lpr_modal'); //event after modal close
    }

    // call modal open
    if (settings.action === "open") {
      openModal();
    }

    // call modal close
    if (settings.action === "close") {
      closeModal();
    }

    that.find(".lpr-modal-close, .lpr-close-modal").click(function (e) {
      closeModal();
      return false;
    });

    $(".lpr-modal").click(function (e) {
      if (this === e.target) {
        closeModal();
      }
    });
  };

  $(function () {
    // call modal
    $(document.body).on("added_to_cart", function (e, ...data) {
      
      const [, , buttonInfo] = data;
      var button = buttonInfo[0];

      var addedProductId = $(button).data("product_id");
      
      //if don't find product id from button data
      if(!addedProductId && $(button).closest('form').length) {
        addedProductId = $(button).closest('form').find('[name="add-to-cart"]').val();
      }

      //don't show modal inside modal
      if (!$(button).closest(".recommended-products-wrapper").length) {
        
        var modalId = "#lpr-modal-" + addedProductId;
        var $modal = $(modalId);

        if ($modal.length) {
          var $preloader = $modal.find(".loading-products");
          var $recommendationProductsWrapper = $modal.find(".recommended-products-wrapper");

          var recommendationProducts = $recommendationProductsWrapper.data("recommendation-ids");

          if (recommendationProducts) {
            recommendationProducts = recommendationProducts.toString();
            recommendationProducts = recommendationProducts.split(",").map(Number);
          }

          var addedProducts = localStorage.getItem("lpr_cart_items");

          if (addedProducts) {
            addedProducts = addedProducts.split(",").map(Number);
            recommendationProducts = recommendationProducts.filter(function (id) {
              return addedProducts.indexOf(id) < 0;
            });
          }

          // return if all recommendation product are already in cart
          if (!recommendationProducts.length) return;
          $('body, .quickview-wrapper .closeqv, .yith-quick-view-overlay, .mfp-wrap').click(); // to hide existing popup, quick view, etc
          $modal.lprModal();
          $preloader.show();

          $.ajax({
            method: "GET",
            url: lc_ajax_modal.url,
            data: {
              action: "fetch_modal_products",
              nonce: lc_ajax_modal.nonce,
              recommendation_items: recommendationProducts,
              layout_type: lc_ajax_modal.layout_type,
              variable_add_to_cart: lc_ajax_modal.variable_add_to_cart
            },
          }).done(function (data) {
            $preloader.hide();

            if (lc_ajax_modal.layout_type === "slider") {
              var owl = $modal.find(".recommended-products-slider").trigger("replace.owl.carousel", data);

                $total_items = owl.data('owl.carousel')._items.length
                $visible_items = owl.data('owl.carousel').options.items;
                
                owl.data('owl.carousel').options.loop = owl.data('owl.carousel').options.loop && $total_items > $visible_items
                owl.trigger("refresh.owl.carousel");
            } else {
              $recommendationProductsWrapper.html(data);
            }

            setTimeout(() =>{
              //message animation
              $modal.find('.message-text').addClass('lpr__animated animate__lpr_headShake');
            }, 200);

            //variable product swatch
            setTimeout(() => {
              $( '.lpr-modal .variations_form' ).each( function() {
                  $( this ).wc_variation_form();
              });   
              // woodmart theme vernation swatch
              if(window.woodmartThemeModule && woodmartThemeModule.swatchesVariations) {
                woodmartThemeModule.swatchesVariations();
              }

              $modal.find('.modal-heading').addClass('lpr__animated animate__lpr_headShake');
              $modal.find('.modal-heading-article').addClass('lpr__animated animate__lpr_headShake');
            }, 700);
          });
        }
      }else {
        var $productHeading = $(button).closest('.single-lpr').find('.woocommerce-loop-product__title'); //show notification for added product.
        var notificationText = ($productHeading.length) ? `${$productHeading.text()} ${__('has been added to cart.','leo-product-recommendations')}` : __('Item has been added to cart.','leo-product-recommendations'); 
        var topPosition = $(button).closest('.lpr-modal').find('.lpr-message').outerHeight();

        $notification_bar = $(button).closest('.lpr-modal').find('.lpr-purchase-notification'); 
        $notification_bar.css('top', topPosition).fadeIn(300).text(notificationText);
        setTimeout(() => {
           $notification_bar.fadeOut(600);
        }, 1500);
      }
    });
  });
})(jQuery, wp.i18n.__);