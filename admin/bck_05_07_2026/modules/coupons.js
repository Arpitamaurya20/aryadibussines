function openAddCouponModal() {
    $('#couponModalTitle').text('Add Coupon');
    $('#coupon_id').val('');
    $('#coupon_name').val('');
    $('#discount').val('');
    $('#discount_type').val('');
    $('#couponModal').modal('show');
}

/* =========================
   EDIT COUPON (GET DATA)
   ========================= */
function openEditCoupon(id) {
    $.post('action/coupon-action.php', {
        action: 'get',
        id: id
    }, function (res) {

        let response = JSON.parse(res);

        if (response.error === false) {
            let data = response.data;
            $('#couponModalTitle').text('Edit Coupon');
            $('#coupon_id').val(data.ID);
            $('#coupon_name').val(data.CouponName);
            $('#discount').val(data.Discount);
            $('#discount_type').val(data.Type);
            $('#couponModal').modal('show');

            $('#discount_type')
                .val(data.Type.toLowerCase())
                .trigger('change');
        } else {
            alert(response.message);
        }

    });
}

/* =========================
   ADD / UPDATE COUPON
   ========================= */
function saveCoupon() {

    let data = {
        action: 'save',
        id: $('#coupon_id').val(), // empty = add
        CouponName: $('#coupon_name').val(),
        Discount: $('#discount').val(),
        Type: $('#discount_type').val()
    };

    $.post('action/coupon-action.php', data, function (res) {

        let response = JSON.parse(res);

        if (response.error === false) {
            $('#couponModal').modal('hide');
            $('#view-all-coupons').DataTable().ajax.reload(null, false);
        } else {
            alert(response.message);
        }

    });
}

/* =========================
   DELETE COUPON
   ========================= */
function openDeleteCoupon(id) {
    $('#delete_coupon_id').val(id);
    $('#deleteCouponModal').modal('show');
}

function deleteCoupon() {

    let id = $('#delete_coupon_id').val();

    $.post('action/coupon-action.php', {
        action: 'delete',
        id: id
    }, function (res) {

        let response = JSON.parse(res);

        if (response.error === false) {
            $('#deleteCouponModal').modal('hide');
            $('#view-all-coupons').DataTable().ajax.reload(null, false);
        } else {
            alert(response.message);
        }

    });
}

/* =========================
   ACTIVE / INACTIVE TOGGLE
   ========================= */
function toggleCouponStatus(id, status) {

    $.post('action/coupon-action.php', {
        action: 'status',
        id: id,
        status: status
    }, function (res) {

        let response = JSON.parse(res);

        if (response.error === false) {
            $('#view-all-coupons').DataTable().ajax.reload(null, false);
        } else {
            alert(response.message);
        }

    });
}
