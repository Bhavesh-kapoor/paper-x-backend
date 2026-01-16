$('#loginform').on('submit', function (e) {
    e.preventDefault();
    $('.error').html(" ");
    $('#loginbutton').html("Please wait...");

    const submiturl = $('#loginform').attr("action");
    const method = $('#loginform').attr("method");
    const formData = $('#loginform').serialize();

    $.ajax({
        url: submiturl,
        method: method,
        data: formData,
        success: function (result) {
            $('#loginbutton').html("Login");

            $('#successfull').html(result.message);
            setTimeout(() => {
                window.location.href = '/admin/dashboard';
            }, 1000)
        },
        error: function (xhr, status, error) {
            $('#loginbutton').html("Login");

            if (xhr.responseJSON && xhr.responseJSON.errors) {
                Object.keys(xhr.responseJSON.errors).forEach(function (key) {
                    // Iterate over each error message for the current field
                    xhr.responseJSON.errors[key].forEach(function (value) {
                        // Display each error message in the corresponding element
                        $('#' + key + '-error').html(value);
                    });
                });
            } else {
                console.log(status, error, xhr);
            }
        }
    });
});
