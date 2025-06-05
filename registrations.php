<?php
// Start the session
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

include("./config/db.php");

// Set page-specific variables
$pageTitle = "Student Registrations - School Management System";
$activePage = "registrations";
$contentPath = "content/registrations.php";

// Page specific CSS
$pageSpecificCSS = '<link href="assets/css/registrations.css" rel="stylesheet">';

// Page specific scripts for DataTables and SweetAlert2
$pageSpecificScripts = '
<!-- Page level plugins -->
<script src="sb-admin-2/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="sb-admin-2/vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Page level custom scripts -->
<script>
$(document).ready(function() {
  // Initialize DataTable with search, pagination and sorting
  $("#registrationsTable").DataTable({
    order: [[3, "desc"]],
    language: {
      search: "ຄົ້ນຫາ:",
      lengthMenu: "ສະແດງ _MENU_ ລາຍການ",
      zeroRecords: "ບໍ່ພົບຂໍ້ມູນ",
      info: "ສະແດງໜ້າທີ່ _PAGE_ ຈາກ _PAGES_",
      infoEmpty: "ບໍ່ມີຂໍ້ມູນ",
      infoFiltered: "(ກັ່ນຕອງຈາກ _MAX_ ລາຍການ)",
      paginate: {
        first: "ໜ້າທຳອິດ",
        last: "ໜ້າສຸດທ້າຍ",
        next: "ໜ້າຕໍ່ໄປ",
        previous: "ໜ້າກ່ອນ"
      }
    }
  });
  
  // Initialize tooltips for bootstrap
  $("[data-toggle=\'tooltip\']").tooltip();
  
  // Fade out alerts after 5 seconds
  $(".alert").delay(5000).fadeOut(500);
  
  // Show payment slip in modal with zoom functionality
  $(".view-payment").click(function() {
    var slipPath = $(this).data("slip");
    $("#paymentSlipImg").attr("src", slipPath);
    $("#paymentSlipModal").modal("show");
    
    // Add zoom functionality
    let scale = 1;
    const zoomSpeed = 0.1;
    const paymentSlipImg = document.getElementById("paymentSlipImg");
    
    $("#zoomIn").click(function() {
      scale += zoomSpeed;
      paymentSlipImg.style.transform = `scale(${scale})`;
    });
    
    $("#zoomOut").click(function() {
      if (scale > zoomSpeed) {
        scale -= zoomSpeed;
        paymentSlipImg.style.transform = `scale(${scale})`;
      }
    });
    
    $("#resetZoom").click(function() {
      scale = 1;
      paymentSlipImg.style.transform = `scale(${scale})`;
    });
    
    // Reset zoom when modal is closed
    $("#paymentSlipModal").on("hidden.bs.modal", function() {
      scale = 1;
      paymentSlipImg.style.transform = `scale(${scale})`;
    });
  });
  
  // View student details in modal
  $(".view-student").click(function() {
    var studentData = $(this).data("student");
    var studentName = $(this).data("name");
    var registrationType = $(this).data("type");
    var classInfo = $(this).data("class");
    
    $("#studentDetailsModalLabel").text(studentName);
    
    // Format and show student details
    let detailsHtml = `<div class="table-responsive">
      <table class="table table-bordered">
        <tr>
          <th>ຊື່ ແລະ ນາມສະກຸນ</th>
          <td>${studentName}</td>
        </tr>
        <tr>
          <th>ປະເພດການລົງທະບຽນ</th>
          <td>${registrationType === "new" ? "ນັກຮຽນໃໝ່" : "ນັກຮຽນເກົ່າ"}</td>
        </tr>
        <tr>
          <th>ຫ້ອງຮຽນ</th>
          <td>${classInfo || "ບໍ່ມີຂໍ້ມູນ"}</td>
        </tr>`;
    
    // Parse and display JSON data if available
    if (studentData) {
      try {
        const data = JSON.parse(studentData);
        Object.keys(data).forEach(key => {
          if (key !== "name" && key !== "type" && key !== "class") {
            detailsHtml += `<tr>
              <th>${formatFieldName(key)}</th>
              <td>${data[key]}</td>
            </tr>`;
          }
        });
      } catch (e) {
        console.error("Error parsing student data:", e);
      }
    }
    
    detailsHtml += `</table></div>`;
    $("#studentDetailsContent").html(detailsHtml);
    $("#studentDetailsModal").modal("show");
  });
  
  // Format field names for display
  function formatFieldName(field) {
    const fieldMap = {
      "fname": "ຊື່",
      "lname": "ນາມສະກຸນ",
      "gender": "ເພດ",
      "birth_date": "ວັນເດືອນປີເກີດ",
      "village": "ບ້ານ",
      "district": "ເມືອງ",
      "province": "ແຂວງ",
      "student_id": "ລະຫັດນັກຮຽນ"
    };
    
    return fieldMap[field] || field.replace(/_/g, " ");
  }
  
  // Approve registration confirmation
  $(".approve-btn").click(function(e) {
    e.preventDefault();
    var regId = $(this).data("id");
    var regType = $(this).data("type");
    var studentName = $(this).data("name");
    
    Swal.fire({
      title: "ຢືນຢັນການອະນຸມັດ",
      html: `ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການອະນຸມັດການລົງທະບຽນຂອງ <strong>${studentName}</strong>?<br>
      <small>${regType === "new" ? "ລະບົບຈະສ້າງນັກຮຽນໃໝ່ໃນລະບົບ" : "ການລົງທະບຽນຈະຖືກອະນຸມັດ"}</small>`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#28a745",
      cancelButtonColor: "#d33",
      confirmButtonText: "ຢືນຢັນການອະນຸມັດ",
      cancelButtonText: "ຍົກເລີກ"
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading state
        Swal.fire({
          title: "ກຳລັງດຳເນີນການ...",
          html: "ກຳລັງປະມວນຜົນການອະນຸມັດ",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        $("#action_type").val("approve");
        $("#registration_id").val(regId);
        $("#actionForm").submit();
      }
    });
  });
  
  // Reject registration confirmation
  $(".reject-btn").click(function(e) {
    e.preventDefault();
    var regId = $(this).data("id");
    var studentName = $(this).data("name");
    
    Swal.fire({
      title: "ຢືນຢັນການປະຕິເສດ",
      html: `ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການປະຕິເສດການລົງທະບຽນຂອງ <strong>${studentName}</strong>?`,
      icon: "warning",
      input: "textarea",
      inputLabel: "ເຫດຜົນໃນການປະຕິເສດ (ຈະຖືກສົ່ງໃຫ້ຜູ້ປົກຄອງ)",
      inputPlaceholder: "ກະລຸນາລະບຸເຫດຜົນໃນການປະຕິເສດ...",
      inputAttributes: {
        "aria-label": "ເຫດຜົນໃນການປະຕິເສດ",
        "required": "required"
      },
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "ປະຕິເສດ",
      cancelButtonText: "ຍົກເລີກ",
      preConfirm: (notes) => {
        if (!notes || notes.trim() === "") {
          Swal.showValidationMessage("ກະລຸນາລະບຸເຫດຜົນໃນການປະຕິເສດ");
        }
        return notes;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading state
        Swal.fire({
          title: "ກຳລັງດຳເນີນການ...",
          html: "ກຳລັງປະມວນຜົນການປະຕິເສດ",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        $("#action_type").val("reject");
        $("#registration_id").val(regId);
        $("#notes").val(result.value);
        $("#actionForm").submit();
      }
    });
  });
  
  // Filter by date range
  $("#filterDates").click(function() {
    const startDate = $("#startDate").val();
    const endDate = $("#endDate").val();
    if (startDate && endDate) {
      window.location.href = `registrations.php?status=${$("#currentStatus").val()}&start=${startDate}&end=${endDate}`;
    } else {
      Swal.fire({
        title: "ຂໍ້ມູນບໍ່ຄົບຖ້ວນ",
        text: "ກະລຸນາເລືອກວັນທີເລີ່ມຕົ້ນ ແລະ ວັນທີສິ້ນສຸດ",
        icon: "info"
      });
    }
  });
  
  // Reset date filters
  $("#resetFilter").click(function() {
    window.location.href = `registrations.php?status=${$("#currentStatus").val()}`;
  });
  
  // Update download link for payment slip
  $(".view-payment").click(function() {
    const slipPath = $(this).data("slip");
    $("#downloadSlip").attr("href", slipPath);
  });
  
  // Export to CSV functionality
  $("#exportCsv").click(function() {
    // Get table data
    const table = document.getElementById("registrationsTable");
    let csv = [];
    const rows = table.querySelectorAll("tr");
    
    for (let i = 0; i < rows.length; i++) {
      let row = [], cols = rows[i].querySelectorAll("td, th");
      
      for (let j = 0; j < cols.length - 1; j++) { // Skip the last column (actions)
        // Get the text content, clean it up
        let text = cols[j].textContent.trim().replace(/(\r\n|\n|\r)/gm, " ").replace(/\s+/g, " ");
        // Remove any HTML tags
        text = text.replace(/<[^>]*>?/gm, "");
        // Wrap with quotes if contains comma
        if (text.includes(",")) {
          text = "\"" + text + "\"";
        }
        row.push(text);
      }
      csv.push(row.join(","));
    }
    
    // Create CSV file
    const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "registrations_export_" + new Date().toISOString().slice(0,10) + ".csv");
    document.body.appendChild(link);
    
    link.click();
    document.body.removeChild(link);
  });
});
</script>
';

// Include the layout template
include("includes/layout.php");
?>
