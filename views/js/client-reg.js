$(function () {
    // var datehired = $('#datehired');
    // datehired.attr('placeholder', '  /  /  ');
    // datehired.flatpickr({
    //   monthSelectorType: 'static',
    //   dateFormat: 'm/d/Y',
    //   static: true
    // });

    // $("#btn-new").click(function(){
    //     Swal.fire({
    //       title: 'Enlist new staff?',
    //       icon: 'question',
    //       showCancelButton: true,
    //       confirmButtonText: 'Yes',
    //       customClass: {
    //         confirmButton: 'btn btn-primary',
    //         cancelButton: 'btn btn-label-secondary'
    //       },
    //       buttonsStyling: false
    //     }).then(function (result) {
    //       if (result.value) {
    //         window.location = 'staffclinic';
    //       }
    //     });
    // });   

    $("#btn-save").click(function () {
        let requiredFields = [
            { id: "#firstname", label: "First Name" },
            { id: "#lastname", label: "Last Name" },
            { id: "#mi", label: "Middle Initial" },
            { id: "#prc", label: "PRC" },
            { id: "#designation", label: "Designation" }
        ];

        let emptyFields = [];
        requiredFields.forEach(function (field) {
            let value = $(field.id).val();

            if (!value || value.trim() === '') {
                emptyFields.push(field.label);
            }
        });

        if (emptyFields.length > 0) {
            // Swal.fire({
            //     title: 'Required Fields Missing',
            //     icon: 'warning',
            //     html: '<div style="text-align:left;margin-left:20px;">' +
            //           '<p>The following fields are required:</p>' +
            //           '<ul>' +
            //           emptyFields.map(f => `<li>${f}</li>`).join('') +
            //           '</ul></div>',
            //     confirmButtonText: 'OK',
            //     customClass: {
            //         confirmButton: 'btn btn-primary'
            //     },
            //     buttonsStyling: false
            // });
            return;
        }

        // let isEdit = $("#empid").val();
        
        // let titletext = isEdit ? 'Update this Staff?' : "Save new Staff?"


        // Swal.fire({
        //   title: titletext,
        //   icon: 'question',
        //   showCancelButton: true,
        //   confirmButtonText: 'Yes',
        //   customClass: {
        //     confirmButton: 'btn btn-primary',
        //     cancelButton: 'btn btn-label-secondary'
        //   },
        //   buttonsStyling: false
        // }).then(function (result) {
        //   if (result.value) {
        //     saveStaff();
        //   }
        // });
    });

    // function newStaff(){
    //     $("#firstname").val('');
    //     $("#lastname").val('');
    //     $("#mi").val('');
    //     $("#extension").val('');
    //     $("#designation").val('').trigger('change');
    //     //$("#estatus").val('').trigger('change');
    //     $("#prc").val('');
    //     $("#empid").val('');
    //     $("#mobile").val('');
    //     $("#alternate").val('');
    //     $("#address").val('');
         
    //     $("#firstname").focus();
    // }

    function saveStaff(){
        let trans_type = $("#trans_type").val();
        let encodedby = $("#encodedby").val();

        let firstname = $("#firstname").val();
        let lastname = $("#lastname").val();
        let mi = $("#mi").val();
        let extension = $("#extension").val();
        let designation = $("#designation").val();
        //let estatus = $("#estatus").val();
        let prc = $("#prc").val();
        let empid = $("#empid").val();
        let mobile = $("#mobile").val();
        let alternate = $("#alternate").val();

        let raw_datehired = $("#datehired").val();
        let datehired = '';

        if (raw_datehired !== "") {
            let parts = raw_datehired.split("/");
            datehired = parts[2] + "-" + parts[0] + "-" + parts[1];
        }

        let address = $("#address").val();

        let staffclinic = new FormData();
        staffclinic.append("trans_type", trans_type);
        staffclinic.append("encodedby", encodedby);

        staffclinic.append("firstname", firstname);
        staffclinic.append("lastname", lastname);
        staffclinic.append("mi", mi);
        staffclinic.append("extension", extension);
        staffclinic.append("designation", designation);
        //staffclinic.append("estatus", estatus);
        staffclinic.append("prc", prc);
        staffclinic.append("empid", empid);
        staffclinic.append("mobile", mobile);
        staffclinic.append("alternate", alternate);
        staffclinic.append("datehired", datehired);
        staffclinic.append("address", address);


        $.ajax({
            url:"ajax/staffclinic_save_record.ajax.php",
            method: "POST",
            data: staffclinic,
            cache: false,
            contentType: false,
            processData: false,
            dataType:"text",
            success:function(answer){
                let empid = answer;
                if (empid != 'error' && empid != 'existing'){
                  /* Swal.fire({
                      title: 'Staff details successfully saved!',
                      icon: 'success',
                      confirmButtonText: 'Got it',
                      customClass: {
                        confirmButton: 'btn btn-success waves-effect waves-light'
                      },
                      buttonsStyling: false
                  }).then(function (result) {
                      if (result.value) {
                          
                          window.location = 'staffclinic';
                      }
                  }); */
                }
            },
            error: function () {
                /* Swal.fire({
                    title: 'Oops. Something went wrong!',
                    icon: 'error',
                    confirmButtonText: 'Got it',
                    customClass: {
                      confirmButton: 'btn btn-danger waves-effect waves-light'
                    },
                    buttonsStyling: false
                }); */
            }
        });
    }
});