// Admin Registration Management JavaScript

$(document).ready(function() {
    // Initialize DataTable with additional features
    $('#registrationsTable').DataTable({
        order: [[6, "desc"]], // Order by registration date
        pageLength: 25,
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            zeroRecords: "ไม่พบข้อมูล",
            info: "แสดงหน้าที่ _PAGE_ จาก _PAGES_",
            infoEmpty: "ไม่มีข้อมูล",
            infoFiltered: "(กรองจาก _MAX_ รายการทั้งหมด)",
            paginate: {
                first: "หน้าแรก",
                last: "หน้าสุดท้าย",
                next: "ถัดไป",
                previous: "ก่อนหน้า"
            }
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> ส่งออก Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> พิมพ์',
                className: 'btn btn-secondary btn-sm'
            }
        ],
        columnDefs: [
            { orderable: false, targets: [0, 9] } // Disable sorting for checkbox and actions
        ]
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Select All functionality
    $('#selectAll').change(function() {
        $('.registration-checkbox').prop('checked', this.checked);
        updateSelectedCount();
    });

    // Individual checkbox change
    $(document).on('change', '.registration-checkbox', function() {
        updateSelectedCount();
        
        // Update select all checkbox
        var total = $('.registration-checkbox').length;
        var checked = $('.registration-checkbox:checked').length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
    });

    // Update selected count
    function updateSelectedCount() {
        var count = $('.registration-checkbox:checked').length;
        $('#countNumber').text(count);
        $('#selectedCount').toggleClass('alert-warning', count > 0);
        $('#selectedCount').toggleClass('alert-secondary', count === 0);
    }

    // Status change handling
    $(document).on('click', '.status-change', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var status = $(this).data('status');
        var statusText = $(this).text().trim();
        
        $('#changeStatusId').val(id);
        $('#changeStatusValue').val(status);
        $('#statusChangeInfo').html(
            '<i class="fas fa-info-circle"></i> ' +
            'คุณต้องการเปลี่ยนสถานะเป็น: <strong>' + statusText + '</strong>'
        );
        
        $('#statusChangeModal').modal('show');
    });

    // Handle status change form submission
    $('#statusChangeForm').submit(function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('update_status', '1');
        formData.append('ajax', '1');
        
        $.ajax({
            url: 'api/admin_registration_actions.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            // Remove dataType to handle response manually
            beforeSend: function() {
                console.log('Sending AJAX request...');
            },
            success: function(response, textStatus, jqXHR) {
                console.log('Response received:', response);
                console.log('Response type:', typeof response);
                console.log('Text status:', textStatus);
                
                // Handle different response types
                let parsedResponse;
                if (typeof response === 'string') {
                    try {
                        parsedResponse = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse JSON response:', response);
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด!',
                            text: 'ระบบตอบกลับในรูปแบบที่ไม่ถูกต้อง'
                        });
                        $('#statusChangeModal').modal('hide');
                        return;
                    }
                } else {
                    parsedResponse = response;
                }
                
                if (parsedResponse && parsedResponse.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: parsedResponse.message || 'ดำเนินการสำเร็จแล้ว',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด!',
                        text: parsedResponse ? (parsedResponse.message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ') : 'ไม่สามารถประมวลผลได้'
                    });
                }
                $('#statusChangeModal').modal('hide');
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error Details:');
                console.log('- xhr.status:', xhr.status);
                console.log('- xhr.statusText:', xhr.statusText);
                console.log('- xhr.responseText:', xhr.responseText);
                console.log('- status:', status);
                console.log('- error:', error);
                
                let errorMessage = 'เกิดข้อผิดพลาดในการติดต่อเซิร์ฟเวอร์';
                
                // Check if it's actually a successful request with malformed JSON
                if (xhr.status === 200 && xhr.responseText) {
                    try {
                        const successResponse = JSON.parse(xhr.responseText);
                        if (successResponse.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: successResponse.message || 'ดำเนินการสำเร็จแล้ว',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                            $('#statusChangeModal').modal('hide');
                            return;
                        }
                    } catch (e) {
                        errorMessage = 'การดำเนินการอาจสำเร็จแล้ว กรุณารีเฟรชหน้าเพื่อตรวจสอบ';
                    }
                }
                
                // Try to parse error response
                try {
                    if (xhr.responseText && xhr.responseText.includes('{')) {
                        const startIndex = xhr.responseText.indexOf('{');
                        const jsonPart = xhr.responseText.substring(startIndex);
                        const errorResponse = JSON.parse(jsonPart);
                        errorMessage = errorResponse.message || errorMessage;
                    }
                } catch (e) {
                    // Use default error message
                    if (xhr.status === 0) {
                        errorMessage = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'เกิดข้อผิดพลาดที่เซิร์ฟเวอร์';
                    } else if (xhr.status >= 400) {
                        errorMessage = 'คำขอไม่ถูกต้อง';
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: errorMessage,
                    footer: 'หากปัญหายังคงอยู่ กรุณารีเฟรชหน้าเพื่อตรวจสอบผลลัพธ์'
                });
                $('#statusChangeModal').modal('hide');
            }
        });
    });

    // Bulk action form submission
    $('#bulkActionForm').submit(function(e) {
        e.preventDefault();
        
        var selectedIds = $('.registration-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่ได้เลือกรายการ',
                text: 'กรุณาเลือกรายการที่ต้องการดำเนินการ'
            });
            return;
        }

        Swal.fire({
            title: 'ยืนยันการดำเนินการ',
            text: 'คุณต้องการดำเนินการกับ ' + selectedIds.length + ' รายการที่เลือก?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = new FormData(this);
                formData.append('bulk_update', '1');
                formData.append('ajax', '1');
                
                // Add selected IDs
                selectedIds.forEach(function(id) {
                    formData.append('registration_ids[]', id);
                });
                
                $.ajax({
                    url: 'api/admin_registration_actions.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        Swal.fire({
                            title: 'กำลังดำเนินการ...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response, textStatus, jqXHR) {
                        console.log('Bulk response received:', response);
                        console.log('Response type:', typeof response);
                        
                        // Handle different response types
                        let parsedResponse;
                        if (typeof response === 'string') {
                            try {
                                parsedResponse = JSON.parse(response);
                            } catch (e) {
                                console.error('Failed to parse JSON response:', response);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: 'ระบบตอบกลับในรูปแบบที่ไม่ถูกต้อง'
                                });
                                $('#bulkActionModal').modal('hide');
                                return;
                            }
                        } else {
                            parsedResponse = response;
                        }
                        
                        if (parsedResponse && parsedResponse.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: parsedResponse.message || 'ดำเนินการสำเร็จแล้ว',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด!',
                                text: parsedResponse ? (parsedResponse.message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ') : 'ไม่สามารถประมวลผลได้'
                            });
                        }
                        $('#bulkActionModal').modal('hide');
                    },
                    error: function(xhr, status, error) {
                        console.log('Bulk Update AJAX Error Details:');
                        console.log('- xhr.status:', xhr.status);
                        console.log('- xhr.statusText:', xhr.statusText);
                        console.log('- xhr.responseText:', xhr.responseText);
                        console.log('- status:', status);
                        console.log('- error:', error);
                        
                        let errorMessage = 'เกิดข้อผิดพลาดในการติดต่อเซิร์ฟเวอร์';
                        
                        // Check if it's actually a successful request with malformed JSON
                        if (xhr.status === 200 && xhr.responseText) {
                            try {
                                const successResponse = JSON.parse(xhr.responseText);
                                if (successResponse.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'สำเร็จ!',
                                        text: successResponse.message || 'ดำเนินการสำเร็จแล้ว',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(function() {
                                        location.reload();
                                    });
                                    $('#bulkActionModal').modal('hide');
                                    return;
                                }
                            } catch (e) {
                                errorMessage = 'การดำเนินการอาจสำเร็จแล้ว กรุณารีเฟรชหน้าเพื่อตรวจสอบ';
                            }
                        }
                        
                        // Try to parse error response
                        try {
                            if (xhr.responseText && xhr.responseText.includes('{')) {
                                const startIndex = xhr.responseText.indexOf('{');
                                const jsonPart = xhr.responseText.substring(startIndex);
                                const errorResponse = JSON.parse(jsonPart);
                                errorMessage = errorResponse.message || errorMessage;
                            }
                        } catch (e) {
                            // Use default error message based on status
                            if (xhr.status === 0) {
                                errorMessage = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
                            } else if (xhr.status >= 500) {
                                errorMessage = 'เกิดข้อผิดพลาดที่เซิร์ฟเวอร์';
                            } else if (xhr.status >= 400) {
                                errorMessage = 'คำขอไม่ถูกต้อง';
                            }
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด!',
                            text: errorMessage,
                            footer: 'หากปัญหายังคงอยู่ กรุณารีเฟรชหน้าเพื่อตรวจสอบผลลัพธ์'
                        });
                        $('#bulkActionModal').modal('hide');
                    }
                });
            }
        });
    });

    // Delete registration
    $(document).on('click', '.delete-registration', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        Swal.fire({
            title: 'ยืนยันการลบ',
            text: 'คุณต้องการลบการลงทะเบียนของ "' + name + '" ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/admin_registration_actions.php',
                    method: 'POST',
                    data: {
                        delete_registration: '1',
                        registration_id: id,
                        ajax: '1'
                    },
                    dataType: 'text', // Use text to handle potential non-JSON responses
                    beforeSend: function() {
                        Swal.fire({
                            title: 'กำลังลบข้อมูล...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response, textStatus, jqXHR) {
                        console.log('Delete response received:', response);
                        console.log('Response type:', typeof response);
                        
                        // Handle different response types
                        let parsedResponse;
                        if (typeof response === 'string') {
                            try {
                                parsedResponse = JSON.parse(response);
                            } catch (e) {
                                console.error('Failed to parse JSON response:', response);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด!',
                                    text: 'ระบบตอบกลับในรูปแบบที่ไม่ถูกต้อง'
                                });
                                return;
                            }
                        } else {
                            parsedResponse = response;
                        }
                        
                        if (parsedResponse && parsedResponse.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบสำเร็จ!',
                                text: parsedResponse.message || 'ลบข้อมูลเรียบร้อยแล้ว',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                $('tr[data-id="' + id + '"]').fadeOut(function() {
                                    $(this).remove();
                                    $('#registrationsTable').DataTable().row(this).remove().draw();
                                });
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด!',
                                text: parsedResponse?.message || 'ไม่สามารถลบข้อมูลได้'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Delete AJAX Error Details:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });
                        
                        let errorMessage = 'เกิดข้อผิดพลาดในการลบข้อมูล';
                        
                        if (xhr.responseText) {
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                errorMessage = errorResponse.message || errorResponse.error || errorMessage;
                            } catch (e) {
                                // If response is not JSON, check for common error patterns
                                if (xhr.responseText.includes('Fatal error')) {
                                    errorMessage = 'เกิดข้อผิดพลาดร้ายแรงในระบบ';
                                } else if (xhr.responseText.includes('Parse error')) {
                                    errorMessage = 'เกิดข้อผิดพลาดในการประมวลผล';
                                } else if (xhr.status === 500) {
                                    errorMessage = 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์';
                                } else if (xhr.status === 404) {
                                    errorMessage = 'ไม่พบไฟล์ API ที่ต้องการ';
                                } else {
                                    errorMessage = `HTTP Error ${xhr.status}: ${error}`;
                                }
                            }
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด!',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    });

    // View payment slip
    $(document).on('click', '.view-payment', function() {
        var slipPath = $(this).data('slip');
        if (slipPath) {
            $('#paymentSlipImg').attr('src', slipPath);
            $('#downloadSlip').attr('href', slipPath);
            $('#paymentSlipModal').modal('show');
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'ไม่พบไฟล์',
                text: 'ไม่พบหลักฐานการโอนเงิน'
            });
        }
    });

    // View details (placeholder for future development)
    $(document).on('click', '.view-details', function() {
        var id = $(this).data('id');
        // TODO: Implement detailed view modal
        Swal.fire({
            icon: 'info',
            title: 'รายละเอียดการลงทะเบียน',
            text: 'ฟีเจอร์นี้จะพัฒนาในอนาคต (ID: ' + id + ')'
        });
    });
});

// Export functions
function exportTable() {
    $('#registrationsTable').DataTable().button('.buttons-excel').trigger();
}

function printTable() {
    $('#registrationsTable').DataTable().button('.buttons-print').trigger();
}

// Real-time updates (optional - can be enhanced with WebSocket)
function refreshData() {
    location.reload();
}

// Auto-refresh every 5 minutes for pending registrations
setInterval(function() {
    if ($('.badge-warning').length > 0) { // If there are pending registrations
        // Optional: Add a subtle notification that data will refresh
        // refreshData();
    }
}, 300000); // 5 minutes
