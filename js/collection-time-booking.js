jQuery(document).ready(function ($) {
  // Initialize datepicker

  $("#collection_date").datepicker({
    dateFormat: "yy-mm-dd",
    minDate: collectionTimeOptions.minDate,
    beforeShowDay: function (date) {
      return [true];
    },
    onSelect: function (selectedDate) {
      var openingHours = collectionTimeOptions.openingHours;
      if (selectedDate == collectionTimeOptions.curdate) {
        var enddtime = collectionTimeOptions.minTime.split(":");
        for (i = "10"; i <= enddtime[0]; i++) {
          $('#collection_time > option[value="' + i + ':00"]').prop(
            "disabled",
            true
          );
        }
      } else {
        $("#collection_time > option").prop("disabled", false);
      }
    }
  });
});
