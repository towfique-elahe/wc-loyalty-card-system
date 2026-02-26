// public/js/loyalty-system.js

jQuery(document).ready(function ($) {

  // ---------------------------------------------------------------
  // Floating widget toggle
  // ---------------------------------------------------------------
  $(".wcls-widget-toggle").on("click", function () {
    $(".wcls-widget-content").toggle();
  });

  $(document).on("click", function (event) {
    if (!$(event.target).closest(".wcls-floating-widget").length) {
      $(".wcls-widget-content").hide();
    }
  });

  // ---------------------------------------------------------------
  // Checkout discount boxes (points & gift card as optional discounts)
  // ---------------------------------------------------------------

  // Live discount preview while typing in the points field
  $(document).on("input", "#wcls_points_to_use", function () {
    var pts = parseInt($(this).val()) || 0;
    var max = parseInt($(this).attr("max")) || 0;
    if (pts > max) {
      $(this).val(max);
      pts = max;
    }
    if (pts > 0) {
      $("#wcls-points-preview").text(wcls_ajax.currency_symbol + " " + pts.toFixed(2));
    } else {
      $("#wcls-points-preview").text("—");
    }
  });

  // Apply loyalty points
  $(document).on("click", "#wcls-apply-points", function () {
    var pts = parseInt($("#wcls_points_to_use").val()) || 0;
    var $btn = $(this);
    var $msg = $("#wcls-points-message");

    if (pts <= 0) {
      showMessage($msg, wcls_strings.enter_points, "error");
      return;
    }

    $btn.prop("disabled", true).text(wcls_strings.applying);

    $.ajax({
      url: wcls_ajax.ajax_url,
      type: "POST",
      data: {
        action: "wcls_apply_points",
        points: pts,
        nonce: wcls_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Reload checkout to re-render the applied state + updated totals
          location.reload();
        } else {
          showMessage($msg, response.data.message, "error");
          $btn.prop("disabled", false).text(wcls_strings.apply);
        }
      },
      error: function () {
        showMessage($msg, wcls_strings.error, "error");
        $btn.prop("disabled", false).text(wcls_strings.apply);
      },
    });
  });

  // Remove loyalty points
  $(document).on("click", "#wcls-remove-points", function () {
    $(this).prop("disabled", true);
    $.ajax({
      url: wcls_ajax.ajax_url,
      type: "POST",
      data: {
        action: "wcls_remove_points",
        nonce: wcls_ajax.nonce,
      },
      success: function () {
        location.reload();
      },
    });
  });

  // Apply gift card
  $(document).on("click", "#wcls-apply-gift-card", function () {
    var cardNumber = $.trim($("#wcls_gift_card_number").val());
    var $btn = $(this);
    var $msg = $("#wcls-gift-card-message");

    if (!cardNumber) {
      showMessage($msg, wcls_strings.enter_card_number, "error");
      return;
    }

    $btn.prop("disabled", true).text(wcls_strings.applying);

    $.ajax({
      url: wcls_ajax.ajax_url,
      type: "POST",
      data: {
        action: "wcls_apply_gift_card_checkout",
        card_number: cardNumber,
        nonce: wcls_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          location.reload();
        } else {
          showMessage($msg, response.data.message, "error");
          $btn.prop("disabled", false).text(wcls_strings.apply);
        }
      },
      error: function () {
        showMessage($msg, wcls_strings.error, "error");
        $btn.prop("disabled", false).text(wcls_strings.apply);
      },
    });
  });

  // Remove gift card
  $(document).on("click", "#wcls-remove-gift-card", function () {
    $(this).prop("disabled", true);
    $.ajax({
      url: wcls_ajax.ajax_url,
      type: "POST",
      data: {
        action: "wcls_remove_gift_card_checkout",
        nonce: wcls_ajax.nonce,
      },
      success: function () {
        location.reload();
      },
    });
  });

  // ---------------------------------------------------------------
  // Gift card balance check (standalone, e.g. on My Account page)
  // ---------------------------------------------------------------
  $(document).on("click", "#check_gift_card", function () {
    var cardNumber = $("#gift_card_number").val();
    var $btn = $(this);
    var $display = $("#gift_card_balance_display");

    if (!cardNumber) {
      alert(wcls_strings.enter_card_number);
      return;
    }

    $btn.prop("disabled", true).text(wcls_strings.checking);

    $.ajax({
      url: wcls_ajax.ajax_url,
      type: "POST",
      data: {
        action: "check_gift_card_balance",
        card_number: cardNumber,
        nonce: wcls_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          $display
            .removeClass("error")
            .addClass("success")
            .html("<p>" + response.data.message + "</p>")
            .show();
        } else {
          $display
            .removeClass("success")
            .addClass("error")
            .html("<p>" + response.data.message + "</p>")
            .show();
        }
      },
      complete: function () {
        $btn.prop("disabled", false).text(wcls_strings.check_balance);
      },
    });
  });

  // ---------------------------------------------------------------
  // Privilege card purchase form
  // ---------------------------------------------------------------
  $(".wcls-buy-card form").on("submit", function () {
    return true;
  });

  // ---------------------------------------------------------------
  // Copy gift card number to clipboard
  // ---------------------------------------------------------------
  $(".wcls-copy-card").on("click", function () {
    var cardNumber = $(this).data("card");
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

  // ---------------------------------------------------------------
  // Tooltips
  // ---------------------------------------------------------------
  $(".wcls-points-info-icon").hover(
    function () {
      $(this).next(".wcls-tooltip").fadeIn(200);
    },
    function () {
      $(this).next(".wcls-tooltip").fadeOut(200);
    }
  );

  // ---------------------------------------------------------------
  // Counter animation
  // ---------------------------------------------------------------
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

  // ---------------------------------------------------------------
  // Tab switching in My Account
  // ---------------------------------------------------------------
  $(".wcls-tabs .tab-link").on("click", function (e) {
    e.preventDefault();
    var tabId = $(this).data("tab");
    $(".wcls-tabs .tab-link").removeClass("active");
    $(this).addClass("active");
    $(".wcls-tab-content").removeClass("active");
    $("#" + tabId).addClass("active");
  });

  // ---------------------------------------------------------------
  // Live points balance update on My Account points page
  // ---------------------------------------------------------------
  function updatePointsBalance() {
    $.ajax({
      url: wcls_ajax.ajax_url,
      type: "POST",
      data: { action: "get_points_balance", nonce: wcls_ajax.nonce },
      success: function (response) {
        if (response.success) {
          $(".wcls-points-balance-display").text(response.data.points);
        }
      },
    });
  }

  if ($(".wcls-points-page").length) {
    setInterval(updatePointsBalance, 30000);
  }

  // ---------------------------------------------------------------
  // Helper: show inline message
  // ---------------------------------------------------------------
  function showMessage($el, message, type) {
    $el
      .removeClass("wcls-msg-success wcls-msg-error")
      .addClass(type === "error" ? "wcls-msg-error" : "wcls-msg-success")
      .html(message)
      .show();
  }
});

// ---------------------------------------------------------------
// Localisation strings (referenced before document.ready in some cases)
// ---------------------------------------------------------------
var wcls_strings = {
  enter_card_number: "Please enter a gift card number",
  enter_points: "Please enter the number of points to use",
  checking: "Checking...",
  check_balance: "Check Balance",
  applying: "Applying...",
  apply: "Apply",
  error: "Something went wrong. Please try again.",
  currency_symbol: (typeof wcls_ajax !== "undefined" && wcls_ajax.currency_symbol) ? wcls_ajax.currency_symbol : "TK",
};
