// Registration functionality

let selectedStudent = null;
let availableClasses = [];

class RegistrationManager {
    static async loadClasses() {
        const result = await API.loadClasses();
        if (result.success) {
            availableClasses = result.levels;
            this.updateClassOptions();
            console.log('Classes loaded successfully:', availableClasses);
        } else {
            console.error('API Error:', result);
        }
    }

    static updateClassOptions() {
        const classSelect = document.getElementById('studentClass');
        if (!classSelect) return;
        
        classSelect.innerHTML = '<option value="">ເລືອກຊັ້ນຮຽນ</option>';
        
        availableClasses.forEach(level => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = level.name;
            
            level.classes.forEach(cls => {
                const option = document.createElement('option');
                option.value = cls.id;
                option.textContent = `${level.name}/${cls.name}`;
                optgroup.appendChild(option);
            });
            
            classSelect.appendChild(optgroup);
        });
    }

    static toggleRegistrationType(type) {
        console.log(`Switching to registration type: ${type}`);
        
        const existingStudentSection = document.getElementById('existingStudentSection');
        const newStudentSection = document.getElementById('newStudentSection');
        const existingBtn = document.getElementById('existingStudentBtn');
        const newBtn = document.getElementById('newStudentBtn');
        
        if (type === 'existing') {
            existingStudentSection.classList.remove('hidden');
            newStudentSection.classList.add('hidden');
            existingBtn.classList.add('bg-blue-600', 'text-white');
            existingBtn.classList.remove('bg-gray-200', 'text-gray-700');
            newBtn.classList.remove('bg-blue-600', 'text-white');
            newBtn.classList.add('bg-gray-200', 'text-gray-700');
            
            const newStudentInputs = newStudentSection.querySelectorAll('input, select');
            newStudentInputs.forEach(input => {
                input.value = '';
            });
            
        } else {
            existingStudentSection.classList.add('hidden');
            newStudentSection.classList.remove('hidden');
            newBtn.classList.add('bg-blue-600', 'text-white');
            newBtn.classList.remove('bg-gray-200', 'text-gray-700');
            existingBtn.classList.remove('bg-blue-600', 'text-white');
            existingBtn.classList.add('bg-gray-200', 'text-gray-700');
        }
        
        selectedStudent = null;
        this.clearStudentInfo();
        
        console.log(`Registration type switched to: ${type}`);
        console.log('Section visibility after switch:', {
            existingHidden: existingStudentSection.classList.contains('hidden'),
            newHidden: newStudentSection.classList.contains('hidden')
        });
    }

    static async searchStudent() {
        const studentIdInput = document.getElementById('studentId');
        const studentNameInput = document.getElementById('studentName');
        
        const searchValue = studentIdInput.value.trim() || studentNameInput.value.trim();
        
        if (!searchValue) {
            alert('ກະລຸນາປ້ອນລະຫັດນັກຮຽນ ຫຼື ຊື່ນັກຮຽນ');
            return;
        }
        
        const result = await API.searchStudent(searchValue);
        
        if (result.success && result.students.length > 0) {
            if (result.students.length === 1) {
                this.selectStudent(result.students[0]);
            } else {
                this.showStudentSelection(result.students);
            }
        } else {
            alert(result.message || 'ບໍ່ພົບຂໍ້ມູນນັກຮຽນ');
            this.clearStudentInfo();
        }
    }

    static showStudentSelection(students) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        
        modal.innerHTML = `
            <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
                <h3 class="text-xl font-bold mb-4">ເລືອກນັກຮຽນ</h3>
                <div class="space-y-3">
                    ${students.map(student => `
                        <div class="border rounded-lg p-4 hover:bg-blue-50 cursor-pointer" onclick="RegistrationManager.selectStudentFromModal('${student.id}', this)">
                            <div class="font-semibold">${student.full_name}</div>
                            <div class="text-sm text-gray-600">ລະຫັດ: ${student.id} | ຊັ້ນ: ${student.class_name || 'ບໍ່ມີຂໍ້ມູນ'}</div>
                            <div class="text-sm text-gray-600">ອາຍຸ: ${student.age} ປີ | ${student.full_address}</div>
                        </div>
                    `).join('')}
                </div>
                <div class="mt-6 text-center">
                    <button onclick="RegistrationManager.closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg">ຍົກເລີກ</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        window.currentModal = modal;
    }

    static async selectStudentFromModal(studentId, element) {
        const result = await API.searchStudentById(studentId);
        
        if (result.success && result.students.length > 0) {
            this.selectStudent(result.students[0]);
            this.closeModal();
        }
    }

    static closeModal() {
        if (window.currentModal) {
            document.body.removeChild(window.currentModal);
            window.currentModal = null;
        }
    }

    static selectStudent(student) {
        selectedStudent = student;
        this.displayStudentInfo(student);
    }

    static displayStudentInfo(student) {
        const studentInfoDiv = document.getElementById('studentInfo');
        const studentDetailsDiv = document.getElementById('studentDetails');
        
        if (!studentInfoDiv || !studentDetailsDiv) return;
        
        studentDetailsDiv.innerHTML = `
            <div>
                <span class="font-semibold">ລະຫັດນັກຮຽນ:</span>
                <span>${student.id}</span>
            </div>
            <div>
                <span class="font-semibold">ຊື່-ນາມສະກຸນ:</span>
                <span>${student.full_name}</span>
            </div>
            <div>
                <span class="font-semibold">ຊັ້ນຮຽນ:</span>
                <span>${student.class_name || 'ບໍ່ມີຂໍ້ມູນ'}</span>
            </div>
            <div>
                <span class="font-semibold">ອາຍຸ:</span>
                <span>${student.age} ປີ</span>
            </div>
            <div>
                <span class="font-semibold">ເພດ:</span>
                <span>${student.gender === 'M' ? 'ຊາຍ' : (student.gender === 'F' ? 'ຍິງ' : 'ອື່ນໆ')}</span>
            </div>
            <div>
                <span class="font-semibold">ທີ່ຢູ່:</span>
                <span>${student.full_address}</span>
            </div>
        `;
        
        studentInfoDiv.classList.remove('hidden');
        studentInfoDiv.style.animation = 'slideIn 0.5s ease-out';
    }

    static clearStudentInfo() {
        const studentInfoDiv = document.getElementById('studentInfo');
        if (studentInfoDiv) {
            studentInfoDiv.classList.add('hidden');
        }
    }

    static async handleRegistrationSubmit(event) {
        event.preventDefault();
        
        console.log('=== Starting registration submission ===');
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Validate academic year selection
        const academicYearId = formData.get('academic_year_id');
        if (!academicYearId || academicYearId.trim() === '') {
            alert('ກະລຸນາເລືອກປີການສຶກສາກ່ອນ');
            return;
        }
        
        const existingStudentSection = document.getElementById('existingStudentSection');
        const newStudentSection = document.getElementById('newStudentSection');
        
        const isExistingStudentVisible = existingStudentSection && !existingStudentSection.classList.contains('hidden');
        const isNewStudentVisible = newStudentSection && !newStudentSection.classList.contains('hidden');
        
        console.log('Section visibility:', {
            existingVisible: isExistingStudentVisible,
            newVisible: isNewStudentVisible
        });
        
        let registrationType;
        if (isExistingStudentVisible && !isNewStudentVisible) {
            registrationType = 'existing';
        } else if (isNewStudentVisible && !isExistingStudentVisible) {
            registrationType = 'new';
        } else {
            const existingBtn = document.getElementById('existingStudentBtn');
            const newBtn = document.getElementById('newStudentBtn');
            
            if (existingBtn && existingBtn.classList.contains('bg-blue-600')) {
                registrationType = 'existing';
            } else if (newBtn && newBtn.classList.contains('bg-blue-600')) {
                registrationType = 'new';
            } else {
                registrationType = 'existing'; // default fallback
            }
        }
        
        console.log(`Final registration type: ${registrationType}`);
        console.log(`Academic Year ID: ${academicYearId}`);
        
        formData.append('registration_type', registrationType);
        
        if (registrationType === 'existing') {
            if (!selectedStudent) {
                alert('ກະລຸນາຄົ້ນຫາແລະເລືອກນັກຮຽນກ່ອນ');
                return;
            }
            formData.append('student_id', selectedStudent.id);
            console.log(`Added student_id for existing student: ${selectedStudent.id}`);
            
            const newStudentFields = ['student_fname', 'student_lname', 'student_gender', 'student_birth_date', 
                                    'student_village', 'student_district', 'student_province', 'class_id'];
            newStudentFields.forEach(field => {
                if (formData.has(field)) {
                    formData.delete(field);
                    console.log(`Removed new student field: ${field}`);
                }
            });
            
        } else {
            console.log('New student registration - validating required fields');
            const requiredNewStudentFields = ['student_fname', 'student_lname', 'student_gender', 'student_birth_date', 
                                            'student_village', 'student_district', 'student_province', 'class_id'];
            
            const missingFields = [];
            requiredNewStudentFields.forEach(field => {
                const value = formData.get(field);
                if (!value || value.trim() === '') {
                    missingFields.push(field);
                }
            });
            
            if (missingFields.length > 0) {
                console.log('Missing new student fields:', missingFields);
                alert('ກະລຸນາປ້ອນຂໍ້ມູນນັກຮຽນໃໝ່ໃຫ້ຄົບຖ້ວນ: ' + missingFields.join(', '));
                return;
            }
        }
        
        const paymentProof = formData.get('payment_slip');
        if (!paymentProof || paymentProof.size === 0) {
            alert('ກະລຸນາອັບໂຫລດຫຼັກຖານການໂອນເງີນ');
            return;
        }
        
        console.log('=== Final Form Data Being Sent ===');
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(`${key}: FILE - ${value.name} (${value.size} bytes)`);
            } else {
                console.log(`${key}: ${value}`);
            }
        }
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>ກຳລັງປະມວນຜົນ...';
        submitButton.disabled = true;
        
        try {
            const result = await API.registerStudent(formData);
            
            if (result.success) {
                alert(result.message);
                
                form.reset();
                selectedStudent = null;
                this.clearStudentInfo();
                FileManager.removeFile();
                
                this.toggleRegistrationType('existing');
                
            } else {
                alert('ເກີດຂໍ້ຜິດພາດ: ' + result.error);
                console.error('API Error:', result);
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert('ເກີດຂໍ້ຜິດພາດໃນການສົ່ງໃບສະໝັກ: ' + error.message);
        } finally {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }
    }
}

// File management utilities
class FileManager {
    static handleFileUpload(input) {
        const file = input.files[0];
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const imagePreview = document.getElementById('imagePreview');

        if (file) {
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('ກະລຸນາເລືອກໄຟລ໌ຮູບພາບ (JPG, JPEG, PNG) ເທົ່ານັ້ນ');
                input.value = '';
                return;
            }

            const maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if (file.size > maxSize) {
                alert('ຂະໜາດໄຟລ໌ໃຫຍ່ເກີນໄປ ກະລຸນາເລືອກໄຟລ໌ທີ່ມີຂະໜາດບໍ່ເກີນ 5MB');
                input.value = '';
                return;
            }

            fileName.textContent = file.name;
            filePreview.classList.remove('hidden');

            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    static removeFile() {
        const fileInput = document.getElementById('paymentProof');
        const filePreview = document.getElementById('filePreview');
        const imagePreview = document.getElementById('imagePreview');

        if (fileInput) fileInput.value = '';
        if (filePreview) filePreview.classList.add('hidden');
        if (imagePreview) imagePreview.style.display = 'none';
    }
}

// Global functions for compatibility
function toggleRegistrationType(type) {
    RegistrationManager.toggleRegistrationType(type);
}

function searchStudent() {
    RegistrationManager.searchStudent();
}

function handleFileUpload(input) {
    FileManager.handleFileUpload(input);
}

function removeFile() {
    FileManager.removeFile();
}