jQuery(document).ready(function ($) {
  // Initialize datepicker
  $("#collection_time_field").hide();

  $("#collection_date").datepicker({
    dateFormat: "yy-mm-dd",
    minDate: collectionTimeOptions.minDate,
    beforeShowDay: function (date) {
      return [true];
    },
    onSelect: function (selectedDate) {
      // Clear existing notices and hide time field initially
      $("#collection_time_field").hide();
      $("#times").remove();

      var daysOfWeek = [
        "sunday",
        "monday",
        "tuesday",
        "wednesday",
        "thursday",
        "friday",
        "saturday"
      ];
      var selectedDateObj = new Date(selectedDate);
      var dayOfWeek = daysOfWeek[selectedDateObj.getDay()];
      var openingHours = collectionTimeOptions.openingHours[dayOfWeek];
      var html = '<option value="">Select Collection Time</option>';

      // Generate time slots
      if (openingHours.start_time != "" && openingHours.end_time != "") {
        $("#collection_time_field").show();
        var st_first = openingHours.start_time.split(":");
        var et_first = openingHours.end_time.split(":");
        for (
          var ab = parseInt(st_first[0]);
          ab <= parseInt(et_first[0]);
          ab++
        ) {
          html += '<option value="' + ab + ': 00">' + ab + ": 00</option>";
        }
      } else {
        // Show a notice if no times are available
        $("#collection_time_field").after(
          '<div id="times">No times available for the selected date</div>'
        );
        return;
      }

      $("#collection_time").html(html);

      // Check if the selected date is the next day
      var currentDate = new Date();
      var nextDay = new Date(currentDate);
      nextDay.setDate(currentDate.getDate() + 1);
      var isNextDay = selectedDateObj.toDateString() === nextDay.toDateString();

      // Disable past times with a 2-hour buffer
      if (selectedDate == collectionTimeOptions.curdate || isNextDay) {
        var bufferHour = isNextDay ? 2 : currentDate.getHours() + 2; // 2-hour buffer from midnight if it's the next day
        $("#collection_time > option").each(function () {
          var optionHour = parseInt($(this).val().split(":")[0]);
          if (optionHour < bufferHour) {
            $(this).prop("disabled", true);
          }
        });
      } else {
        $("#collection_time > option").prop("disabled", false);
      }
    }
  });
});
