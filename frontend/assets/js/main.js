// Main application initialization and setup

document.addEventListener("DOMContentLoaded", function () {
  initializeApplication();
});

async function initializeApplication() {
  console.log("Initializing Sanfan Primary School Application...");

  // Load components
  loadComponents();

  // Load classes for registration
  await RegistrationManager.loadClasses();

  // Load academic years for registration
  await loadAcademicYears();

  // Show home page by default
  showPage("home");

  // Set default transfer date to today
  setDefaultTransferDate();

  // Animate counters on home page
  setTimeout(() => {
    animateCounter("studentsCount", 500);
    animateCounter("teachersCount", 50);
    animateCounter("awardsCount", 25);
    animateCounter("yearsCount", 15);
  }, 500);

  // Setup event listeners
  setupEventListeners();

  console.log("Application initialized successfully!");
}

function loadComponents() {
  console.log("Loading components...");
  
  // Load navigation
  const navigationContainer = document.getElementById("navigation-container");
  if (navigationContainer) {
    try {
      if (typeof ComponentRenderer !== 'undefined' && typeof ComponentRenderer.renderNavigation === 'function') {
        const navHTML = ComponentRenderer.renderNavigation();
        navigationContainer.innerHTML = navHTML;
        console.log("✓ Navigation loaded successfully");
      } else {
        console.error("✗ ComponentRenderer or renderNavigation not available");
      }
    } catch (error) {
      console.error("✗ Error loading navigation:", error);
    }
  } else {
    console.error("✗ Navigation container not found");
  }

  // Load hero section
  const heroSection = document.getElementById("hero-section");
  if (heroSection) {
    heroSection.innerHTML = ComponentRenderer.renderHeroSection();
  }

  // Load stats section
  const statsSection = document.getElementById("stats-section");
  if (statsSection) {
    statsSection.innerHTML = ComponentRenderer.renderStatsSection();
  }

  // Load services section
  const servicesSection = document.getElementById("services-section");
  if (servicesSection) {
    servicesSection.innerHTML = ComponentRenderer.renderServicesSection();
  }

  // Load footer
  const footerContainer = document.getElementById("footer-container");
  if (footerContainer) {
    footerContainer.innerHTML = ComponentRenderer.renderFooter();
  }

  // Load page content
  loadPageContent();
}

function loadPageContent() {
  // Load registration page content
  const registrationContent = document.getElementById("registration-content");
  if (registrationContent) {
    registrationContent.innerHTML = getRegistrationPageContent();
  }

  // Load scores page content
  const scoresContent = document.getElementById("scores-content");
  if (scoresContent) {
    scoresContent.innerHTML = getScoresPageContent();
  }

  // Load activities page content
  const activitiesContent = document.getElementById("activities-content");
  if (activitiesContent) {
    activitiesContent.innerHTML = getActivitiesPageContent();
  }

  // Load gallery page content
  const galleryContent = document.getElementById("gallery-content");
  if (galleryContent) {
    galleryContent.innerHTML = getGalleryPageContent();
  }

  // Load about page content
  const aboutContent = document.getElementById("about-content");
  if (aboutContent) {
    aboutContent.innerHTML = getAboutPageContent();
  }
}

function setDefaultTransferDate() {
  const today = new Date().toISOString().split("T")[0];
  const transferDateInput = document.querySelector('[name="transfer_date"]');
  if (transferDateInput) {
    transferDateInput.value = today;
  }
}

function setupEventListeners() {
  // Registration form event listener
  const registrationForm = document.getElementById("registrationForm");
  if (registrationForm) {
    registrationForm.addEventListener("submit", (event) => {
      RegistrationManager.handleRegistrationSubmit(event);
    });
  }

  // Set default registration type
  RegistrationManager.toggleRegistrationType("existing");

  // Search inputs event listeners
  const studentIdInput = document.getElementById("studentId");
  const studentNameInput = document.getElementById("studentName");
  const scoreStudentIdInput = document.getElementById("scoreStudentId");

  if (studentIdInput) {
    studentIdInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") RegistrationManager.searchStudent();
    });
  }

  if (studentNameInput) {
    studentNameInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") RegistrationManager.searchStudent();
    });
  }

  if (scoreStudentIdInput) {
    scoreStudentIdInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") ScoresManager.searchScores();
    });
  }

  // Phone input validation
  const phoneInputs = document.querySelectorAll('input[type="tel"]');
  phoneInputs.forEach((input) => {
    input.addEventListener("input", function (e) {
      this.value = this.value.replace(/[^\d\s\-\+]/g, "");
    });
  });

  // Amount input validation
  const amountInput = document.querySelector('[name="transfer_amount"]');
  if (amountInput) {
    amountInput.addEventListener("input", function (e) {
      const value = parseInt(this.value);
      if (value < 500000) {
        this.setCustomValidity("ຈຳນວນເງີນຕ້ອງບໍ່ນ້ອຍກວ່າ 500,000 ກີບ");
      } else {
        this.setCustomValidity("");
      }
    });
  }
}

// Load academic years for registration form
async function loadAcademicYears() {
  try {
    console.log("Loading academic years...");
    const response = await fetch('/school-management/frontend/api/get_academic_years.php');
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    console.log('Academic years response:', data);
    
    if (data.success && data.years) {
      const academicYearSelect = document.getElementById('academicYear');
      if (academicYearSelect) {
        // Clear existing options except the first one
        academicYearSelect.innerHTML = '<option value="">ເລືອກປີການສຶກສາ</option>';
        
        // Add academic year options
        data.years.forEach(year => {
          const option = document.createElement('option');
          option.value = year.id;
          option.textContent = year.year_name;
          academicYearSelect.appendChild(option);
        });
        
        console.log(`✓ Loaded ${data.years.length} academic years`);
      } else {
        console.error("✗ Academic year select element not found");
      }
    } else {
      console.error('✗ Error loading academic years:', data.error || 'Unknown error');
    }
  } catch (error) {
    console.error('✗ Error loading academic years:', error);
  }
}

// Page content functions
function getRegistrationPageContent() {
  return `
        <section class="py-20 bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="text-center mb-12">
                        <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">ລົງທະບຽນນັກຮຽນ</h2>
                        <p class="text-xl text-gray-600">ເລືອກປະເພດການລົງທະບຽນ</p>
                    </div>

                    <form id="registrationForm" class="bg-white rounded-3xl shadow-xl p-8 md:p-12" enctype="multipart/form-data">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">ປະເພດການລົງທະບຽນ</h3>
                            <div class="flex justify-center gap-4">
                                <button type="button" id="existingStudentBtn" onclick="toggleRegistrationType('existing')" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                                    <i class="fas fa-user-check mr-2"></i>ນັກຮຽນເກົ່າ
                                </button>
                                <button type="button" id="newStudentBtn" onclick="toggleRegistrationType('new')" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold transition-colors">
                                    <i class="fas fa-user-plus mr-2"></i>ນັກຮຽນໃໝ່
                                </button>
                            </div>
                        </div>

                        <!-- Academic Year Selection -->
                        <div class="mb-8">
                            <div class="p-6 bg-purple-50 rounded-2xl">
                                <h3 class="text-2xl font-bold text-gray-800 mb-4">
                                    <i class="fas fa-calendar-alt mr-2 text-purple-600"></i>
                                    ເລືອກປີການສຶກສາ
                                </h3>
                                <div class="max-w-md mx-auto">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">ປີການສຶກສາ *</label>
                                    <select id="academicYear" name="academic_year_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <option value="">ເລືອກປີການສຶກສາ</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Student Section -->
                        <div id="existingStudentSection" class="mb-8">
                            <div class="p-6 bg-blue-50 rounded-2xl">
                                <h3 class="text-2xl font-bold text-gray-800 mb-4">
                                    <i class="fas fa-search mr-2 text-blue-600"></i>
                                    ຄົ້ນຫາຂໍ້ມູນນັກຮຽນເກົ່າ
                                </h3>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ລະຫັດນັກຮຽນ</label>
                                        <input type="text" id="studentId" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ປ້ອນລະຫັດນັກຮຽນ">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ຊື່ນັກຮຽນ</label>
                                        <input type="text" id="studentName" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ປ້ອນຊື່ນັກຮຽນ">
                                    </div>
                                </div>
                                <button type="button" onclick="searchStudent()" class="mt-4 bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-search mr-2"></i>ຄົ້ນຫາ
                                </button>
                            </div>

                            <!-- Student Info Display -->
                            <div id="studentInfo" class="hidden mt-6 p-6 bg-green-50 rounded-2xl border-2 border-green-200">
                                <h3 class="text-xl font-bold text-green-800 mb-4">
                                    <i class="fas fa-user-check mr-2"></i>ຂໍ້ມູນນັກຮຽນທີ່ພົບເຫັນ
                                </h3>
                                <div id="studentDetails" class="grid md:grid-cols-2 gap-4 text-gray-700"></div>
                            </div>
                        </div>

                        <!-- New Student Section -->
                        <div id="newStudentSection" class="hidden mb-8">
                            <div class="p-6 bg-green-50 rounded-2xl">
                                <h3 class="text-2xl font-bold text-gray-800 mb-6">
                                    <i class="fas fa-user-plus mr-2 text-green-600"></i>
                                    ຂໍ້ມູນນັກຮຽນໃໝ່
                                </h3>
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ຊື່ *</label>
                                        <input type="text" name="student_fname" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="ປ້ອນຊື່">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ນາມສະກຸນ *</label>
                                        <input type="text" name="student_lname" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="ປ້ອນນາມສະກຸນ">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ເພດ *</label>
                                        <select name="student_gender" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                            <option value="">ເລືອກເພດ</option>
                                            <option value="M">ຊາຍ</option>
                                            <option value="F">ຍິງ</option>
                                            <option value="O">ອື່ນໆ</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ວັນເກີດ *</label>
                                        <input type="date" name="student_birth_date" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ບ້ານ *</label>
                                        <input type="text" name="student_village" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="ປ້ອນຊື່ບ້ານ">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ເມືອງ *</label>
                                        <input type="text" name="student_district" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="ປ້ອນຊື່ເມືອງ">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ແຂວງ *</label>
                                        <input type="text" name="student_province" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="ປ້ອນຊື່ແຂວງ">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">ຊັ້ນຮຽນ *</label>
                                        <select id="studentClass" name="class_id" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                            <option value="">ເລືອກຊັ້ນຮຽນ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Information -->
                        <div class="space-y-6">
                            <h3 class="text-2xl font-bold text-gray-800 mb-6">
                                <i class="fas fa-user-friends mr-2 text-purple-600"></i>
                                ຂໍ້ມູນຜູ້ປົກຄອງ
                            </h3>
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">ຊື່ຜູ້ປົກຄອງ *</label>
                                    <input type="text" name="parent_name" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ປ້ອນຊື່ຜູ້ປົກຄອງ">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">ເບີໂທລະສັບ *</label>
                                    <input type="tel" name="parent_phone" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ປ້ອນເບີໂທລະສັບ">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ອີເມວ</label>
                                <input type="email" name="parent_email" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ປ້ອນອີເມວ">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ທີ່ຢູ່</label>
                                <textarea name="address" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ປ້ອນທີ່ຢູ່"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ຄວາມສຳພັນກັບນັກຮຽນ *</label>
                                <select name="relationship" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">ເລືອກຄວາມສຳພັນ</option>
                                    <option value="father">ພໍ່</option>
                                    <option value="mother">ແມ່</option>
                                    <option value="guardian">ຜູ້ປົກຄອງ</option>
                                    <option value="relative">ຍາດຕິພີ່ນ້ອງ</option>
                                </select>
                            </div>

                            <!-- Payment Section -->
                            <div class="mt-8 p-6 bg-yellow-50 rounded-2xl border-2 border-yellow-200">
                                <h3 class="text-2xl font-bold text-gray-800 mb-6">
                                    <i class="fas fa-credit-card mr-2 text-yellow-600"></i>
                                    ການຊຳລະຄ່າລົງທະບຽນ
                                </h3>

                                <div class="grid md:grid-cols-2 gap-8">
                                    <!-- QR Code Section -->
                                    <div class="text-center">
                                        <h4 class="text-lg font-semibold text-gray-700 mb-4">ສະແກນ QR Code ເພື່ອຊຳລະເງີນ</h4>
                                        <div class="bg-white p-6 rounded-2xl shadow-lg inline-block">
                                            <div class="w-48 h-48 bg-gradient-to-br from-blue-100 to-purple-100 flex items-center justify-center rounded-xl border-2 border-dashed border-gray-300">
                                                <div class="text-center">
                                                    <i class="fas fa-qrcode text-6xl text-gray-400 mb-2"></i>
                                                    <p class="text-sm text-gray-500">QR Code BCELONE</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <p class="text-lg font-bold text-blue-600">ຄ່າລົງທະບຽນ: 500,000 ກີບ</p>
                                            <p class="text-sm text-gray-600">ໂຮງຮຽນປະຖົມສານຝັນ</p>
                                            <p class="text-sm text-gray-600">ເລກບັນຊີ: 0XX-X-XXXXX-X</p>
                                        </div>
                                    </div>

                                    <!-- Upload Section -->
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-700 mb-4">ອັບໂຫລດຫຼັກຖານການໂອນເງີນ</h4>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">ຮູບພາບການໂອນເງີນ *</label>
                                                <div class="relative">
                                                    <input type="file" id="paymentProof" name="payment_slip" accept="image/*" required class="hidden" onchange="handleFileUpload(this)">
                                                    <label for="paymentProof" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                                            <p class="mb-2 text-sm text-gray-500">
                                                                <span class="font-semibold">ກົດເພື່ອເລືອກໄຟລ໌</span> ຫຼືລາກໄຟລ໌ມາວາງ
                                                            </p>
                                                            <p class="text-xs text-gray-500">PNG, JPG ຫຼື JPEG (ຂະໜາດບໍ່ເກີນ 5MB)</p>
                                                        </div>
                                                    </label>
                                                </div>

                                                <!-- File Preview -->
                                                <div id="filePreview" class="hidden mt-4 p-4 bg-green-50 rounded-xl border border-green-200">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                                                        <div class="flex-1">
                                                            <p class="text-sm font-medium text-green-800">ໄຟລ໌ທີ່ເລືອກ:</p>
                                                            <p id="fileName" class="text-sm text-green-600"></p>
                                                        </div>
                                                        <button type="button" onclick="removeFile()" class="text-red-500 hover:text-red-700">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <div class="mt-3">
                                                        <img id="imagePreview" class="max-w-full h-32 object-cover rounded-lg" style="display: none;">
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">ວັນທີໂອນ *</label>
                                                <input type="date" name="transfer_date" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">ເວລາທີ່ໂອນ *</label>
                                                <input type="time" name="transfer_time" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">ຈຳນວນເງີນທີ່ໂອນ (ກີບ) *</label>
                                                <input type="number" name="transfer_amount" required min="500000" value="500000" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Instructions -->
                                <div class="mt-6 p-4 bg-blue-50 rounded-xl">
                                    <h5 class="font-semibold text-blue-800 mb-2">
                                        <i class="fas fa-info-circle mr-2"></i>ຂັ້ນຕອນການຊຳລະເງີນ
                                    </h5>
                                    <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                                        <li>ສະແກນ QR Code ດ້ວຍແອັບທະນາຄານຂອງທ່ານ</li>
                                        <li>ຊຳລະເງີນຈຳນວນ 500,000 ກີບ</li>
                                        <li>ຖ່າຍພາບຫຼັກຖານການໂອນເງີນ (ສະລິບ)</li>
                                        <li>ອັບໂຫລດຫຼັກຖານການໂອນເງີນດ້ານເທີງ</li>
                                        <li>ປ້ອນຂໍ້ມູນວັນທີ່ແລະເວລາທີໂອນ</li>
                                        <li>ກົດປຸ່ມ "ສົ່ງໃບລົງທະບຽນ" ເພື່ອສຳເລັດ</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="pt-6">
                                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-4 rounded-xl font-semibold text-lg hover:from-blue-700 hover:to-purple-700 transition-all transform hover:scale-105">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    ສົ່ງໃບສະໝັກ
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    `;
}

function getScoresPageContent() {
  return `
        <section class="py-20 bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
            <div class="container mx-auto px-4">
                <div class="max-w-5xl mx-auto">
                    <div class="text-center mb-12">
                        <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">ກວດສອບຄະແນນ</h2>
                        <p class="text-xl text-gray-600">ປ້ອນລະຫັດນັກຮຽນເພື່ອເບິ່ງຜົນການຮຽນ</p>
                    </div>

                    <div class="bg-white rounded-3xl shadow-xl p-8 md:p-12">
                        <!-- Search Section -->
                        <div class="mb-8">
                            <div class="max-w-md mx-auto">
                                <div class="bg-gradient-to-r from-green-50 to-blue-50 p-6 rounded-2xl mb-6">
                                    <label class="block text-lg font-semibold text-gray-700 mb-4 text-center">
                                        <i class="fas fa-search text-green-500 mr-2"></i>ຄົ້ນຫາຂໍ້ມູນຄະແນນ
                                    </label>
                                    <div class="flex gap-3">
                                        <input type="text" id="scoreStudentId" 
                                            class="flex-1 px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent text-center text-lg" 
                                            placeholder="ປ້ອນລະຫັດນັກຮຽນ">
                                        <button onclick="searchScores()" 
                                            class="bg-gradient-to-r from-green-500 to-blue-500 text-white px-6 py-4 rounded-xl font-semibold hover:from-green-600 hover:to-blue-600 transition-all transform hover:scale-105">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Score Results -->
                        <div id="scoreResults" class="hidden">
                            <!-- Student Info Card -->
                            <div class="mb-8">
                                <div id="scoreStudentInfo" class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-2xl p-6"></div>
                            </div>

                            <!-- Statistics Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <div class="bg-white p-6 rounded-xl shadow-md border border-blue-100">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="text-gray-500 text-sm">ຄະແນນສະເລ່ຍ</h5>
                                            <p class="text-2xl font-bold text-blue-600" id="avgScore">0</p>
                                        </div>
                                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-calculator text-blue-500"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white p-6 rounded-xl shadow-md border border-green-100">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="text-gray-500 text-sm">ຄະແນນສູງສຸດ</h5>
                                            <p class="text-2xl font-bold text-green-600" id="highestScore">0</p>
                                        </div>
                                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-trophy text-green-500"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white p-6 rounded-xl shadow-md border border-purple-100">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="text-gray-500 text-sm">ຈຳນວນວິຊາ</h5>
                                            <p class="text-2xl font-bold text-purple-600" id="totalSubjects">0</p>
                                        </div>
                                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-book text-purple-500"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Score Tables -->
                            <div class="space-y-8">
                                <h3 class="text-2xl font-bold text-gray-800 text-center mb-6">
                                    <i class="fas fa-chart-line text-green-600 mr-2"></i>ຜົນການຮຽນ
                                </h3>
                                <div id="scoreTable" class="overflow-x-auto"></div>
                            </div>
                        </div>

                        <!-- No Results -->
                        <div id="noScoreResults" class="hidden text-center py-12">
                            <div class="bg-gray-50 rounded-2xl p-8">
                                <i class="fas fa-search text-6xl text-gray-400 mb-4"></i>
                                <h3 class="text-2xl font-bold text-gray-600 mb-2">ບໍ່ພົບຂໍ້ມູນ</h3>
                                <p class="text-gray-500">ກະລຸນາກວດສອບລະຫັດນັກຮຽນ ແລະ ລອງໃໝ່ອີກຄັ້ງ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    `;
}

function getActivitiesPageContent() {
  return `
        <section class="py-20 bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">ກິດຈະກຳນັກຮຽນ</h2>
                    <p class="text-xl text-gray-600">ກິດຈະກຳຫຼາກຫຼາຍເພື່ອພັດທະນາສັກກະຍະພາບເດັກ</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                    <div class="bg-white p-8 rounded-2xl shadow-lg card-hover">
                        <div class="w-20 h-20 bg-gradient-to-r from-red-400 to-pink-500 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <i class="fas fa-palette text-white text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4 text-center">ສິລະປະ ແລະ ຫັດຖະກຳ</h3>
                        <p class="text-gray-600 text-center mb-6">ພັດທະນາຄວາມຄິດສ້າງສັນຜ່ານການແຕ້ມຮູບ, ປັ້ນດິນ ແລະ ງານຫັດຖະກຳ</p>
                    </div>

                    <div class="bg-white p-8 rounded-2xl shadow-lg card-hover">
                        <div class="w-20 h-20 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <i class="fas fa-laptop-code text-white text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4 text-center">ເຕັກໂນໂລຊີ ແລະ ການຂຽນໂປຣແກຣມ</h3>
                        <p class="text-gray-600 text-center mb-6">ຮຽນຮູ້ເຕັກໂນໂລຊີທັນສະໄໝ ແລະ ທັກສະການຂຽນໂປຣແກຣມເບື້ອງຕົ້ນ</p>
                    </div>

                    <div class="bg-white p-8 rounded-2xl shadow-lg card-hover">
                        <div class="w-20 h-20 bg-gradient-to-r from-green-400 to-emerald-500 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <i class="fas fa-music text-white text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4 text-center">ດົນຕີ ການສະແດງ</h3>
                        <p class="text-gray-600 text-center mb-6">ພັດທະນາທັກສະທາງດົນຕີ ສິລະປະການສະແດງຕ່າງໆ</p>
                    </div>

                    <div class="bg-white p-8 rounded-2xl shadow-lg card-hover">
                        <div class="w-20 h-20 bg-gradient-to-r from-orange-400 to-yellow-500 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <i class="fas fa-running text-white text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4 text-center">ກິລາ ແລະ ສຸຂະພາບ</h3>
                        <p class="text-gray-600 text-center mb-6">ສົ່ງເສີມໃຫ້ເດັກມີຮ່າງກາຍແຂງແຮງ ແລະ ສຸຂະພາບຈິດທີ່ດີ</p>
                    </div>

                    <div class="bg-white p-8 rounded-2xl shadow-lg card-hover">
                        <div class="w-20 h-20 bg-gradient-to-r from-purple-400 to-indigo-500 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <i class="fas fa-microscope text-white text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4 text-center">ວິທະຍາສາດ ແລະ ການທົດລອງ</h3>
                        <p class="text-gray-600 text-center mb-6">ສຳຫຼວດໂລກຂອງວິທະຍາສາດຜ່ານການທົດລອງທີ່ມ່ວນຊື່ນ</p>
                    </div>

                    <div class="bg-white p-8 rounded-2xl shadow-lg card-hover">
                        <div class="w-20 h-20 bg-gradient-to-r from-pink-400 to-rose-500 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <i class="fas fa-heart text-white text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4 text-center">ກິດຈະກຳຈິດອາສາ</h3>
                        <p class="text-gray-600 text-center mb-6">ປູກຝັງຈິດສຳນຶກໃນການຊ່ວຍເຫຼືອສັງຄົມ ແລະ ສິ່ງແວດລ້ອມ</p>
                    </div>
                </div>
            </div>
        </section>
    `;
}

function getGalleryPageContent() {
  return `
        <section class="py-20 bg-gradient-to-br from-pink-50 to-purple-50 min-h-screen">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">ຮູບພາບກິດຈະກຳ</h2>
                    <p class="text-xl text-gray-600">ຊົມຮູບພາບຄວາມຊົງຈຳດີໆ ຈາກກິດຈະກຳຕ່າງໆ ຂອງໂຮງຮຽນ</p>
                </div>
                
                <div class="flex flex-wrap justify-center gap-4 mb-12">
                    <button onclick="filterGallery('all')" class="gallery-filter-btn active bg-blue-600 text-white px-6 py-3 rounded-full font-semibold hover:bg-blue-700 transition-colors">ທັງໝົດ</button>
                    <button onclick="filterGallery('sports')" class="gallery-filter-btn bg-gray-200 text-gray-700 px-6 py-3 rounded-full font-semibold hover:bg-gray-300 transition-colors">ກິລາ</button>
                    <button onclick="filterGallery('arts')" class="gallery-filter-btn bg-gray-200 text-gray-700 px-6 py-3 rounded-full font-semibold hover:bg-gray-300 transition-colors">ສິລະປະ</button>
                    <button onclick="filterGallery('science')" class="gallery-filter-btn bg-gray-200 text-gray-700 px-6 py-3 rounded-full font-semibold hover:bg-gray-300 transition-colors">ວິທະຍາສາດ</button>
                    <button onclick="filterGallery('events')" class="gallery-filter-btn bg-gray-200 text-gray-700 px-6 py-3 rounded-full font-semibold hover:bg-gray-300 transition-colors">ງານພິເສດ</button>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" id="galleryGrid">
                    <!-- Sports Items -->
                    <div class="gallery-item sports bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <div class="h-64 bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                            <i class="fas fa-futbol text-white text-6xl"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">ການແຂ່ງຂັນເຕະບານ</h3>
                            <p class="text-gray-600">ນັກຮຽນສະແດງທັກສະການຫຼິ້ນເຕະບານໃນການແຂ່ງຂັນລະຫວ່າງໂຮງຮຽນ</p>
                        </div>
                    </div>

                    <div class="gallery-item sports bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <div class="h-64 bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center">
                            <i class="fas fa-swimmer text-white text-6xl"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">ກິລາລອຍນ້ຳ</h3>
                            <p class="text-gray-600">ນັກຮຽນຝຶກຊ້ອມ ແລະ ແຂ່ງຂັນລອຍນ້ຳໃນສະລອຍນ້ຳຂອງໂຮງຮຽນ</p>
                        </div>
                    </div>

                    <!-- Arts Items -->
                    <div class="gallery-item arts bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <div class="h-64 bg-gradient-to-br from-pink-400 to-purple-500 flex items-center justify-center">
                            <i class="fas fa-palette text-white text-6xl"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">ງານສິລະປະສ້າງສັນ</h3>
                            <p class="text-gray-600">ນັກຮຽນສ້າງສັນຜົນງານສິລະປະທີ່ສວຍງາມ ແລະ ມີເອກະລັກ</p>
                        </div>
                    </div>

                    <div class="gallery-item arts bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <div class="h-64 bg-gradient-to-br from-indigo-400 to-pink-500 flex items-center justify-center">
                            <i class="fas fa-music text-white text-6xl"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">ການສະແດງດົນຕີ</h3>
                            <p class="text-gray-600">ນັກຮຽນສະແດງຄວາມສາມາດທາງດົນຕີໃນງານສະແດງປະຈຳປີ</p>
                        </div>
                    </div>

                    <!-- Science Items -->
                    <div class="gallery-item science bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <div class="h-64 bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center">
                            <i class="fas fa-microscope text-white text-6xl"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">ການທົດລອງວິທະຍາສາດ</h3>
                            <p class="text-gray-600">ນັກຮຽນຮຽນຮູ້ຜ່ານການທົດລອງວິທະຍາສາດທີ່ໜ້າສົນໃຈ</p>
                        </div>
                    </div>

                    <div class="gallery-item science bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <div class="h-64 bg-gradient-to-br from-green-400 to-teal-500 flex items-center justify-center">
                            <i class="fas fa-seedling text-white text-6xl"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">ໂຄງການສິ່ງແວດລ້ອມ</h3>
                            <p class="text-gray-600">ນັກຮຽນຮ່ວມກັນປູກຕົ້ນໄມ້ ແລະ ເບິ່ງແຍງສິ່ງແວດລ້ອມ</p>
                        </div>
                    </div>

                    <!-- Events Items -->
                    <div class="gallery-item events bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <div class="h-64 bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-white text-6xl"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">ງານຈົບຊັ້ນ</h3>
                            <p class="text-gray-600">ງານສົ່ງນັກຮຽນຊັ້ນ ປ.5 ສູ່ການສຶກສາໃນລະດັບທີ່ສູງຂຶ້ນ</p>
                        </div>
                    </div>

                    <div class="gallery-item events bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <div class="h-64 bg-gradient-to-br from-red-400 to-pink-500 flex items-center justify-center">
                            <i class="fas fa-heart text-white text-6xl"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">ວັນແມ່ແຫ່ງຊາດ</h3>
                            <p class="text-gray-600">ນັກຮຽນສະແດງຄວາມກະຕັນຍູກະຕະເວທິຕາຕໍ່ແມ່ໃນວັນແມ່</p>
                        </div>
                    </div>

                    <div class="gallery-item arts bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <div class="h-64 bg-gradient-to-br from-purple-400 to-indigo-500 flex items-center justify-center">
                            <i class="fas fa-theater-masks text-white text-6xl"></i>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">ການສະແດງລະຄອນເວທີ</h3>
                            <p class="text-gray-600">ນັກຮຽນສະແດງລະຄອນເວທີທີ່ສ້າງສັນ ແລະ ໜ້າປະທັບໃຈ</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    `;
}

function getAboutPageContent() {
  return `
        <section class="py-20 bg-gradient-to-br from-purple-50 to-pink-50 min-h-screen">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="text-center mb-12">
                        <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">ກ່ຽວກັບພວກເຮົາ</h2>
                        <p class="text-xl text-gray-600">ໂຮງຮຽນປະຖົມສານຝັນ ມຸ່ງໝັ້ນສ້າງອະນາຄົດທີ່ສົດໃສ</p>
                    </div>

                    <div class="bg-white rounded-3xl shadow-xl p-8 md:p-12">
                        <div class="prose prose-lg max-w-none">
                            <div class="text-center mb-8">
                                <div class="w-32 h-32 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-school text-white text-4xl"></i>
                                </div>
                                <h3 class="text-3xl font-bold text-gray-800 mb-4">ໂຮງຮຽນປະຖົມສານຝັນ</h3>
                            </div>

                            <div class="grid md:grid-cols-2 gap-8 mb-8">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 mb-4">ວິໄສທັດ</h4>
                                    <p class="text-gray-600">ມຸ່ງໝັ້ນເປັນສະຖາບັນການສຶກສາຊັ້ນນຳທີ່ພັດທະນານັກຮຽນໃຫ້ມີຄວາມຮູ້, ຄຸນນະທຳ ແລະ ທັກສະໃນສັດຕະວັດທີ 21.</p>
                                </div>
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-800 mb-4">ພາລະກິດ</h4>
                                    <p class="text-gray-600">ຈັດການສຶກສາທີ່ມີຄຸນນະພາບ, ສົ່ງເສີມການຮຽນຮູ້ແບບອົງຄ໌ລວມ ແລະ ພັດທະນາສັກກະຍະພາບຂອງນັກຮຽນທຸກຄົນ.</p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-purple-100 to-pink-100 p-8 rounded-2xl">
                                <h4 class="text-2xl font-bold text-gray-800 mb-4">ຕິດຕໍ່ພວກເຮົາ</h4>
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <p class="flex items-center mb-3"><i class="fas fa-map-marker-alt text-purple-600 mr-3"></i> 123 ຖະໜົນການສຶກສາ, ບ້ານຄວາມຮູ້, ເມືອງການຮຽນ, ແຂວງປັນຍາ 12345</p>
                                        <p class="flex items-center mb-3"><i class="fas fa-phone text-purple-600 mr-3"></i> 02-123-4567</p>
                                    </div>
                                    <div>
                                        <p class="flex items-center mb-3"><i class="fas fa-envelope text-purple-600 mr-3"></i> info@sanfanprimary.com.la</p>
                                        <p class="flex items-center"><i class="fas fa-globe text-purple-600 mr-3"></i> www.sanfanprimary.ac.th</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    `;
}
                                /* 
                                <div class="grid md:grid-cols-3 gap-6 mt-8">
                                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-2xl text-center">
                                        <div class="text-3xl font-bold mb-2" id="avgScore">0</div>
                                        <div class="text-blue-100">ຄະແນນສະເລ່ຍ</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-2xl text-center">
                                        <div class="text-3xl font-bold mb-2" id="highestScore">0</div>
                                        <div class="text-green-100">ຄະແນນສູງສຸດ</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-2xl text-center">
                                        <div class="text-3xl font-bold mb-2" id="totalSubjects">0</div>
                                        <div class="text-purple-100">ຈຳນວນວິຊາ</div>
                                    </div>
                                </div>  */
