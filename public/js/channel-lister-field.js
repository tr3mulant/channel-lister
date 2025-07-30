function CAMLUpdateTable() {
  var me = this;
  me.columns = null;
  me.init = function () {
    return $.ajax({
      type: "GET",
      url: "api/Datatable/product_listings/channel_lister_fields",
      data: { action: "heading" },
      dataType: "json",
    })
      .done(function (data) {
        me.columns = data;
        me.addColumnDefs();
        me.addColumnRenders();
        me.display();
      })
      .fail(function (error) {
        alert("Failed to initialize CAML Update table! (see console)");
        console.log(error);
      });
  };

  /**
   * controls the default columns to be displayed and adds some css classes for wide content
   */
  me.addColumnDefs = function () {
    let className;
    me.columns.forEach(function (v, k, a) {
      className = "";
      switch (v.data.toLowerCase()) {
        case "tooltip":
        case "example":
        case "input_type_aux":
          className = "dt-min-w-15";
          break;
        default:
          break;
      }
      me.columns[k].className = className;
    });
  };

  me.addColumnRenders = function () {
    let renderFunc;
    me.columns.forEach(function (v, k, a) {
      renderFunc = "";
      switch (v.data.toLowerCase()) {
        case "tooltip":
        case "example":
        case "input_type_aux":
          renderFunc = function (data, type, row, meta) {
            if (data == null) {
              return data;
            } else {
              let display =
                type === "display" && data.length > 75
                  ? `${data.substr(0, 75)}...`
                  : data;
              let template = `<div class='tooltip tt-caml-update-table tt-min-w-5 tt-max-w-50' role='tooltip'><div class='tooltip-arrow'></div><div class='tooltip-inner'></div></div>`;
              return `<span class="" data-toggle="tooltip" data-placement="top" data-container="body" data-template="${template}" title="${htmlEntities(
                data
              )}">
										${htmlEntities(display)}
									</span>`;
            }
          };
          break;
        default:
          break;
      }
      if (renderFunc != "") {
        me.columns[k].render = renderFunc;
      }
    });
  };

  me.display = function () {
    return $("#caml-update-table").DataTableExtended({
      processing: true,
      serverSide: true,
      paging: true,
      ordering: true,
      ajax: {
        url: "api/Datatable/product_listings/channel_lister_fields",
        type: "POST",
      },
      scrollX: true,
      stateSave: true,
      select: { style: "single" },
      columns: me.columns,
      search: { regex: false },
      searchByColumn: true,
      downloadCSV: { addCols: ["id"], removeCols: ["ordering"] },
      buttons: [
        "addCamlUpdate",
        "editCamlUpdate",
        "removeCamlUpdate",
        "reorderCamlUpdate",
      ],
      dom: "Blfrtip",
    });
  };
}

//custom buttons
$.fn.dataTable.ext.buttons.addCamlUpdate = {
  text: "Add",
  extend: "selectedSingle",
  name: "addCamlUpdate",
  action: addCamlUpdate,
};

$.fn.dataTable.ext.buttons.editCamlUpdate = {
  text: "Edit",
  extend: "selectedSingle",
  name: "editCamlUpdate",
  action: editCamlUpdate,
};

$.fn.dataTable.ext.buttons.removeCamlUpdate = {
  text: "Remove",
  extend: "selectedSingle",
  name: "removeCamlUpdate",
  action: removeCamlUpdate,
};

$.fn.dataTable.ext.buttons.reorderCamlUpdate = {
  text: "Reorder",
  extend: "selectedSingle",
  name: "reorderCamlUpdate",
  action: reorderCamlUpdate,
};

/**
 * Can add new rows to the channel lister fields table depending on where is clicked
 */
function addCamlUpdate(e, dt, node, config) {
  let id = dt.row({ selected: true }).id();
  let row_data = dt.row({ selected: true }).data();
  let field_name = row_data.field_name;
  let ordering = row_data.ordering;
  let marketplace = row_data.marketplace;
  let grouping = row_data.grouping;

  let add_caml_update = function (form_info) {
    if (form_info == null) {
      return false;
    }
    $.each(form_info, function (i, item) {
      if (item == "[<NULL>]") form_info[i] = null;
    });
    $.ajax({
      type: "POST",
      url: "api/channel-lister/addChannelListerFields",
      data: form_info,
      dataType: "json",
    })
      .done(function (response) {
        console.log(response);
        if (response.status == "success") {
          $("#caml-update-table").DataTable().draw(false);
          $(".modal button.close").trigger("click");
        } else {
          console.error(response);
          alert(response.message);
        }
      })
      .fail(function (response) {
        console.error(response);
        alert(response.responseText);
      });
    return false;
  };

  let location_column = {
    name: "location",
    type: "enum(" + `Before ${field_name}` + "," + `After ${field_name}` + ")",
  };

  let form = FormMaker.from_database(
    "add_caml_form",
    "product_listings",
    "channel_lister_fields",
    add_caml_update
  );
  form.done(function (form) {
    form.fill(row_data);
    form.edit_field("id", id);
    form.disable_field("id");
    form.edit_field("ordering", ordering);
    form.disable_field("ordering");
    form.edit_field("field_name", "");
    form.edit_field("display_name", "");
    form.edit_field("tooltip", "");
    form.edit_field("example", "");
    form.edit_field("input_type", "");
    form.edit_field("input_type_aux", "");
    form.edit_field("type", "");

    form.add_form_group(id, location_column, 2);
    form
      .find('[name="location"]')
      .append(
        `<p class="help-block">Selecting 'After' will place the new field AFTER ${field_name} in table</p>`
      );

    let modal = $(window.modal);
    modal.find("h4.modal-title").text("Add Channel Lister Field");

    modal.find("div.modal-body").html(form);
    modal.modal();
    modal.find("button.close").click(function () {
      modal.modal("hide");
      window.setTimeout(() => modal.delay(2000).remove(), 1000);
    });
  });
}

/**
 * Makes the channel lister fields table editable using the button after clicking on a row
 */
function editCamlUpdate(e, dt, node, config) {
  let id = dt.row({ selected: true }).id();
  let row_data = dt.row({ selected: true }).data();

  let submitcb = function () {
    return confirm("Do you want to submit?");
  };

  let deleterow = function (row) {
    $("#caml-update-table").DataTable().draw(false);
    $(".modal button.close").trigger("click");
    return false;
  };

  let form = FormMaker.from_database(
    "caml_edit_form",
    "product_listings",
    "channel_lister_fields",
    submitcb,
    deleterow
  );

  form.done(function (form) {
    form.fill(row_data);
    form.edit_field("id", id);
    form.disable_field("id");
    form.disable_field("ordering");
    let modal = $(window.modal);
    modal.find("h4.modal-title").text("Edit Channel Lister Field");
    modal.find("div.modal-body").html(form);
    modal.modal();
    modal.find("button.close").click(function () {
      modal.modal("hide");
      window.setTimeout(() => modal.delay(2000).remove(), 1000);
    });
  });
}

/**
 * Makes the channel lister fields row removable using the button after clicking on a row
 */
function removeCamlUpdate(e, dt, node, config) {
  var id = dt.row({ selected: true }).id();
  var row_data = dt.row({ selected: true }).data();
  var field_name = row_data.field_name;
  var ordering = row_data.ordering;
  var data = {
    ordering: ordering,
  };

  if (confirm(`Would you like to remove field: ${field_name}?`)) {
    $.ajax({
      type: "POST",
      url: "api/channel-lister/removeChannelListerFields",
      data: data,
      dataType: "json",
    })
      .done(function (response) {
        if (response.status == "success") {
          $("#caml-update-table").DataTable().draw(false);
        } else {
          console.error(response);
        }
      })
      .fail(function (response) {
        console.error(response);
        alert(response.responseText);
      });
  }
}

/**
 * Makes the channel lister fields table re-orderable using the button after clicking on a row
 */
function reorderCamlUpdate(e, dt, node, config) {
  var id = dt.row({ selected: true }).id();
  var row_data = dt.row({ selected: true }).data();
  var field_name = row_data.field_name;
  var ordering = row_data.ordering;

  (function () {
    $.ajax({
      type: "GET",
      url: "api/channel-lister/getChannelListerFieldNames",
      dataType: "json",
    })
      .done(function (response) {
        console.log(response);
        var listing_names = response.data;
        var dest_field_string = Object.values(listing_names);
        createReorderForm(dest_field_string);
      })
      .fail(function (response) {
        console.error(response);
        alert(response.message);
      });
  })();

  var reorder_caml = function (form_info) {
    if (form_info == null) {
      return false;
    }
    $.each(form_info, function (i, item) {
      console.log(item);
      if (item == "[<NULL>]") form_info[i] = null;
    });
    $.ajax({
      type: "POST",
      url: "api/channel-lister/reorderChannelListerFields",
      data: form_info,
      dataType: "json",
    })
      .done(function (response) {
        console.log(response);
        if (response.status == "success") {
          $(".modal button.close").trigger("click");
          $("#caml-update-table").DataTable().draw(false);
        } else {
          console.error(response);
          alert(response.message);
        }
      })
      .fail(function (response) {
        console.error(response);
        alert(response.responseText);
      });
    return false;
  };

  function createReorderForm(dropdown_string) {
    var columns = [
      {
        name: "field_to_move",
        type: "varchar(100)",
      },
      {
        name: "location",
        type: "enum('Before', 'After')",
      },
      {
        name: "place_around_field",
        type: `enum(${dropdown_string})`,
      },
    ];
    var form = FormMaker.from_object("fM", columns, reorder_caml);

    var modal = $(window.modal);
    modal.find("h4.modal-title").text("Reorder Channel Lister Fields");

    modal.find("div.modal-body").html(form);

    form.edit_field(`field_to_move`, field_name);
    form.disable_field(`field_to_move`);
    form
      .find("#fMplace_around_field")
      .addClass("select-picker")
      .attr("data-live-search", "true")
      .selectpicker();

    modal.modal();
    modal.find("button.close").click(function () {
      modal.modal("hide");
      window.setTimeout(() => modal.delay(2000).remove(), 1000);
    });
  }
}
