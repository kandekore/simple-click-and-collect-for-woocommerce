jQuery(document).ready(function ($) {
  // Initialize datepicker
  $("#collection_date").datepicker({
    dateFormat: "yy-mm-dd",
    minDate: collectionTimeOptions.minDate,
    beforeShowDay: function (date) {
      var day = date.getDay();
      var openingHours = collectionTimeOptions.openingHours;

      if (openingHours[day]) {
        var startTime = new Date();
        var endTime = new Date();
        var start = openingHours[day].start_time.split(":");
        var end = openingHours[day].end_time.split(":");

        startTime.setHours(start[0], start[1], 0, 0);
        endTime.setHours(end[0], end[1], 0, 0);

        return [date >= startTime && date <= endTime];
      }

      return [false];
    },
    onSelect: function (selectedDate) {
      if (selectedDate === collectionTimeOptions.minDate) {
        $("#collection_time").prop("disabled", false).val("").trigger("change");
      } else {
        $("#collection_time").prop("disabled", true).val("").trigger("change");
      }
    }
  });

  // Initialize timepicker
  $("#collection_time").timepicker({
    timeFormat: collectionTimeOptions.timeFormat,
    minTime: collectionTimeOptions.minTime,
    maxTime: collectionTimeOptions.maxTime
  });
});
