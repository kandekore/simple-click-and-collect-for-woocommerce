jQuery(document).ready(function($) {
  // Initialize datepicker

  $("#collection_time_field").hide();

  $('#collection_date').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: collectionTimeOptions.minDate,
    beforeShowDay: function(date) {      
      return [true];
    },
    onSelect: function(selectedDate) {      

      var daysOfWeek = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

      var selectedDate1 = new Date(selectedDate);
      var dayOfWeek = daysOfWeek[selectedDate1.getDay()];
     
      var openingHours = collectionTimeOptions.openingHours;

      var curdaytime=openingHours[dayOfWeek];

      // console.log(curdaytime.start_time);
      // console.log(curdaytime.end_time);

      var html='<option value="">Select Collection Time</option>';
      
      if(curdaytime.start_time!='' && curdaytime.end_time!=''){
        $("#collection_time_field").show();
        $("#collection_time_field > span").show();
        $("#collection_time_field > #times").remove();

        var st_first=(curdaytime.start_time).split(":");
        var et_first=(curdaytime.end_time).split(":");

        for(ab=st_first[0];ab<=et_first[0];ab++){
          html+='<option value="'+ab+': 00">'+ab+': 00</option>';
        }
      }if(curdaytime.start_time=='' && curdaytime.end_time==''){
        $("#collection_time_field").show();
        $("#collection_time_field > span").hide();
        $("#collection_time_field").append("<span id='times'>No times available</span>");
      }

      $('#collection_time').html(html);

      if(selectedDate==collectionTimeOptions.curdate){
        var enddtime=(collectionTimeOptions.minTime).split(":");
        for(i='10';i<=enddtime[0];i++){
          $('#collection_time > option[value="'+i+': 00"]').prop('disabled', true);
        }
      }else{
        $('#collection_time > option').prop('disabled', false);
      }
    }
  });

});
