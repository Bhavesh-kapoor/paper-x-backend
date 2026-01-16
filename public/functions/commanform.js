$('#commandForm').on('submit', function (e) {
    e.preventDefault();
    $('.error').html("");
    $('#commanButton').html("Please wait...");

    const submiturl = $(this).attr("action");
    const method = $(this).attr("method");

    let formData = new FormData(this); // Use FormData to handle file uploads

    $.ajax({
        url: submiturl,
        method: method,
        data: formData,
        processData: false, // Prevent jQuery from processing the data
        contentType: false, // Prevent jQuery from setting contentType
        success: function (result) {
            const errorColors = {
                '201': 'success',
                '500': 'error',
                '200': 'success'
            };

            var type = errorColors[result.code] || 'info'; // Default to 'info' if undefined

            toastr.options.timeOut = 10000;

            switch (type) {
                case 'info':
                    toastr.info(result.message);
                    break;
                case 'success':
                    toastr.success(result.message);
                    break;
                case 'warning':
                    toastr.warning(result.message);
                    break;
                case 'error':
                    toastr.error(result.message);
                    break;
                default:
                    toastr.info("Unexpected response");
            }

            $('#commanButton').html("Submit");
            if (result.code == 200 || result.code == 201) {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }

        },
        error: function (xhr, status, error) {
            $('#commanButton').html("Submit");

            if (xhr.responseJSON && xhr.responseJSON.errors) {
                Object.keys(xhr.responseJSON.errors).forEach(function (key) {
                    xhr.responseJSON.errors[key].forEach(function (value) {
                        $('#' + key + '-error').html(value);
                    });
                });
            } else {
                console.error("Error:", status, error, xhr);
            }
        }
    });
});

$('.commandForm').on('submit', function (e) {
    e.preventDefault();
    $('.error').html("");
    $('.commanButton').html("Please wait...");

    const submiturl = $(this).attr("action");
    const method = $(this).attr("method");

    let formData = new FormData(this); // Use FormData to handle file uploads

    $.ajax({
        url: submiturl,
        method: method,
        data: formData,
        processData: false, // Prevent jQuery from processing the data
        contentType: false, // Prevent jQuery from setting contentType
        success: function (result) {
            const errorColors = {
                '201': 'success',
                '500': 'error',
                '200': 'success'
            };

            var type = errorColors[result.code] || 'info'; // Default to 'info' if undefined

            toastr.options.timeOut = 10000;

            switch (type) {
                case 'info':
                    toastr.info(result.message);
                    break;
                case 'success':
                    toastr.success(result.message);
                    break;
                case 'warning':
                    toastr.warning(result.message);
                    break;
                case 'error':
                    toastr.error(result.message);
                    break;
                default:
                    toastr.info("Unexpected response");
            }

            $('.commanButton').html("Submit");
            if (result.code == 200 || result.code == 201) {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }

        },
        error: function (xhr, status, error) {
            $('.commanButton').html("Submit");

            if (xhr.responseJSON && xhr.responseJSON.errors) {
                Object.keys(xhr.responseJSON.errors).forEach(function (key) {
                    xhr.responseJSON.errors[key].forEach(function (value) {
                        $('#' + key + '-error').html(value);
                    });
                });
            } else {
                console.error("Error:", status, error, xhr);
            }
        }
    });
});


$('.ajaxForm').on('submit', function (e) {
    e.preventDefault();
    $('.ajaxFormbtn').html("Please wait...");

    const $form = $(this);
    const actionUrl = $form.attr('action');
    const formData = new FormData(this);

    $.ajax({
        url: actionUrl,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (result) {
            console.log(result.code);
            const errorColors = {
                '201': 'success',
                '500': 'error',
                '200': 'success'
            };

            var type = errorColors[result.code] || 'success'; // Default to 'info' if undefined

            toastr.options.timeOut = 10000;
            $('.ajaxFormbtn').html("Submit");
            console.log(type);

            switch (type) {
                case 'info':
                    toastr.info(result.message);
                    break;
                case 'success':
                    toastr.success(result.message);
                    break;
                case 'warning':
                    toastr.warning(result.message);
                    break;
                case 'error':
                    toastr.error(result.message);
                    break;
                default:
                    toastr.info("Unexpected response");
            }

            $('.commanButton').html("Submit");
            if (result.code == 200 || result.code == 201) {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        },
        error: function (xhr) {
            $('.ajaxFormbtn').html("Submit");

            const err = xhr.responseJSON;
            if (err?.errors) {
                Object.values(err.errors).forEach(function (msg) {
                    toastr.error(msg[0]);
                });
            } else {
                toastr.error(err.message || 'Something went wrong');
            }
        }
    });
});



function CommanDelete(type, table, id) {
    if (type === 'delete') {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.origin + '/admin/commandelete', // Update with your actual delete route
                    method: 'POST',
                    data: { table: table, id: id, type: type },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },

                    success: function (response) {
                        if (response.code === 200) {
                            Swal.fire("Deleted!", "Your record has been deleted.", "success");
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            Swal.fire("Error!", "Failed to delete the record.", "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Error!", "Something went wrong.", "error");
                    }
                });
            }
        });
    }
}
