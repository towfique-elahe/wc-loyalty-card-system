// public/js/loyalty-system.js

jQuery(document).ready(function ($) {
  // Floating widget toggle
  $(".wcls-widget-toggle").on("click", function () {
    $(".wcls-widget-content").toggle();
  });

  // Close widget when clicking outside
  $(document).on("click", function (event) {
    if (!$(event.target).closest(".wcls-floating-widget").length) {
      $(".wcls-widget-content").hide();
    }
  });

  // Points calculator on checkout
  $("#points_to_use").on("input", function () {
    var points = parseInt($(this).val()) || 0;
    var maxPoints = parseInt($(this).attr("max")) || 0;

    if (points > maxPoints) {
      $(this).val(maxPoints);
      points = maxPoints;
    }

    var value = points; // 1 point = 1 TK
    $("#points_value_display").text(woocommerce_price(value));
  });

  // Gift card balance check
  $("#check_gift_card").on("click", function () {
    var cardNumber = $("#gift_card_number").val();

    if (!cardNumber) {
      alert(wcls_strings.enter_card_number);
      return;
    }

    $.ajax({
      url: wcls_ajax.ajax_url,
      type: "POST",
      data: {
        action: "check_gift_card_balance",
        card_number: cardNumber,
        nonce: wcls_ajax.nonce,
      },
      beforeSend: function () {
        $("#check_gift_card")
          .prop("disabled", true)
          .text(wcls_strings.checking);
      },
      success: function (response) {
        if (response.success) {
          $("#gift_card_balance_display")
            .removeClass("error")
            .addClass("success")
            .html("<p>" + response.data.message + "</p>")
            .show();
        } else {
          $("#gift_card_balance_display")
            .removeClass("success")
            .addClass("error")
            .html("<p>" + response.data.message + "</p>")
            .show();
        }
      },
      complete: function () {
        $("#check_gift_card")
          .prop("disabled", false)
          .text(wcls_strings.check_balance);
      },
    });
  });

  // Helper function for WooCommerce price formatting
  function woocommerce_price(amount) {
    var formatted = amount.toFixed(2);

    // Check if WooCommerce price format exists
    if (typeof wc_price !== "undefined") {
      return wc_price(amount);
    }

    // Fallback formatting
    return wcls_strings.currency_symbol + " " + formatted;
  }

  // Handle privilege card purchase
  $(".wcls-buy-card form").on("submit", function (e) {
    // Allow form submission
    return true;
  });

  // Tooltips for points info
  $(".wcls-points-info-icon").hover(
    function () {
      $(this).next(".wcls-tooltip").fadeIn(200);
    },
    function () {
      $(this).next(".wcls-tooltip").fadeOut(200);
    },
  );

  // Copy gift card number to clipboard
  $(".wcls-copy-card").on("click", function () {
    var cardNumber = $(this).data("card");

    // Create temporary input
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(cardNumber).select();

    try {
      document.execCommand("copy");
      $(this).next(".wcls-copy-success").fadeIn().delay(2000).fadeOut();
    } catch (err) {
      console.error("Copy failed", err);
    }

    $temp.remove();
  });

  // Points redemption confirmation
  $("#place_order").on("click", function (e) {
    var pointsToUse = $("#points_to_use").val();

    if (pointsToUse && parseInt(pointsToUse) > 0) {
      if (
        !confirm(
          wcls_strings.confirm_redemption.replace("{points}", pointsToUse),
        )
      ) {
        e.preventDefault();
      }
    }
  });

  // Initialize any counters
  $(".wcls-counter").each(function () {
    var $this = $(this);
    var target = parseInt($this.data("target")) || 0;
    var current = 0;
    var increment = target > 100 ? Math.ceil(target / 100) : 1;

    var timer = setInterval(function () {
      current += increment;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      $this.text(current);
    }, 20);
  });

  // Tab switching in my account
  $(".wcls-tabs .tab-link").on("click", function (e) {
    e.preventDefault();

    var tabId = $(this).data("tab");

    $(".wcls-tabs .tab-link").removeClass("active");
    $(this).addClass("active");

    $(".wcls-tab-content").removeClass("active");
    $("#" + tabId).addClass("active");
  });

  // Handle AJAX points update in real-time
  function updatePointsBalance() {
    $.ajax({
      url: wcls_ajax.ajax_url,
      type: "POST",
      data: {
        action: "get_points_balance",
        nonce: wcls_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          $(".wcls-points-balance-display").text(response.data.points);
        }
      },
    });
  }

  // Update points every 30 seconds on points page
  if ($(".wcls-points-page").length) {
    setInterval(updatePointsBalance, 30000);
  }
});

// Localization strings
var wcls_strings = {
  enter_card_number: "Please enter a gift card number",
  checking: "Checking...",
  check_balance: "Check Balance",
  confirm_redemption: "Are you sure you want to redeem {points} points?",
  currency_symbol: wcls_ajax.currency_symbol || "TK",
};
