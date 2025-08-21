"use strict";
(function () {
  if (!Array.prototype.indexOfPropertyValue) {
    Array.prototype.indexOfPropertyValue = function (prop, value) {
      for (var index = 0; index < this.length; index++) {
        if (this[index][prop]) {
          if (this[index][prop] === value) {
            return index;
          }
        }
      }
      return -1;
    };
  }
})();

function htmlEntities(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

/**
 * Shows the form tab for the given platform
 * @param  {object} platform
 * @param  {bool} [allowRemoval=true] Allow the tab to removed after showing it (ie show the remove button)
 */
function showPlatformTab(platform, allowRemoval) {
  $("#li" + platform).css("display", "");
  if (typeof allowRemoval === "undefined" || allowRemoval === true) {
    $("#li" + platform)
      .find("i.glyphicon-remove")
      .show();
  }
  $(format("a[data-list-id='{}']", platform)).hide();
  var emptyPlatformList = $("#dropdown li > a")
    .toArray()
    .map((k, v) => $(k).css("display") == "none")
    .reduce((a, b) => a && b, true);
  if (emptyPlatformList) $("#dropdown").css("display", "none");
  toggleDisableFormElementsByPlatform(platform, false);
}

/**
 * Makes the supplied platform tab active
 * @param  {string} platform name of the platform tab
 */
function gotoPlatformTab(platform) {
  showPlatformTab(platform);
}

/**
 * Closes the platform tab
 * @param  {string} platform name of the platform tab to close
 */
function closePlatformTab(platform) {
  $("#li" + platform).css("display", "none");
  var emptyPlatformList = $("#dropdown li > a")
    .toArray()
    .map((k, v) => $(k).css("display") == "none")
    .reduce((a, b) => a && b, true);
  $(format("a[data-list-id='{}']", platform)).show();
  if (emptyPlatformList) $("#dropdown").css("display", "");
  toggleDisableFormElementsByPlatform(platform, true);
  gotoPlatformTab("common");
}

/**
 * Gets a list of open platform tabs
 * @return {array} Array of platform tab names
 */
function getOpenPlataformTabs() {
  var open = $(".nav > li:visible")
    // From the visible tabs
    .map((k, v) => v.id.substr(2))
    // Get ids without "li"
    .filter((k, v) => v != "dropdown".substr(2));
  // Remove dropdown tab from list
  return open.toArray();
}

/**
 * Disables or enables form elements by tab name
 * @param  {string}  platform    name of the platform tab
 * @param  {Boolean} is_disabled true to disable, false to enable
 */
function toggleDisableFormElementsByPlatform(platform, is_disabled) {
  $("#" + platform + " .form-group").attr("disabled", is_disabled);
}

/**
 * Formats a string, python-style
 * Example: format("{} {}!","Hello","World") => "Hello World!"
 * @author Daniel Ramos <sentret@live.com>
 */
function format(string, args) {
  var args = Array.prototype.slice.call(arguments, 1);
  var i = 0;
  return string.replace(/\{\}/g, function () {
    return args[i++];
  });
}

/**
 * Wraps an error message with html to make it appear as a bootstrap error message
 * @param  {string} message message text
 * @return {string}         message wrapped in alert div
 */
function getBootstrapError(message) {
  return '<div class="alert alert-danger">' + message + "</div>";
}

/**
 * Puts the starting UPC value into the upc_seed input based on the selected dropdown value
 * @param  {string} platform name of the platform
 * @param  {string} value    starting UPC digits
 */
function seedUPC(platform, value) {
  var seed_field = $("#" + platform + "_upc_seed");
  seed_field.val(value);
}

/**
 * Gets the seed upc, fills in the rest, and puts it in the upc field
 * @param  {string} platform name of the platform where the upc exists
 */
function fillUPC(platform) {
  //generate a new random upc and add it to the UPC form field
  var seed_field = $(`#${platform}_upc_seed`);
  var upc_field = $(`#${platform}_upc`);
  var seed = seed_field.val();

  $.getJSON("api/channel-lister/build-upc", { prefix: seed })
    .done((response) => {
      upc_field.val(response.data).trigger("change");
    })
    .fail((response) => {
      console.error(response);
      alert(response.responseText);
    });
}

function getAndSetProductTypeOptions(category) {
  return $.getJSON("api/channel-lister/getAmazonProductTypeOptions/" + category)
    .done(function (response) {
      $("#product_type_amazon-id").html(response.data);
      if (category == "Clothing") {
        $("#product_type_amazon-id").removeProp("required");
      } else {
        $("#product_type_amazon-id").attr("required", "");
      }
      $("#product_type_amazon-id")
        .selectpicker("destroy")
        .selectpicker({ liveSearch: true });
    })
    .fail(function (response) {
      $("#product_type_amazon-id").html(
        "<option>Unable to get options for" + category + "</option>"
      );
    });
}

/**
 * Gets the amazon product type options for the supplied category
 * @param {string} category name of the Aamzon categroy
 */
function setProductTypeOptions(category) {
  if (typeof category == "string") {
    $.getJSON("api/channel-lister/getAmazonProductTypeOptions/" + category)
      .done(function (response) {
        $("#product_type_amazon-id").html(response.data);
        if (category == "Clothing") {
          $("#product_type_amazon-id").removeProp("required");
        } else {
          $("#product_type_amazon-id").attr("required", "");
        }
        $("#product_type_amazon-id")
          .selectpicker("destroy")
          .selectpicker({ liveSearch: true });
      })
      .fail(function (response) {
        $("#product_type_amazon-id").html(
          "<option>Unable to get options for" + category + "</option>"
        );
        // console.log(response);
      });
  }
}

function setAmazonCategoryOption(category) {
  $("#amazon_category-id").val(category).change();
}

//removes provided value from array
Array.prototype.clean = function (deleteValue) {
  for (var i = 0; i < this.length; i++) {
    if (this[i] === deleteValue) {
      this.splice(i, 1);
      i--;
    }
  }
  return this;
};

// init labels values
window.labels = ["DO NOT LIST - All Marketplaces", "Product Needs Review"];

/**
 * Updates the label value based on marketplace action dropdown menus
 */
function updateLabelInput() {
  window.labels.sort();
  var labels = window.labels.concat(
    $("select.marketplace_actions")
      .map(function () {
        return this.value;
      })
      .get()
  );
  labels = labels.clean("").join(", ");
  $("#Labels-id").val(labels);
}

/**
 * Returns true when supplied value is numeric
 * @param  {mixed}  n test value
 * @return {Boolean}   true when numeric, false otherwise
 */
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

/**
 * Gets the shipping cost and service based on shipping weight/dims and puts them in the
 * appropriate form fields
 */
function updateShipCost() {
  var all_fields_valid = true;
  var weight = $("#ship_weight-id").val();
  if (!isNumeric(weight) || weight == 0) {
    all_fields_valid = false;
  }
  var length = $("#ship_length-id").val();
  if (!isNumeric(length) || length == 0) {
    all_fields_valid = false;
  }
  var width = $("#ship_width-id").val();
  if (!isNumeric(width) || width == 0) {
    all_fields_valid = false;
  }
  var height = $("#ship_height-id").val();
  if (!isNumeric(height) || height == 0) {
    all_fields_valid = false;
  }
  var hazmat = $("#hazmat-id").prop("checked") ? 1 : 0;
  var packaging = $("#ship_packaging-id").val();
  if (all_fields_valid) {
    // gets shipping rate and service from api
    $.ajax({
      type: "POST",
      url: "api/channel-lister/calculateShippingCost",
      data: {
        weight: weight,
        length: length,
        width: width,
        height: height,
        hazmat: hazmat,
        packaging: packaging,
      },
      dataType: "json",
    })
      .done(function (response) {
        var rate = response.data;
        console.log(rate);
        $("#cost_shipping-api-error").hide();
        $("#cost_shipping-id").val("");
        if (!isNumeric(rate[0])) {
          if ($("#cost_shipping-api-error").length) {
            $("#cost_shipping-api-error")
              .html(
                rate[0] +
                  " for Weight: " +
                  weight +
                  " Length: " +
                  length +
                  " Width: " +
                  width +
                  " Height: " +
                  height +
                  " Hazmat: " +
                  hazmat +
                  " Packaging: " +
                  packaging
              )
              .show();
          } else {
            $(
              "<label id='cost_shipping-api-error' class='error' for='cost_shipping-id'>" +
                rate[0] +
                " for Weight: " +
                weight +
                " Length: " +
                length +
                " Width: " +
                width +
                " Height: " +
                height +
                " Hazmat: " +
                hazmat +
                " Packaging: " +
                packaging +
                "</label>"
            ).insertAfter("#cost_shipping-id");
          }
        } else {
          $("#cost_shipping-id").val(rate[0]);
        }
        $("#calculated_shipping_service-id").val(rate[1]);
      })
      .fail(function (response) {
        console.log(response);
        alert(response.responseText);
      });
  } else {
    $("#cost_shipping-id").val("");
    $("#calculated_shipping_service-id").val("");
  }
}

/**
 * Checks to see if url is in proper format
 * @param  {string}  url url to check
 * @return {Boolean}     true when supplied url is valid
 */
function isValidUrl(url) {
  var urlPattern =
    "(https?|ftp)://(www\\.)?(((([a-zA-Z0-9.-]+\\.){1,}[a-zA-Z]{2,4}|localhost))|((\\d{1,3}\\.){3}(\\d{1,3})))(:(\\d+))?(/([a-zA-Z0-9-._~!$&'()*+,;=:@/]|%[0-9A-F]{2})*)?(\\?([a-zA-Z0-9-._~!$&'()*+,;=:/?@]|%[0-9A-F]{2})*)?(#([a-zA-Z0-9._-]|%[0-9A-F]{2})*)?";
  urlPattern = "^" + urlPattern + "$";
  var regex = new RegExp(urlPattern);
  return regex.test(url);
}

/**
 * Runs JS functions on form inputs, called when a new tab is added
 */
function runTabInitFunctions(platform) {
  $("#" + platform + " .editable-select").editableSelect();
  $("#" + platform + " input.form-group[maxlength]").maxlength({
    alwaysShow: true,
  });
}

function getChannelListerFieldsMarketplaceMap() {
  return $.ajax({
    url: "api/channel-lister/getChannelListerFieldsMarketplaceMap",
    type: "GET",
    dataType: "json",
  }).fail(function (error) {
    alert("Something went wrong. Check console for details.");
    console.log(error);
  });
}

function getAmazonProductTypeFromAmazonCategory(category) {
  return $.getJSON(
    "api/channel-lister/getAmazonProductTypeRequiredFields/" + category
  )
    .done(function (response) {
      var html = response.data;
      if (html !== "") {
        $("#amazon_category-id")
          .parent()
          .parent()
          .next()
          .after(
            $("<div id='product_type_amazon-required'>" + html + "</div>")
          );
      }
      $("#amazon_category-id")
        .parent()
        .parent()
        .next()
        .after($("<div id='product_type_amazon-required'>" + html + "</div>"));
    })
    .fail(function (response) {
      console.log(response);
      alert(response.responseText);
    });
}

function replaceFields(html, fieldsToRemove, id, targetId, removedFields) {
  let removedVals = [];
  if (fieldsToRemove !== undefined) {
    console.log(
      "must remove these attributes from form before appending new",
      fieldsToRemove
    );
    fieldsToRemove.forEach(function (v, k) {
      let ele = $(`[name = ${v}]`);
      let obj = {};
      obj.name = v;
      obj.value = ele.val();
      removedVals.push(obj);
      console.log("found the element to remove", ele);
      removedFields[v] = ele.closest("div[class^='form-group'");
      removedFields[v].remove();
    });
  } else {
    console.log("no attributes to remove for item type");
  }
  if (html != "") {
    $(`#${id}`).remove();
    console.log("removing id:", id);
    let new_element = `<div id='${id}'>${html}</div>`;
    console.log("targetId:", targetId);
    console.log(
      "appending to targetId.parent.parent:",
      $(`#${targetId}`).parent().parent()
    );
    // if (fieldsToRemove[0] == 'color_map_amazon') debugger;
    $(`#${targetId}`).parent().parent().append(new_element);
    //let's try to snag the values that might of been set in the old field and set the value in the new field
    console.log("removedVals", removedVals);
    removedVals.forEach(function (v, k) {
      $(`[name=${v.name}]`).val(v.value);
    });
    $(".selectpicker").selectpicker();
  }
  console.log("checking for attributes to add back to the dom");
  console.log("removedFields", removedFields);
  console.log("fieldsToRemove", fieldsToRemove);
  for (const k in removedFields) {
    if (Object.hasOwnProperty.call(removedFields, k)) {
      console.log("checking if we have add back", k);
      if (
        fieldsToRemove === undefined ||
        fieldsToRemove.indexOf(k) === undefined
      ) {
        console.log("removing if already on dom");
        $(`[name=${k}]`).closest("div[class^='form-group'").remove();
        console.log("adding back to the dom", k);
        $(`#${id}`).append(removedFields[k]);
        delete removedFields.k;
      }
    }
  }
  return removedFields;
}

/* End CAML Draft Functionality */

function eBayCategoryVariationSupported(catId, catMap) {
  if (catMap.hasOwnProperty(catId)) {
    console.log("Category " + catId + " does not support variations");
    if (!$("#ebay-cat-var-exclusion").length) {
      $("#ebay_categories-id").after(
        '<div class="alert alert-warning alert-dismissable" id="ebay-cat-var-exclusion" style="margin-bottom:10px" role="alert">Please note this category does not currently support variations.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'
      );
    }
  } else {
    console.log("category ", catId + " supports variations");
    $(".alert").alert("close");
  }
}

/* Begin Easter Egg stuff */

var easterEggRunSpeed = 5;
//easter eggs
function selectEasterEgg(egg) {
  console.log("Selected easter egg: " + egg);
  var easterEgg = $("#easter_egg");
  var submitButton = $("#submit_button");
  easterEgg.removeClass();
  switch (egg) {
    case "pikachu":
      easterEgg.addClass("pikachu");
      submitButton.prop("value", "I choose you Pikachu! (submit)");
      easterEggRunSpeed = 9;
      break;
    case "rick":
      easterEgg.addClass("rick_morty_c137");
      easterEggRunSpeed = 5;
      submitButton.prop("value", "Get Schwifty");
      break;
    case "nyan":
      easterEgg.addClass("nyan");
      easterEggRunSpeed = 5;
      submitButton.prop("value", "Submit right meow.");
      break;
    case "batman":
      easterEgg.addClass("batman_robin");
      submitButton.prop("value", "To the Database, Robin!");
      break;
    case "carlton":
      easterEgg.addClass("carlton");
      submitButton.prop("value", "Kick it in Bel Aire");
      easterEggRunSpeed = 5;
      break;
  }
}

function randomizeEasterEgg() {
  var r = Math.random();
  if (r < 0.3) {
    selectEasterEgg("pikachu");
  } else if (r < 0.5) {
    selectEasterEgg("batman");
  } else if (r < 0.7) {
    selectEasterEgg("nyan");
  } else if (r < 0.96) {
    selectEasterEgg("carlton");
  } else {
    selectEasterEgg("rick");
  }
}

function showEasterEgg() {
  //easter egg
  if ($("#easter_egg").hasClass("rick_morty_c137")) {
    return rickAndMorty();
  }
  console.log("Showing easter egg");
  var easterEgg = document.getElementById("easter_egg");
  easterEgg.style.display = "block";
  easterEgg.style.left = "-60px";
  window.scrollTo(0, document.body.scrollHeight);
  var interval = window.setInterval(function () {
    var easterEgg = document.getElementById("easter_egg");
    var pos = easterEgg.style.left;
    pos = parseInt(pos);
    if (pos > $(window).width()) {
      clearInterval(interval);
      easterEgg.style.display = "none";
      easterEgg.style.left = "-60px";
    } else {
      easterEgg.style.left = pos + easterEggRunSpeed + "px";
    }
  }, 33);
}

//removes the portals for the rick and morty easter egg by id
function removePortal(id) {
  var div_width = $("#rick-div").width();
  $("#" + id).animate(
    {
      width: "1",
      height: "1",
      "margin-right": div_width / 14 + "px",
      "margin-left": div_width / 14 + "px",
      "margin-top": div_width / 14 + "px",
    },
    1500,
    function () {
      $("#" + id).remove();
      if (id == " portal2") {
        $("#rick-div").next().remove();
        $("#rick-div").remove();
      }
    }
  );
}

//starts the rick and morty easter egg on submit
function rickAndMorty() {
  $("div#form").after(
    $(
      '<div style="background: none;" id="rick-div"></div><br class="clearfloat">'
    )
  );
  $("#submit_button").css({ position: "relative" });
  console.log("wabalubadubdub");
  var div_width = $("#rick-div").width();
  $("#rick-div").css({ float: "left", width: "100%" });
  $("#rick-div").append(
    $("<img>")
      .prop("src", "vendor/channel-lister/images/portal.gif")
      .prop("id", "portal1")
      .css({
        position: "relative",
        width: "10px",
        height: "10px",
        "margin-top": div_width / 14 + "px",
        "margin-left": div_width / 21 + "px",
        "margin-right": "-350px",
        float: "left",
      })
  );
  $("#portal1").animate(
    {
      width: div_width / 7,
      height: div_width / 7,
      "margin-left": "0px",
      "margin-top": "0px",
    },
    1500,
    "easeInOutElastic",
    function () {
      $("#rick-div").append(
        $("<img>")
          .prop("src", "vendor/channel-lister/images/rick-morty.gif")
          .prop("id", "rick")
          .css({
            position: "absolute",
            float: "left",
            "margin-left": div_width / 21 + "px",
            "margin-top": $("#rick-div").height() * 0.15 + "px",
            width: div_width / 14,
            height: div_width / 14,
            "margin-right": "0px",
          })
      );
      var width = $(window).width();
      $("#rick").animate(
        {
          left: div_width - div_width / 7 + "px",
        },
        7000,
        function () {
          $("#rick").fadeTo(2000, 0, function () {
            $("#rick").remove();
            removePortal("portal2");
          });
        }
      );
    }
  );
  setTimeout(function () {
    removePortal("portal1");
    addPortal();
  }, 3000);
}

//adds the second portal
function addPortal() {
  var div_width = $("#rick-div").width();
  $("#rick-div").append(
    $("<img>")
      .prop("src", "vendor/channel-lister/images/portal.gif")
      .prop("id", "portal2")
      .css({
        width: "10px",
        "margin-left": "0",
        "margin-top": div_width / 14 + "px",
        "margin-right": div_width / 21 + "px",
        height: "10px",
        float: "right",
      })
  );
  $("#portal2").animate(
    {
      width: div_width / 7,
      height: div_width / 7,
      "margin-top": "0px",
      "margin-right": "0px",
    },
    1500,
    "easeInOutElastic"
  );
}

/* End Easter Egg stuff */

window.modal; //global variable used for the forms in the update data table

$(document).ready(function () {
  $("#user_input").validate({
    ignore: "",
    rules: {
      UPC: {
        required: true,
        remote: {
          url: "api/channel-lister/is-upc-valid",
          type: "GET",
          data: {
            sku: $("[id='Inventory Number-id']").val(),
          },
        },
      },
    },
    invalidHandler: function (event, validator) {
      var err_el_id = validator.invalidElements()[0].id;
      console.log({
        err_el_id: err_el_id,
        validator: validator,
      });
      var platform = $("[id='" + err_el_id + "']")
        .closest(".platform-container")
        .attr("id");
      gotoPlatformTab(platform);
      $("[id='" + err_el_id + "']").focus();
    },
    submitHandler: function (form, event) {
      event.preventDefault();
      $.ajax({
        method: "POST",
        url: "api/channel-lister",
        data: $(form)
          .serialize()
          .replace(/(^|&)drafts-[a-zA-Z0-9_-]+=\d+(&|$)/, ""),
        dataType: "json",
      })
        .done(function (response) {
          console.log(response);
          window.open(response.download_url, "_blank");
        })
        .fail(function (response) {
          console.log(response);
          alert(response.responseText);
        });
    },
    focusInvalid: false,
  });

  randomizeEasterEgg();
  var pageLoad = [$.Deferred(), 0];
  var pageReady = pageLoad[0].promise();
  var newListing = true;

  //add each platform tab
  $.each(platforms, function (k, v) {
    $(v.id).prop("disabled", true);
    var ddadd = $("#dropdownadd").append(
      format(
        `<li>
            <a class="dropdown-item" data-list-id="{}" href="#">{}</a>
        </li>`,
        v.id,
        v.name
      )
    );
    $("#dropdown").before(
      format(
        `<li id="li{}" class="nav-item" style="display: none;" role="presentation">
            <a href="#{}" class="nav-link h-100" data-toggle="tab" data-target="#{}" role="tab" aria-controls="{}" aria-expanded="false">
                {}
                <i class="text-danger icon-x tab-close-btn" data-toggle="tooltip" title="Remove marketplace"></i>
            </a>
        </li>`,
        v.id,
        v.id,
        v.id,
        v.id,
        v.name
      )
    );
    $("#pantab").append(
      format(
        `<div class="tab-pane fade platform-container" id="{}" role="tabpanel" aria-labelledby="li{}">
        </div>`,
        v.id,
        v.id
      )
    );
    $("#li" + v.id)
      .find("i.icon-x")
      .click(function (e) {
        closePlatformTab(v.id);
        e.stopPropagation();
        window.labels.push("DO NOT LIST - " + v.name);
        updateLabelInput();
      });

    window.labels = window.labels.concat("DO NOT LIST - " + v.name);
    updateLabelInput();

    var glyphUp = $("#li" + v.id)
      .find("i.glyphicon-upload")
      .show();
    var glyphBan = $("#li" + v.id)
      .find("i.glyphicon-ban-circle")
      .hide();
    var glyphRemove = $("#li" + v.id)
      .find("i.glyphicon-remove")
      .hide();

    $.getJSON("api/channel-lister/get-form-data-by-platform/" + v.id)
      .done(function (d) {
        d = d.data;
        $("#" + v.id).append(d);
        closePlatformTab(v.id);
        pageLoad[1]++;
        if (pageLoad[1] == platforms.length) {
          pageLoad[0].resolve();
        }

        $("body").on(
          "change",
          "[id='action_select_" + v.id + "']",
          function (e) {
            var val = $(this).val();
            //console.log(val);
            if (
              val == "DO NOT LIST - " + v.name ||
              val == "Restricted - " + v.name
            ) {
              glyphUp.hide();
              glyphBan.show();
              //console.log($("#"+v.id+" .form-group"));
              $("#" + v.id + " .form-group").attr("disabled", true);
            } else {
              glyphUp.show();
              glyphBan.hide();
              $("#" + v.id + " .form-group").attr("disabled", false);
            }
            $("[id='action_select_" + v.id + "']").attr("disabled", false);
          }
        );
        runTabInitFunctions(v.id);
      })
      .fail(function (d) {
        console.error("Failed getting lister HTML", v.id, d);
        $("#li" + v.id).hide();
        ddadd.hide();
        pageLoad[0].reject(d);
      });
  });

  // Sets file value used in updating fields csv file upload
  $("#file-to-upload,#reorder-file-to-upload").on("change", function (e) {
    fileSelected({
      file: $(this).prop("files")[0],
    });
  });

  let uploadBtnId = null;

  // uploads the csv to modify channel_lister table
  $("#upload-btn,#reorder-upload-btn").on("click", function (e) {
    uploadBtnId = e.target.id;
    let fileId = $(this)
      .closest("div.form-group")
      .find("input[type='file']")[0].id;
    let url =
      uploadBtnId === "upload-btn"
        ? "api/channel-lister/updateChannelListerFieldsFromCsv"
        : "api/channel-lister/reorderChannelListerFieldsFromCsv";
    const msg =
      "Updating via csv will can delete fields from the CAML.\nWould you like to proceed?";
    if ($(`#${fileId}`).val() && confirm(msg)) {
      $(this).prop("disabled", true);
      uploadFile({
        file: $(`#${fileId}`).prop("files")[0],
        upload_url: url,
        progressFunc: uploadProgress,
        loadFunc: uploadComplete,
        errorFunc: uploadFailed,
        abortFunc: uploadCanceled,
      });
    }
  });

  // changes update button value to % of file uploaded
  function uploadProgress(e) {
    if (e.lengthComputable) {
      var percent_complete = Math.round((e.loaded * 100) / e.total);
      $(`#${uploadBtnId}`).prop("value", percent_complete.toString() + "%");
    } else {
      $(`#${uploadBtnId}`).prop("value", "???");
    }
  }

  // re-enables upload button when upload complete
  function uploadComplete(e) {
    $(`#${uploadBtnId}`).prop("disabled", false);
    $(`#${uploadBtnId}`).prop("value", "Upload");
    var json = JSON.parse(e.target.responseText);
    if (json.status == "success") {
      if (confirm("Update successful. Would you like to reload the page?")) {
        location.reload();
      }
    } else {
      alert("Failed to update fields");
    }
  }

  // re-enables upload button on fail
  function uploadFailed(e) {
    $(`#${uploadBtnId}`).prop("disabled", false);
    $(`#${uploadBtnId}`).prop("value", "Upload");
  }

  // re-enables upload button on upload cancel
  function uploadCanceled(e) {
    $(`#${uploadBtnId}`).prop("disabled", false);
    $(`#${uploadBtnId}`).prop("value", "Upload");
  }

  // Adds platform tab and adjusts labels
  $("#dropdownadd a").click(function (e) {
    var id = $(this).data("list-id");
    showPlatformTab(id);
    window.labels = window.labels.clean("DO NOT LIST - " + $(this).text());
    updateLabelInput();
  });

  $("#chief_approved-id").one("mouseover", function (e) {
    var today = new Date();
    var date = today.getDate();
    if (date % 5 == 0) {
      if (!$("#ball").length) {
        var size = $(this).parent().parent().width();
        var img = $("<img>")
          .prop("src", "images/tennis-bal.png")
          .prop("title", "get the ball")
          .prop("id", "ball")
          .css({ position: "absolute" });
        $(this).parent().after(img);
        var me = this;
        img.click(function () {
          img.stop();
          $(this).remove();
          var gif = $("<img>")
            .prop("src", "images/eastereggdog.gif")
            .prop("title", "Chief approves")
            .prop("id", "this");
          $(me).parent().after(gif);
          gif.click(function () {
            $(this).remove();
          });
        });
        moveRight(img, size);
      }
    }
  });

  function moveRight(img, size) {
    var dist = size * 0.8;
    img.stop();
    img.animate(
      { left: "+=" + dist },
      {
        duration: 3000,
        complete: function () {
          img.animate(
            { left: "-=" + dist },
            {
              complete: function () {
                img.remove();
              },
            }
          );
        },
      }
    );
  }

  // changes UPC prefix based on platform selected
  $("body").on("change", ".manufacturer_code_select", function () {
    var platform = $(this).data("platform");
    var value = $(this).find("option:selected").val();
    seedUPC(platform, value);
  });

  //fills upc field
  $("body").on("click", ".fill_upc", function () {
    fillUPC($(this).data("platform"));
  });

  // Sets 2 and 3 digit country code values based on selected country
  $("body").on("change", '[id="Country of Manufacture-id"]', function () {
    var country = $(this).val();
    if (typeof country === "undefined" || country.length < 1) {
      $("#country_of_origin_2_digit-id").val("");
      $("#country_of_origin_3_digit-id").val("");
      return;
    }
    $.getJSON("api/channel-lister/country-code-options/" + country + "/2")
      .done(function (response) {
        var digit2 = response.data;
        $("#country_of_origin_2_digit-id").val(digit2.trim());
      })
      .fail(function (response) {
        console.log(response);
        alert(response.responseText);
      });
    $.getJSON("api/channel-lister/country-code-options/" + country + "/3")
      .done(function (response) {
        var digit3 = response.data;
        $("#country_of_origin_3_digit-id").val(digit3.trim());
      })
      .fail(function (response) {
        console.log(response);
        alert(response.responseText);
      });
  });

  // gets required fields for selected amazon product type and adds them to form
  $("body").on("change", "#amazon_category-id", function () {
    var selected_cat = $("#amazon_category-id").val();
    $("#product_type_amazon-required").remove();
    if (typeof selected_cat === "string" && selected_cat.length > 0) {
      $.getJSON(
        "api/channel-lister/getAmazonProductTypeRequiredFields/" + selected_cat
      )
        .done(function (response) {
          var html = response.data;
          if (html !== "") {
            $("#amazon_category-id")
              .parent()
              .parent()
              .next()
              .after(
                $("<div id='product_type_amazon-required'>" + html + "</div>")
              );
          }
        })
        .fail(function (response) {
          console.log(response);
          alert(response.responseText);
        });
      setProductTypeOptions(selected_cat);
    }
  });

  // Adds comma separated list of values to input from checked boxes for commaseparated input type
  $("body").on(
    "change",
    'div.comma-sep-options input[type="checkbox"]',
    function () {
      var checked_count = $(this)
        .closest("div.comma-sep-options")
        .find('input[type="checkbox"]:checked').length;
      var limit = $(this)
        .closest("div.form-group")
        .find('input[type="text"]')
        .data("limit");
      if (checked_count <= limit) {
        var input = $(this)
          .closest("div.form-group")
          .find('input[type="text"]');
        var values = $(this)
          .closest("div.comma-sep-options")
          .find('input[type="checkbox"]:checked')
          .map(function () {
            return $(this).val();
          })
          .get();
        input.val(values.join(", "));
      } else {
        $(this).attr("checked", false);
      }
    }
  );

  // Updates labels value based on selected marketplace action
  $("body").on("change", "select.marketplace_actions", function () {
    updateLabelInput();
  });

  // runs validate function when a form value changes
  $("body").on(
    "change",
    "form#user_input select, form#user_input input, form#user_input textarea",
    function () {
      $(this).valid();
    }
  );

  // runs function to get ship cost/service when a field that affects it changes
  $("body").on(
    "change",
    "#ship_weight-id,#ship_length-id,#ship_width-id,#ship_height-id,#hazmat-id,#ship_packaging-id",
    function () {
      updateShipCost();
    }
  );

  // shows URL preview on URL fields if they contain a valid url
  $("body")
    .on("mouseover", "input[type=url]", function () {
      //console.log($(this).valid());
      if (isValidUrl($(this).val())) {
        $(this)
          .siblings(".iframe-wrap")
          .show()
          .find(".url-preview")
          .attr("src", $(this).val());
      }
    })
    .on("mouseout", "input[type=url]", function () {
      $(this).siblings(".iframe-wrap").hide();
    });

  runTabInitFunctions("common");

  // puts image urls from modal into main form
  $("body").on("click", "#image_modal_submit_button", function () {
    var validator = $("#image_map").validate();
    if ($("#image_map").valid()) {
      $("form#image_map img").each(function () {
        console.log(this);
        var img_url = $(this).attr("src");
        var field_name = $("#selector-" + $(this).attr("id") + "-id").val();
        $("#" + field_name + "-id").val(img_url);
      });
      $("div.modal button.close").trigger("click");
    }
    return false;
  });

  //this handles adding more bundle rows
  $("#add-component-button").on("click", function () {
    $.getJSON("api/channel-lister/add-bundle-component-row")
      .done(function (response) {
        $("#bundle-components-list").append(response.data);
        $(".remove-row").each(function () {
          $(this).on("click", function () {
            $(this).parent().parent().remove();
            buildBundleComponentString();
            buildSupplierCodeString();
          });
        });
      })
      .fail(function (response) {
        alert(response.responseText);
      });
  });

  $("#bundle-components-container").on(
    "change keyup focusout",
    "input.sku-bundle-input, input.sku-bundle-quantity",
    function () {
      buildBundleComponentString();
    }
  );

  $("#bundle-components-container").on(
    "change keyup focusout",
    "input.supplier-code",
    function () {
      buildSupplierCodeString();
    }
  );

  //handles prop65 stuff for amazon, showing and hiding warning name, and chemical type when applicable
  // for more info see https://sellercentral.amazon.com/gp/help/help.html?ie=UTF8&itemID=G202141960&
  $("#prop65-id").change(function () {
    if ($(this).val() == "true") {
      $("#prop65_warn_type-id").attr("required", true);
      $("#prop65-warning-type-container").removeClass("d-none");
      $("#prop65-chemical-name-container").removeClass("d-none");
      $("#prop65_warn_type-id option[value='default']").prop("selected", true);
      $("#prop65_warn_type-id").selectpicker("refresh");
    } else {
      $("#prop65_warn_type-id").attr("required", false);
      $("#prop65_chem_name-id").attr("required", false);
      $("#prop65_warn_type-id option[value='']").prop("selected", true);
      $("#prop65_chem_name-id option[value='']").prop("selected", true);
      $("#prop65_warn_type-id").selectpicker("refresh");
      $("#prop65_chem_name-id").selectpicker("refresh");
      $("#prop65-warning-type-container").addClass("d-none");
      $("#prop65-chemical-name-container").addClass("d-none");
    }
  });

  /**
   * Force chem_name warning if a chemical is chosen, by selecting chem_name and
   * disabling chem_name's siblings. A chemical is required if chem_name value is chosen,
   * and for some reason this sequence doesn't trigger the warning change event, so we set
   * that here as well.
   */
  $("#prop65_chem_name-id").change(function () {
    if ($("#prop65_chem_name-id option:selected").val() != "") {
      $("#prop65_warn_type-id").selectpicker("val", "chem_name");
      $("#prop65_chem_name-id").attr("required", true);
      $("#prop65_warn_type-id").selectpicker("refresh");
    } else {
      $("#prop65_warn_type-id option[value='default']").prop("selected", true);
      $("#prop65_chem_name-id").attr("required", false);
      $("#prop65_warn_type-id").selectpicker("refresh");
    }
  });

  /**
   * Requre a chemical to be chosen if chem_name warning is chosen
   */
  $("#prop65_warn_type-id").change(function () {
    if ($(this).val() == "chem_name") {
      $("#prop65_chem_name-id").attr("required", true);
      $("#prop65_chem_name-id-error").show();
    } else {
      $("#prop65_chem_name-id option[value='']").prop("selected", true);
      $("#prop65_chem_name-id").selectpicker("refresh");
      $("#prop65_chem_name-id").attr("required", false);
      $("#prop65_chem_name-id-error").hide();
    }
  });

  //clonesite tags start
  function addCloneSiteTag(tag, input_id) {
    console.log(input_id);
    var tags_input = $("#" + input_id);
    if (tags_input.val().length > 0) {
      tags_input.val(tags_input.val() + ", " + tag);
    } else {
      tags_input.val(tag);
    }
  }
  $("body").on("click", ".clonesite_tags", function () {
    var tag_list = $(this).children(".clonesite_tags_inner");
    console.log(tag_list);
    if (tag_list.length > 0) {
      if (tag_list.is(":visible")) {
        tag_list.hide();
      } else {
        tag_list.show();
      }
    }
  });

  $("body").on("click", ".clonesite_tag", function (event) {
    addCloneSiteTag(this.innerHTML, this.dataset.inputId);
    return false;
  });
  //clonesite tags end

  //Builds the the bundle component strings and places them in the bundle components input text field
  function buildBundleComponentString() {
    var sku_bundle = "";
    var id_counter = 0;
    $(".sku-bundle-input").each(function () {
      $(this).prop("id", "bundle-component-sku-id-" + id_counter);
      id_counter++;
      var sku_quantity = $(this)
        .parent()
        .parent()
        .find(".sku-bundle-quantity")
        .val();
      if (!($(this).val() == "" && sku_quantity == "")) {
        if (sku_bundle == "") {
          sku_bundle += $(this).val();
        } else {
          sku_bundle += "," + $(this).val();
        }
        sku_bundle += "=" + sku_quantity;
      }
    });
    $("[id='Bundle Components-id']").val(sku_bundle);
    $("[id='Bundle Components-id']").trigger("change");
  }

  // Builds the supplier code strings and places it in the supplier codes input text field
  function buildSupplierCodeString() {
    var supplier_code = "";
    $(".sku-bundle-input").each(function () {
      var supplier_codeBox = $(this).parent().parent().find(".supplier-code");
      if (supplier_code == "") {
        supplier_code += supplier_codeBox.val();
      } else {
        supplier_code += "," + supplier_codeBox.val();
      }
    });
    $("#supplier_code-id").val(supplier_code);
  }

  $('[id="Vary By-id"]').parent().addClass("d-none");
  $('[id="Parent SKU-id"]').parent().addClass("d-none");

  //checks for the change in the sku type dropdown menu
  //Adds the form fields for the selected values
  const skuTypeSelector = $.escapeSelector("SKU Type-id");
  $(`#${skuTypeSelector}`).on("change", function () {
    console.log("SKU Type changed to " + $(this).val());
    if ($(this).val() == "bundle") {
      console.log("displaying Bundle Components sections");
      $("#bundle-components-container").removeClass("d-none");
      $("#bundled-id").removeClass("d-none");
      $("[id='Bundle Components-id']").prop("required", true);
      $("[id='Bundle Components-id']").parent().addClass("required");
      $("#supplier_code-id").prop("readonly", true);
      buildBundleComponentString();
      buildSupplierCodeString();
    } else {
      if (!$("#bundle-components-container").hasClass("d-none")) {
        $("#bundle-components-container").addClass("d-none");
        $("#bundled-id").addClass("d-none");
        $('[id="Bundle Components-id"]').val("");
      }
      $("[id='Bundle Components-id']").removeProp("required").trigger("change");
      $("#bundled-id").removeClass("required");
      $("[id='Bundle Components-id']").parent().removeClass("required");
      $("#supplier_code-id").removeProp("readonly");
      $("#supplier_code-id").val("");
    }

    if ($(this).val() == "child" || $(this).val() == "parent") {
      $('[id="Vary By-id"]').parent().removeClass("d-none");
      $('[id="Parent SKU-id"]').parent().removeClass("d-none");
      $('[id="Vary By-id"]').prop("required", true);
      $("[id='Vary By-id']").parent().addClass("required");
      $("[id='Parent SKU-id']").parent().addClass("required");
      $('[id="Parent SKU-id"]').prop("required", true);
    } else {
      $('[id="Vary By-id"]').parent().addClass("d-none");
      $('[id="Parent SKU-id"]').parent().addClass("d-none");
      $('[id="Vary By-id"]').removeProp("required");
      $("[id='Vary By-id']").parent().removeClass("required");
      $('[id="Parent SKU-id"]').removeProp("required");
      $('[id="Parent SKU-id"]').parent().removeClass("required");
    }
  });

  // Validates form before submission
  $("#submit_button").click(function (e) {
    if ($("#prop65-id option:selected").val() === "true") {
      let newValue = $("#prop65_warn_type-id option:selected").html();
      let oldValue = $("#prop65_warn_type-id option:selected").val();
      $("#prop65_warn_type-id option:selected").val(newValue);
      if ($("#user_input").valid()) {
        showEasterEgg();
      } else {
        $("#prop65_warn_type-id option:selected").val(oldValue);
      }
    }
  });

  var ebayCatVarExclusions = null;

  //evenet delegation because the target element is dynamic
  //checks if the ebay category allows variations and displays a warning if so
  $("body").on("change keyup", "#ebay_categories-id", function (e) {
    let catId = $(this).val();
    if (ebayCatVarExclusions === null) {
      $.ajax({
        method: "POST",
        url: "api/channel-lister/getEbayCategoryVariationExclusions",
        dataType: "json",
      })
        .done(function (response) {
          ebayCatVarExclusions = JSON.parse(response.data);
          eBayCategoryVariationSupported(catId, ebayCatVarExclusions);
        })
        .fail(function (response) {
          console.log(response);
          alert(response.responseText);
        });
    } else {
      eBayCategoryVariationSupported(catId, ebayCatVarExclusions);
    }
  });

  //lets just remove submitting the form on enter as it's more of pain
  //keep enter for text areas so we can insert newlines
  $(window).keydown(function (e) {
    if (e.keyCode == 13) {
      if (e.target.nodeName.toLowerCase() === "textarea") {
        return true;
      }
      e.preventDefault();
      return false;
    }
  });

  // ===== UNIFIED DRAFT SYSTEM INTEGRATION =====

  // Global namespace for unified draft functions
  window.ChannelListerUnified = {
    // Enhanced form data collection that works across all tabs
    collectAllTabData: function () {
      const data = {
        common: {},
        marketplaces: {},
      };

      // Get all visible marketplace tabs
      const openTabs = getOpenPlataformTabs();

      openTabs.forEach(function (tab) {
        const tabData = {};
        const tabSelector = "#" + tab;

        // Collect form data from this tab
        $(
          tabSelector +
            " input, " +
            tabSelector +
            " select, " +
            tabSelector +
            " textarea"
        ).each(function () {
          const field = $(this);
          const name = field.attr("name");
          let value = field.val();

          if (name && value !== "") {
            if (field.is(":checkbox")) {
              value = field.is(":checked") ? "1" : "0";
            } else if (field.is(":radio") && !field.is(":checked")) {
              return; // Skip unchecked radio buttons
            }

            if (tab === "common") {
              data.common[name] = value;
            } else {
              tabData[name] = value;
            }
          }
        });

        // Store marketplace data
        if (tab !== "common" && Object.keys(tabData).length > 0) {
          data.marketplaces[tab] = tabData;
        }
      });

      // Add Amazon data if it exists and has been modified
      if (
        typeof window.currentProductType !== "undefined" &&
        window.currentProductType
      ) {
        const amazonData = {};
        $(
          ".amazon-generated-panel input, .amazon-generated-panel select, .amazon-generated-panel textarea"
        ).each(function () {
          const field = $(this);
          const name = field.attr("name");
          let value = field.val();

          if (name && value !== "") {
            if (field.is(":checkbox")) {
              value = field.is(":checked") ? "1" : "0";
            }
            amazonData[name] = value;
          }
        });

        if (Object.keys(amazonData).length > 0) {
          amazonData.product_type = window.currentProductType;
          amazonData.marketplace_id =
            window.currentMarketplaceId || "ATVPDKIKX0DER";
          data.marketplaces.amazon = amazonData;
        }
      }

      return data;
    },

    // Enhanced form population that works across all tabs
    populateAllTabData: function (formData) {
      // Populate common tab
      if (formData.common) {
        this.populateTabFields("#common", formData.common);
      }

      // Populate marketplace tabs
      if (formData.marketplaces) {
        Object.keys(formData.marketplaces).forEach((marketplace) => {
          if (marketplace === "amazon") {
            // Handle Amazon tab specially
            this.populateAmazonTab(formData.marketplaces.amazon);
          } else {
            // Handle other marketplace tabs
            const tabSelector = "#" + marketplace;
            if ($(tabSelector).length > 0) {
              this.populateTabFields(
                tabSelector,
                formData.marketplaces[marketplace]
              );
              // Make sure the tab is visible
              showPlatformTab(marketplace, false);
            }
          }
        });
      }
    },

    // Populate fields in a specific tab
    populateTabFields: function (tabSelector, data) {
      Object.keys(data).forEach((fieldName) => {
        const field = $(tabSelector + ' [name="' + fieldName + '"]');
        const value = data[fieldName];

        if (field.length > 0 && value !== null && value !== "") {
          if (field.is("select")) {
            field.val(value);
          } else if (field.is(":checkbox")) {
            field.prop(
              "checked",
              value === "true" || value === "1" || value === true
            );
          } else if (field.is(":radio")) {
            field.filter('[value="' + value + '"]').prop("checked", true);
          } else {
            field.val(value);
          }
          field.trigger("change");
        }
      });
    },

    // Special handling for Amazon tab population
    populateAmazonTab: function (amazonData) {
      if (!amazonData || !amazonData.product_type) return;

      // Show Amazon tab if it exists
      if ($("#liamazon").length > 0) {
        showPlatformTab("amazon", false);
        gotoPlatformTab("amazon");
      }

      // If Amazon product type search exists, use it
      if (typeof window.getAmazonListingRequirements === "function") {
        $("#amazon_product_type-id").val(amazonData.product_type);
        $("#amazon_product_type-searchbox").val(amazonData.product_type);

        // Load Amazon requirements and then populate
        window
          .getAmazonListingRequirements(amazonData.product_type)
          .done(() => {
            setTimeout(() => {
              this.populateTabFields(".amazon-generated-panel", amazonData);

              // Update Amazon draft system variables if they exist
              if (typeof window.currentProductType !== "undefined") {
                window.currentProductType = amazonData.product_type;
                window.currentMarketplaceId =
                  amazonData.marketplace_id || "ATVPDKIKX0DER";
              }
            }, 1000);
          });
      }
    },

    // Get summary of data across all tabs for display
    getDataSummary: function () {
      const data = this.collectAllTabData();
      const summary = {
        commonFields: Object.keys(data.common).length,
        marketplaceFields: 0,
        marketplaces: [],
      };

      Object.keys(data.marketplaces).forEach((marketplace) => {
        const fieldCount = Object.keys(data.marketplaces[marketplace]).length;
        summary.marketplaceFields += fieldCount;
        summary.marketplaces.push({
          name: marketplace,
          fields: fieldCount,
        });
      });

      return summary;
    },

    // Check if form has significant data
    hasSignificantData: function () {
      const summary = this.getDataSummary();
      return summary.commonFields > 0 || summary.marketplaceFields > 0;
    },

    // Clear all form data (for loading new drafts)
    clearAllForms: function () {
      // Clear common tab
      $("#common input, #common select, #common textarea").each(function () {
        const field = $(this);
        if (field.is(":checkbox") || field.is(":radio")) {
          field.prop("checked", false);
        } else {
          field.val("");
        }
      });

      // Clear marketplace tabs
      $(
        ".platform-container:not(#common) input, .platform-container:not(#common) select, .platform-container:not(#common) textarea"
      ).each(function () {
        const field = $(this);
        if (field.is(":checkbox") || field.is(":radio")) {
          field.prop("checked", false);
        } else {
          field.val("");
        }
      });

      // Clear Amazon generated panels
      $(".amazon-generated-panel").remove();

      // Reset Amazon variables
      if (typeof window.currentProductType !== "undefined") {
        window.currentProductType = null;
        window.currentListingId = null;
      }
    },
  };

  // Make unified functions available globally for the unified draft controls
  window.collectAllTabData = window.ChannelListerUnified.collectAllTabData.bind(
    window.ChannelListerUnified
  );
  window.populateAllTabData =
    window.ChannelListerUnified.populateAllTabData.bind(
      window.ChannelListerUnified
    );
  window.clearAllForms = window.ChannelListerUnified.clearAllForms.bind(
    window.ChannelListerUnified
  );
  window.getDataSummary = window.ChannelListerUnified.getDataSummary.bind(
    window.ChannelListerUnified
  );
  window.hasSignificantData =
    window.ChannelListerUnified.hasSignificantData.bind(
      window.ChannelListerUnified
    );

  $("#loading").hide();
});
