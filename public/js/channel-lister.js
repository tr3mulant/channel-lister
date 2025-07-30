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
  // console.log('show '+platform);
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
  // console.log('goto '+platform);
  showPlatformTab(platform);
  $(".tab-pane").removeClass("active");
  $(".nav > li").removeClass("active");
  $("#" + platform).addClass("active");
  $("#li" + platform).addClass("active");
}

/**
 * Closes the platform tab
 * @param  {string} platform name of the platform tab to close
 */
function closePlatformTab(platform) {
  // console.log('close '+platform);
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
  //console.log(platform);
  //console.log(is_disabled);
  $("#" + platform + " .form-control").attr("disabled", is_disabled);
  $("#" + platform + " .select-picker")
    .selectpicker("destroy")
    .selectpicker();
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
 * Takes the api response from ASDF and maps them to form fields
 * Passes images to api request to build image mapping modal
 */
// function mapNpiResponseToFormFields(data) {
//   var images = [];
//   var supplier_name = "";
//   var supplier_id = "";
//   var supplier_title = "";
//   var brand = "";
//   var upc_real = "";
//   var seller_cost = "";
//   var location = "";
//   var ship_weight = "";
//   var ship_length = "";
//   var ship_height = "";
//   var ship_width = "";
//   var ship_packaging = "";

//   supplier_name = data.data.supplier;
//   supplier_id = data.data.supplierID;
//   supplier_title = data.data.title;
//   brand = data.data.brand;
//   upc_real = data.data.upc_real;
//   seller_cost = data.data.seller_cost;
//   location = data.data.location;
//   ship_weight = data.data.ship_weight;
//   ship_length = data.data.ship_length;
//   ship_height = data.data.ship_height;
//   ship_width = data.data.ship_width;
//   ship_packaging = data.data.ship_packaging;

//   $('[id="Supplier Code-id"] option')
//     .filter(function () {
//       return $(this).html() == supplier_name;
//     })
//     .prop("selected", true)
//     .trigger("change");
//   $("#supplier_code-id").val(supplier_id);
//   $("#Item-id").val(supplier_title);
//   $('[id="Warehouse Location-id"]').val(data.data.location);
//   $('[id="Total Quantity-id').val(data.data.in_house_quantity);
//   if (Number(data.data.seller_cost) != 0) {
//     $('[id="Seller Cost-id"]').val(Number(data.data.seller_cost));
//   }
//   $("#Brand-id").val(brand);
//   $("#upc_real").val(upc_real);
//   $("#ship_weight-id").val(ship_weight);
//   $("#ship_length-id").val(ship_length);
//   $("#ship_height-id").val(ship_height);
//   $("#ship_width-id").val(ship_width);
//   $("select[id=ship_packaging-id]").val(ship_packaging.toLowerCase());
//   $("select[id=ship_packaging-id]").change();

//   $.getJSON("api/NewProductInterface/getImages", { id: data.data.id }).done(
//     function (response) {
//       if (response.status == "success") {
//         for (var i = 0; i < response.data.length; i++) {
//           images.push(response.data[i].data.url);
//         }
//         $.ajax({
//           type: "POST",
//           url: "api/channel-lister/getImageMapperModal",
//           data: {
//             images: images,
//           },
//           dataType: "json",
//         })
//           .done(function (response) {
//             var modal = $(response.data);
//             modal.modal();
//             modal.find("button.close").click(function () {
//               modal.modal("hide");
//               window.setTimeout(() => modal.delay(2000).remove(), 1000);
//             });
//           })
//           .fail(function (response) {
//             console.log(response);
//             alert(response.responseText);
//           });
//       } else {
//         console.log(response);
//         alert(response.responseText);
//       }
//     }
//   );
// }

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
  var seed_field = $("#" + platform + "_upc_seed");
  var upc_field = $("#" + platform + "_upc");
  var seed = seed_field.val();
  /*if (seed.length < 5) {
		alert('Our UPC operating procedure is now to utilize the first 5 real upc digits. Please double check you made a UPC using 5 starting digits.');
	}*/
  $.getJSON("api/Products/makeUpc", { upc_start: seed }).done(function (
    response
  ) {
    if (response.status == "success") {
      upc_field.val(response.data);
      $("#" + platform + "_upc").trigger("change");
    } else {
      alert(response.message);
    }
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
      .success(function (response) {
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
      .error(function (response) {
        console.log(response);
        alert(response.responseText);
      });
  } else {
    $("#cost_shipping-id").val("");
    $("#calculated_shipping_service-id").val("");
  }
}

//From http://stackoverflow.com/a/1186309
/*$.fn.serializeObject = function()
{
	var o = {};
	var a = this.serializeArray();
	$.each(a, function() {
		if (o[this.name] !== undefined) {
			if (!o[this.name].push) {
				o[this.name] = [o[this.name]];
			}
			o[this.name].push(this.value || '');
		} else {
			o[this.name] = this.value || '';
		}
	});
	return o;
};*/

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
  $("#" + platform + " .select-picker").selectpicker();
  $("#" + platform + " .editable-select").editableSelect();
  $("#" + platform + " input.form-control[maxlength]").maxlength({
    alwaysShow: true,
  });
}

/* Begin CAML Draft Functionality */

/**
 * The CAML Draft Table object
 */
// function CamlDraftTable() {
//   let me = this;
//   me.tableId = "drafts-fill-table";
//   me.api = null;
//   me.columns = null;

//   me.init = function () {
//     $.ajax({
//       type: "GET",
//       url: "api/Datatable/product_listings/channel_lister_drafts",
//       data: { action: "heading" },
//       dataType: "json",
//     })
//       .done(function (data) {
//         me.columns = data;
//         me.addColumnRenders();
//         me.addColumnDefs();
//         me.display();
//         me.api = $("#" + me.tableId).DataTable();
//       })
//       .fail(function (error) {
//         alert("Failed to get headings");
//         console.log("err", error);
//       });

//     $("#" + me.tableId + "-panel").one("shown.bs.collapse", function () {
//       $("#" + me.tableId)
//         .DataTable()
//         .columns.adjust();
//     });
//   };

//   me.addColumnRenders = function () {
//     let i = me.columns.indexOfPropertyValue("data", "form_data");
//     me.columns[i].render = function (data, type, row, meta) {
//       return type === "display" && data.length > 50
//         ? data.substr(0, 50) + "..."
//         : data;
//     };
//     i = me.columns.indexOfPropertyValue("data", "last_save");
//     me.columns[i].render = function (data, type, row, meta) {
//       return moment(data).format("lll");
//     };
//   };

//   me.addColumnDefs = function () {
//     me.columns[
//       me.columns.indexOfPropertyValue("data", "auction_title")
//     ].className = "dt-min-w-25";
//   };

//   me.display = function () {
//     let lastSaveIdx = me.columns.indexOfPropertyValue("data", "last_save");
//     return $("#" + me.tableId).DataTableExtended({
//       pageLength: 10,
//       lengthMenu: [10, 20, 50],
//       processing: true,
//       serverSide: true,
//       paging: true,
//       ordering: true,
//       order: [lastSaveIdx, "desc"],
//       scrollX: true,
//       ajax: {
//         url: "api/Datatable/product_listings/channel_lister_drafts",
//         type: "POST",
//       },
//       select: { toggleable: false },
//       columns: me.columns,
//       search: { regex: false },
//       searchByColumn: true,
//       downloadCSV: { addCols: ["id"] },
//       buttons: ["restoreDraft", "deleteDraft"],
//       dom: "lBftipr",
//     });
//   };
// }

//custom buttons for drafts fill table
// $.fn.dataTable.ext.buttons.restoreDraft = {
//   text: "Restore Draft",
//   titleAttr:
//     "Restore Draft - Click to recover your saved product draft for the CAML",
//   enabled: true,
//   extend: "selectedSingle",
//   name: "restoreDraft",
//   className: "restore-draft",
//   action: restoreDraft,
// };

// $.fn.dataTable.ext.buttons.deleteDraft = {
//   text: "Delete Draft",
//   titleAttr: "Delete Draft - Click to delete a previously saved draft",
//   enabled: true,
//   extend: "selectedSingle",
//   name: "deleteDraft",
//   className: "delete-draft",
//   action: deleteDraft,
// };

// function restoreDraft(e, dt, type, indexes) {
//   dt.button([".restore-draft"]).processing(true);
//   let rowData = dt.row({ selected: true }).data();
//   getCamlDraft({ id: rowData.DT_RowId }).then(function (response) {
//     if (response.status === "success") {
//       let formData = JSON.parse(response.data);
//       getChannelListerFieldsMarketplaceMap().then(function (data) {
//         let channelListerFields = data.data;
//         processFormDataForDraftRestore(formData, channelListerFields);
//         dt.button([".restore-draft"]).processing(false);
//       });
//     } else {
//       alert("Something went wrong. Check console for details.");
//       console.log(response);
//       dt.button([".restore-draft"]).processing(false);
//     }
//   });
// }

// function getCamlDraft(data) {
//   return $.ajax({
//     type: "POST",
//     url: "api/channel-lister/getCamlDraft",
//     data: data,
//     dataType: "json",
//   }).fail(function (response) {
//     alert("Something went wrong. Check console for details.");
//     console.log(response);
//   });
// }

// function deleteDraft(e, dt, type, indexes) {
//   dt.button([".delete-draft"]).processing(true);
//   let rowData = dt.row({ selected: true }).data();
//   $.ajax({
//     type: "POST",
//     url: "api/channel-lister/deleteCamlDraft",
//     data: { id: rowData.DT_RowId },
//     dataType: "json",
//   })
//     .done(function (response) {
//       if (response.status === "success") {
//         dt.draw(false);
//       } else {
//         alert("Something went wrong. Check console for details.");
//         console.log(response);
//       }
//     })
//     .fail(function (response) {
//       alert("Something went wrong. Check console for details.");
//       console.log(response);
//     })
//     .always(function () {
//       dt.button([".delete-draft"]).processing(false);
//     });
// }

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

// async function processFormDataForDraftRestore(
//   formData,
//   channelListerFields,
//   retries = 0
// ) {
//   if (retries > 9) {
//     console.log("too many retries");
//     console.log(formData);
//     return;
//   }
//   let activeMarketplaces = [];
//   let leftOvers = {};
//   let dropdownMenu = $("#dropdown");
//   let marketplace = null;
//   for (const name in formData) {
//     marketplace = channelListerFields[name];
//     if (
//       marketplace !== undefined &&
//       !activeMarketplaces.includes(marketplace)
//     ) {
//       activeMarketplaces.push(marketplace);
//       dropdownMenu
//         .find("[data-list-id='" + marketplace + "']")
//         .trigger("click");
//     }
//     let value = formData[name];
//     console.log(`starting processing for ${name}`);
//     await processFormElementForDraftRestore(name, value, marketplace).catch(
//       (err) => {
//         leftOvers[err.data.name] = err.data.value;
//       }
//     );
//     console.log("finished processing for " + name);
//   }
//   if (Object.keys(leftOvers).length) {
//     setTimeout(
//       () =>
//         processFormDataForDraftRestore(
//           leftOvers,
//           channelListerFields,
//           ++retries
//         ),
//       500
//     );
//   }
// }

// function processFormElementForDraftRestore(name, value, marketplace) {
//   let searchName = null;
//   let searchValue = null;
//   return new Promise(async (resolve, reject) => {
//     let v = value;
//     if (typeof value === "string" && value === "") {
//       return resolve(console.log(`skipping ${name} because it's empty`)); //lets skip empty strings
//     }
//     let hasSearchBox = false;
//     if (typeof value === "object" && value !== null) {
//       hasSearchBox = true;
//       v = value.name;
//       searchName = value.search_name;
//       searchValue = value.search_value;
//     }
//     let element = $(`[name='${name}']`);
//     if (!element.length) {
//       let rejectObj = {
//         data: {
//           name: name,
//           value: value,
//         },
//         status: "fail",
//         message: "element not found",
//       };
//       return reject(rejectObj);
//     }
//     switch (element[0].nodeName.toLowerCase()) {
//       case "input":
//         await handleInputFillForDraftRestore(
//           element,
//           v,
//           searchName,
//           searchValue,
//           marketplace
//         ).catch((err) => {
//           console.log("handleInputFillForDraftRestore error:", err);
//         });
//       case "select":
//         await handleSelectFillForDraftRestore(element, v).catch((err) => {
//           console.log("handleSelectFillForDraftRestore error:", err);
//         });
//       default:
//         element.val(v).change();
//         return resolve();
//     }
//   });
// }

// function handleInputFillForDraftRestore(
//   element,
//   v,
//   searchName,
//   searchValue,
//   marketplace
// ) {
//   return new Promise((resolve, reject) => {
//     element.val(v).change();
//     let type = element.attr("type");
//     if (type === "checkbox" && v === "on") {
//       element.attr("checked", "");
//     }
//     if (searchName !== null && searchValue !== null) {
//       $(`[id='${searchName}']`).val(searchValue).change();
//       switch (marketplace) {
//         case "amazon":
//           console.log("getAmazonAttributeInput", searchValue);
//           return getAmazonAttributeInput(searchValue).then(resolve());
//         case "sears":
//           return getSearsAttributeInput(v).then(resolve());
//         case "walmart":
//           let subcat = v;
//           let parts = searchValue.split("|");
//           if (parts.length > 1) {
//             let subcat = parts[1];
//           }
//           return getWalmartAttributeInput(v, subcat).then(resolve());
//         default:
//           return resolve();
//       }
//     } else {
//       return resolve();
//     }
//   });
// }

// function handleSelectFillForDraftRestore(element, v) {
//   return new Promise(async (resolve, reject) => {
//     let name = element.attr("name");
//     if (name === "amazon_category") {
//       element.val(v);
//       await getAndSetProductTypeOptions(v)
//         .then(getAmazonProductTypeFromAmazonCategory(v))
//         .then(resolve());
//     } else {
//       element.val(v).change();
//     }
//     element.selectpicker("refresh");
//     if (element.val() !== v) {
//       let rejectObj = {
//         data: {
//           name: name,
//           value: v,
//         },
//         status: "fail",
//         message: "element value not set properly",
//       };
//       reject(rejectObj);
//     }
//     resolve();
//   });
// }

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

// function saveCamlDraft(formData) {
//   return new Promise((resolve, reject) => {
//     $.ajax({
//       type: "POST",
//       url: "api/channel-lister/saveCamlDraft",
//       data: { form: formData },
//       dataType: "json",
//     })
//       .done(function (response) {
//         if (response.status !== "success") {
//           return reject(response);
//         }
//         return resolve(response);
//       })
//       .fail(function (response) {
//         return reject(response);
//       });
//   });
// }

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
      removedFields[v] = ele.closest("div[class^='form-control'");
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
    $(".select-picker").selectpicker();
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
        $(`[name=${k}]`).closest("div[class^='form-control'").remove();
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
    case "rocket":
      submitButton.prop("value", "Launch (to the database)");
      submitButton.on("click", function () {
        if ($("#user_input").valid()) {
          $("#launchbutton").remove();
          let html = `<br class='clearfloat'>
				<div class="row d-flex justify-content-center">
					<div class="col">
						<a id=\"launchbutton\" href=\"javascript:var%20KICKASSVERSION='2.0';var%20s%20=%20document.createElement('script');s.type='text/javascript';document.body.appendChild(s);s.src='listings_lib/kickass_rocket.js';void(0);\">Launch Kickass Rocket</a>
					</div>
				</div>
				<br class='clearfloat'>`;
          $("form").after(html);
          // $('form').after("<br class='clearfloat'><a id=\"launchbutton\" href=\"javascript:var%20KICKASSVERSION='2.0';var%20s%20=%20document.createElement('script');s.type='text/javascript';document.body.appendChild(s);s.src='listings_lib/kickass_rocket.js';void(0);\">Launch</a>");
        }
        // $("#launchbutton").remove();
        // $('form').after("<br class='clearfloat'><a id=\"launchbutton\" href=\"javascript:var%20KICKASSVERSION='2.0';var%20s%20=%20document.createElement('script');s.type='text/javascript';document.body.appendChild(s);s.src='listings_lib/kickass_rocket.js';void(0);\">Launch</a>");
      });
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
  } else if (r < 0.99) {
    selectEasterEgg("rick");
  } else {
    selectEasterEgg("rocket");
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
      .prop("src", "images/portal.gif")
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
          .prop("src", "images/rick-morty.gif")
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
      .prop("src", "images/portal.gif")
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
  var validator = $("#user_input").validate({
    ignore: "",
    rules: {
      UPC: {
        required: true,
        remote: {
          url: "api/Products/isValid",
          type: "GET",
          data: {
            upc: $(".upc_field").val(),
            sku: $("[id='Inventory Number-id']").val(),
          },
        },
      },
    },
    invalidHandler: function (event, validator) {
      var err_el_id = $(validator.invalidElements()[0]).context.id;
      console.log(err_el_id);
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
        url: "api/channel-lister/submitProductData",
        data: $(form)
          .serialize()
          .replace(/(^|&)drafts-[a-zA-Z0-9\-\_]+=\d+(&|$)/, ""), //ditch the stupid drafts table named fields
      })
        .success(function (response) {
          let r = JSON.parse(response);
          if (r.status !== "success") {
            console.log(r);
            alert(`Something went wrong!\n\n${r.message}`);
            return false;
          }
          let html = "";
          for (const [key, value] of Object.entries(r.data)) {
            html += value;
          }
          $("#databaseResponse").append(html);
        })
        .error(function (response) {
          console.log(response);
          alert(response.responseText);
        });
    },
    focusInvalid: false,
  });

  //   $.ajax({
  //     type: "GET",
  //     url: "api/channel-lister/build-modal-view",
  //     dataType: "json",
  //   }).done(function (response) {
  //     window.modal = response.data;
  //     var caml_draft_table = new CamlDraftTable();
  //     caml_draft_table.init();
  //     var caml_update_table = new CAMLUpdateTable();
  //     caml_update_table.init().then(function () {
  //       $("body").tooltip({
  //         selector: '[data-toggle="tooltip"]',
  //       });
  //     });
  //   });

  //   $("#save-draft-btn").click(function (e) {
  //     let me = $(this);
  //     let ogMsg = me.text();
  //     me.text("Saving Draft...");
  //     me.prop("disabled", true);
  //     console.log("disabled button");
  //     // let formData = $('#user_input').serializeArray();
  //     // formData.shift();//kill the CamlDraftFillTable 1st element that makes its way into the form data
  //     let formData = $("#user_input :not([name^='drafts-'])").serializeArray(); //grab all form elements other that stuff starting with drafts in the name prop

  //     //should iterate over the form list and graph any field-searchbox inputs for the search boxes
  //     formData.forEach(function (v, n, form) {
  //       if (v.value === "") {
  //         return;
  //       }
  //       let property = v.name + "-searchbox";
  //       console.log("searching for searchbox " + property);
  //       let element = $("[id='" + property + "']");
  //       if (element.length) {
  //         console.log("found a searchbox for " + v.name);
  //         v.search_name = property;
  //         v.search_value = element.val().trim();
  //         console.log("v after", v);
  //       }
  //     });
  //     console.log("formData after", formData);

  //     saveCamlDraft(formData)
  //       .then((data) => {
  //         $("#drafts-fill-table").DataTable().draw(false);
  //       })
  //       .catch((error) => {
  //         alert("Something went wrong. Check console for details.");
  //         console.log(error);
  //       })
  //       .finally(() => {
  //         console.log("enabled button");
  //         me.text(ogMsg);
  //         me.prop("disabled", false);
  //       });
  //   });

  randomizeEasterEgg();
  var pageLoad = [$.Deferred(), 0];
  var pageReady = pageLoad[0].promise();
  var newListing = true;

  console.log(typeof platforms, platforms);

  //add each platform tab
  $.each(platforms, function (k, v) {
    $(v.id).prop("disabled", true);
    var ddadd = $("#dropdownadd").append(
      format('<li><a data-list-id="{}" href="#">{}</a></li>', v.id, v.name)
    );
    $("#dropdown").before(
      format(
        '<li id="li{}" style="display: none;"><a href="#{}" class="platform" data-toggle="tab"><span>{}</span><i class="glyphicon glyphicon-ban-circle"></i><i class="glyphicon glyphicon-upload"></i><i class="glyphicon glyphicon-remove"></i></a></li>',
        v.id,
        v.id,
        v.name
      )
    );
    $("#pantab").append(
      format('<div class="tab-pane platform-container" id="{}"></div>', v.id)
    );

    $("#li" + v.id)
      .find("i.glyphicon-remove")
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
              //console.log($("#"+v.id+" .form-control"));
              $("#" + v.id + " .form-control").attr("disabled", true);
            } else {
              glyphUp.show();
              glyphBan.hide();
              $("#" + v.id + " .form-control").attr("disabled", false);
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
      .closest("div.form-control")
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

  // Disables the Fill from NPI Data button
  //   $("#fill_flag_button").prop("disabled", true);

  // Gets NPI options based on selected flag type
  //   $("#npi_select").on("change", function () {
  //     var selection = $(this).val();
  //     $("#fill_flag_button").prop("disabled", true);
  //     $("#npi_product_select").html("").selectpicker("destroy");
  //     if (typeof selection === "string" && selection.length > 0) {
  //       $.getJSON("api/channel-lister/getNpiOptions/" + selection)
  //         .done(function (response) {
  //           var html = response.data;
  //           $("#npi_product_select").html(html).selectpicker();
  //           $("#fill_flag_button").prop("disabled", false);
  //         })
  //         .fail(function (response) {
  //           console.log(response);
  //           alert(response.responseText);
  //         });
  //     }
  //   });

  // Gets data for selected NPI product, brings up mapping modal
  //   $("#fill_flag_button").on("click", function () {
  //     var dows_id = $("#npi_product_select").val();
  //     $("#fill_flag_button").nextAll("p.help-block:first").html("");
  //     $.getJSON("api/channel-lister/getNpiValues/" + dows_id)
  //       .done(function (response) {
  //         if (response.status != "success") {
  //           var message = getBootstrapError(response.message);
  //           $("#fill_flag_button").nextAll("p.help-block:first").html(message);
  //           return;
  //         }
  //         mapNpiResponseToFormFields(response.data);
  //       })
  //       .fail(function (response) {
  //         console.log(response);
  //         alert(response.responseText);
  //       });
  //   });

  // Disables the Fill from Draft Data button
  // $('#fill_draft_button').prop('disabled',true);

  // Gets Draft options based on selected field
  // $('#draft_select').on('change', function(){
  // 	var selection = $(this).val();
  // 	$('#fill_draft_button').prop('disabled',true);
  // 	$('#draft_product_select').html('').selectpicker('destroy');
  // 	if (typeof selection === "string" && selection.length > 0) {
  // 		$.getJSON('api/channel-lister/getDraftOptions/'+selection)
  // 			.done(function(response){
  // 				var html = response.data;
  // 				$('#draft_product_select').html(html).selectpicker();
  // 				$('#fill_draft_button').prop('disabled',false);
  // 			}).fail(function(response){
  // 				console.log(response);
  // 				alert(response.responseText);
  // 			});
  // 	}
  // });

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
    $.getJSON("api/channel-lister/getCountryCodeOptions/" + country + "/2")
      .done(function (response) {
        var digit2 = response.data;
        $("#country_of_origin_2_digit-id").val(digit2.trim());
      })
      .fail(function (response) {
        console.log(response);
        alert(response.responseText);
      });
    $.getJSON("api/channel-lister/getCountryCodeOptions/" + country + "/3")
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

  /*
	$('body').on('change', '#item_type_amazon-id', function() {
		let item_type = $(this).val();
		if (item_type == '') {return;}
		// console.log("item_type_amazon = ", item_type);
		$.ajax({
			method   : 'GET',
			url      : 'api/channel-lister/getAmazonCategoryFromItemType/' + item_type,
			dataType : 'json',
		}).done(function(response) {
			let category = response.data;
			if (category !== '') {
				setAmazonCategoryOption(category);
			}
		}).fail(function(response) {
			console.log(response.responseText);
		});
	});
	*/

  // Adds comma separated list of values to input from checked boxes for commaseparated input type
  $("body").on(
    "change",
    'div.comma-sep-options input[type="checkbox"]',
    function () {
      var checked_count = $(this)
        .closest("div.comma-sep-options")
        .find('input[type="checkbox"]:checked').length;
      var limit = $(this)
        .closest("div.form-control")
        .find('input[type="text"]')
        .data("limit");
      if (checked_count <= limit) {
        var input = $(this)
          .closest("div.form-control")
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
    $.getJSON("api/channel-lister/getBundleComponentLine")
      .done(function (response) {
        if (response.status == "success") {
          $("#bundle-components-div").append(response.data);
          $(".remove-row").each(function () {
            $(this).on("click", function () {
              $(this).parent().parent().remove();
              buildBundleComponentString();
              buildSupplierCodeString();
            });
          });
        } else {
          alert("Failed to add bundle line from server.");
        }
      })
      .fail(function (response) {
        alert(response.responseText);
      });
  });

  $("#bundle-components-div").on(
    "change keyup focusout",
    "input.sku-bundle-input, input.sku-bundle-quantity",
    function () {
      buildBundleComponentString();
    }
  );

  $("#bundle-components-div").on(
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
      $("#prop65-warning-type-container").removeClass("hidden");
      $("#prop65-chemical-name-container").removeClass("hidden");
      $("#prop65_warn_type-id option[value='default']").prop("selected", true);
      $("#prop65_warn_type-id").selectpicker("refresh");
    } else {
      $("#prop65_warn_type-id").attr("required", false);
      $("#prop65_chem_name-id").attr("required", false);
      $("#prop65_warn_type-id option[value='']").prop("selected", true);
      $("#prop65_chem_name-id option[value='']").prop("selected", true);
      $("#prop65_warn_type-id").selectpicker("refresh");
      $("#prop65_chem_name-id").selectpicker("refresh");
      $("#prop65-warning-type-container").addClass("hidden");
      $("#prop65-chemical-name-container").addClass("hidden");
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

  $('[id="Relationship Name-id"]').parent().parent().addClass("hidden");
  $('[id="Parent SKU-id"]').parent().addClass("hidden");

  //checks for the change in the sku type dropdown menu
  //Adds the form fields for the selected values
  $("[id='SKU Type-id']").on("change", function () {
    if ($(this).val() == "bundle") {
      $("#bundle-components-div").removeClass("hidden");
      $("#bundled-id").removeClass("hidden");
      $("[id='Bundle Components-id']").prop("required", true);
      $("[id='Bundle Components-id']").parent().addClass("required");
      $("#supplier_code-id").prop("readonly", true);
      buildBundleComponentString();
      buildSupplierCodeString();
    } else {
      if (!$("#bundle-components-div").hasClass("hidden")) {
        $("#bundle-components-div").addClass("hidden");
        $("#bundled-id").addClass("hidden");
        $('[id="Bundle Components-id"]').val("");
      }
      $("[id='Bundle Components-id']").removeProp("required").trigger("change");
      $("#bundled-id").removeClass("required");
      $("[id='Bundle Components-id'").parent().removeClass("required");
      $("#supplier_code-id").removeProp("readonly");
      $("#supplier_code-id").val("");
    }

    if ($(this).val() == "child" || $(this).val() == "parent") {
      $('[id="Relationship Name-id"]').parent().parent().removeClass("hidden");
      $('[id="Parent SKU-id"]').parent().removeClass("hidden");
      $('[id="Relationship Name-id"]').prop("required", true);
      $("[id='Relationship Name-id']").parent().parent().addClass("required");
      $("[id='Parent SKU-id']").parent().addClass("required");
      $('[id="Parent SKU-id"]').prop("required", true);
    } else {
      $('[id="Relationship Name-id"]').parent().parent().addClass("hidden");
      $('[id="Parent SKU-id"]').parent().addClass("hidden");
      $('[id="Relationship Name-id"]').removeProp("required");
      $("[id='Relationship Name-id']")
        .parent()
        .parent()
        .removeClass("required");
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
        .success(function (response) {
          ebayCatVarExclusions = JSON.parse(response.data);
          eBayCategoryVariationSupported(catId, ebayCatVarExclusions);
        })
        .error(function (response) {
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

  $("#loading").hide();
});
