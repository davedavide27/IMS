function start_loader() {
    $('body').append(`
        <div id="preloader">
            <div class="loader-holder">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    `);
}

function end_loader() {
    $('#preloader').fadeOut('fast', function() {
        $('#preloader').remove();
    });
}

// Toast Notification Function
window.alert_toast = function($msg = 'TEST', $bg = 'success', $pos = '') {
    var Toast = Swal.mixin({
        toast: true,
        position: $pos || 'top',
        showConfirmButton: false,
        timer: 3500
    });
    Toast.fire({
        icon: $bg,
        title: $msg
    });
};

$(document).ready(function() {
    // Login Form Submission
    $('#login-frm').submit(function(e) {
        e.preventDefault();
        start_loader();

        if ($('.err_msg').length > 0) {
            $('.err_msg').remove();
        }

        $.ajax({
            url: _base_url_ + 'classes/Login.php?f=login',
            method: 'POST',
            data: $(this).serialize(),
            error: (err) => {
                console.log(err);
            },
            success: function(resp) {
                if (resp) {
                    resp = JSON.parse(resp);

                    if (resp.status === 'success') {
                        location.replace(_base_url_ + 'admin');
                    } else {
                        var _frm = $('#login-frm');
                        var _msg = `<div class='alert alert-danger text-white err_msg'>
                            <i class='fa fa-exclamation-triangle'></i> ${
                                resp.status === 'incorrect'
                                    ? 'Incorrect username or password'
                                    : 'Your Account is not yet verified.'
                            }
                        </div>`;
                        _frm.prepend(_msg);
                        _frm.find('input').addClass('is-invalid');
                        $('[name="username"]').focus();
                    }

                    end_loader();
                }
            }
        });
    });

    // Client Login Form Submission
    $('#clogin-frm').submit(function(e) {
        e.preventDefault();
        start_loader();

        if ($('.err_msg').length > 0) {
            $('.err_msg').remove();
        }

        $.ajax({
            url: _base_url_ + 'classes/Login.php?f=clogin',
            method: 'POST',
            data: $(this).serialize(),
            error: (err) => {
                console.log(err);
                alert_toast("An error occurred", 'danger');
                end_loader();
            },
            success: function(resp) {
                if (resp) {
                    resp = JSON.parse(resp);

                    if (resp.status === 'success') {
                        location.replace(_base_url_);
                    } else if (resp.status === 'incorrect') {
                        var _frm = $('#clogin-frm');
                        var _msg = `<div class='alert alert-danger text-white err_msg'>
                            <i class='fa fa-exclamation-triangle'></i> Incorrect code or password
                        </div>`;
                        _frm.prepend(_msg);
                        _frm.find('input').addClass('is-invalid');
                        $('[name="username"]').focus();
                    }
                }

                end_loader();
            }
        });
    });

    // Student Login Form Submission
    $('#slogin-frm').submit(function(e) {
        e.preventDefault();
        start_loader();

        if ($('.err_msg').length > 0) {
            $('.err_msg').remove();
        }

        $.ajax({
            url: _base_url_ + 'classes/Login.php?f=slogin',
            method: 'POST',
            data: $(this).serialize(),
            error: (err) => {
                console.log(err);
            },
            success: function(resp) {
                if (resp) {
                    resp = JSON.parse(resp);

                    if (resp.status === 'success') {
                        location.replace(_base_url_ + 'student');
                    } else if (resp.status === 'incorrect') {
                        var _frm = $('#slogin-frm');
                        var _msg = `<div class='alert alert-danger text-white err_msg'>
                            <i class='fa fa-exclamation-triangle'></i> Incorrect username or password
                        </div>`;
                        _frm.prepend(_msg);
                        _frm.find('input').addClass('is-invalid');
                        $('[name="username"]').focus();
                    }

                    end_loader();
                }
            }
        });
    });

    // System Info Form Submission
    $('#system-frm').submit(function(e) {
        e.preventDefault();
        start_loader();

        if ($('.err_msg').length > 0) {
            $('.err_msg').remove();
        }

        $.ajax({
            url: _base_url_ + 'classes/SystemSettings.php?f=update_settings',
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Data successfully saved", 'success');
                    location.reload();
                } else {
                    $('#msg').html(`<div class="alert alert-danger err_msg">An Error occurred</div>`);
                    end_loader();
                }
            }
        });
    });
});
