// API functions for server communication

class API {
    // กำหนด base URL สำหรับ API
    static BASE_URL = '../../api'; // เปลี่ยนจาก '../api' เป็น './api'
    
    static async loadClasses() {
        try {
            // ลองหลาย path ที่เป็นไปได้
            const possiblePaths = [
                `${this.BASE_URL}/get_classes.php`,
                './get_classes.php',
                '../get_classes.php',
                './api/get_classes.php'
            ];
            
            let response, data;
            let lastError = null;
            
            for (const path of possiblePaths) {
                try {
                    console.log(`Trying API path: ${path}`);
                    response = await fetch(path);
                    
                    if (response.ok) {
                        const responseText = await response.text();
                        console.log('Raw response:', responseText);
                        
                        // ตรวจสอบว่าเป็น HTML error page หรือไม่
                        if (responseText.includes('<!DOCTYPE') || responseText.includes('<html>')) {
                            throw new Error('Received HTML instead of JSON - API endpoint not found');
                        }
                        
                        data = JSON.parse(responseText);
                        console.log(`Success with path: ${path}`);
                        return data;
                    }
                } catch (error) {
                    lastError = error;
                    console.log(`Failed with path ${path}:`, error.message);
                    continue;
                }
            }
            
            // ถ้าไม่มี path ไหนทำงาน ให้ใช้ mock data
            console.warn('All API paths failed, using mock data');
            return this.getMockClassesData();
            
        } catch (error) {
            console.error('Error loading classes:', error);
            return this.getMockClassesData();
        }
    }

    static async searchStudent(searchValue) {
        try {
            const possiblePaths = [
                `${this.BASE_URL}/search_student.php`,
                './search_student.php',
                '../search_student.php',
                './api/search_student.php'
            ];
            
            for (const path of possiblePaths) {
                try {
                    console.log(`Trying search API path: ${path}`);
                    const response = await fetch(path, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            searchValue: searchValue
                        })
                    });
                    
                    if (response.ok) {
                        const responseText = await response.text();
                        
                        if (responseText.includes('<!DOCTYPE') || responseText.includes('<html>')) {
                            throw new Error('Received HTML instead of JSON');
                        }
                        
                        const data = JSON.parse(responseText);
                        console.log(`Search success with path: ${path}`);
                        return data;
                    }
                } catch (error) {
                    console.log(`Search failed with path ${path}:`, error.message);
                    continue;
                }
            }
            
            // Mock search result
            console.warn('Search API failed, using mock data');
            return this.getMockSearchData(searchValue);
            
        } catch (error) {
            console.error('Error searching student:', error);
            return { success: false, error: error.message };
        }
    }

    static async searchStudentById(studentId) {
        try {
            const possiblePaths = [
                `${this.BASE_URL}/search_student.php`,
                './search_student.php',
                '../search_student.php',
                './api/search_student.php'
            ];
            
            for (const path of possiblePaths) {
                try {
                    const response = await fetch(path, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            searchType: 'id',
                            searchValue: studentId
                        })
                    });
                    
                    if (response.ok) {
                        const responseText = await response.text();
                        
                        if (responseText.includes('<!DOCTYPE') || responseText.includes('<html>')) {
                            throw new Error('Received HTML instead of JSON');
                        }
                        
                        const data = JSON.parse(responseText);
                        return data;
                    }
                } catch (error) {
                    continue;
                }
            }
            
            return this.getMockSearchData(studentId);
            
        } catch (error) {
            console.error('Error searching student by ID:', error);
            return { success: false, error: error.message };
        }
    }

    static async registerStudent(formData) {
        try {
            const possiblePaths = [
                `${this.BASE_URL}/register_student.php`,
                './register_student.php',
                '../register_student.php',
                './api/register_student.php'
            ];
            
            console.log('Submitting registration to API...');
            
            for (const path of possiblePaths) {
                try {
                    console.log(`Trying registration API path: ${path}`);
                    const response = await fetch(path, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        const responseText = await response.text();
                        console.log('Registration response:', responseText);
                        
                        if (responseText.includes('<!DOCTYPE') || responseText.includes('<html>')) {
                            throw new Error('Received HTML instead of JSON');
                        }
                        
                        if (!responseText || responseText.trim() === '') {
                            throw new Error('Server returned empty response');
                        }
                        
                        const data = JSON.parse(responseText);
                        console.log(`Registration success with path: ${path}`);
                        return data;
                    }
                } catch (error) {
                    console.log(`Registration failed with path ${path}:`, error.message);
                    continue;
                }
            }
            
            // Mock successful registration
            console.warn('Registration API failed, returning mock success');
            return {
                success: true,
                message: 'การลงทะเบียนสำเร็จ! (Demo Mode - ไม่ได้บันทึกจริง)'
            };
            
        } catch (error) {
            console.error('Error registering student:', error);
            return { success: false, error: error.message };
        }
    }

    static async getScores(studentId) {
        try {
            const possiblePaths = [
                `${this.BASE_URL}/get_scores.php`,
                './get_scores.php',
                '../get_scores.php',
                './api/get_scores.php'
            ];
            
            for (const path of possiblePaths) {
                try {
                    const response = await fetch(path, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            student_id: studentId
                        })
                    });
                    
                    if (response.ok) {
                        const responseText = await response.text();
                        
                        if (responseText.includes('<!DOCTYPE') || responseText.includes('<html>')) {
                            throw new Error('Received HTML instead of JSON');
                        }
                        
                        const data = JSON.parse(responseText);
                        return data;
                    }
                } catch (error) {
                    continue;
                }
            }
            
            // Mock scores data
            return this.getMockScoresData(studentId);
            
        } catch (error) {
            console.error('Error getting scores:', error);
            return { success: false, error: error.message };
        }
    }

    // Mock data functions สำหรับการทดสอบ
    static getMockClassesData() {
        return {
            success: true,
            levels: [
                {
                    name: "ປະຖົມ 1",
                    classes: [
                        { id: "p1_a", name: "ກ" },
                        { id: "p1_b", name: "ຂ" },
                        { id: "p1_c", name: "ຄ" }
                    ]
                },
                {
                    name: "ປະຖົມ 2",
                    classes: [
                        { id: "p2_a", name: "ກ" },
                        { id: "p2_b", name: "ຂ" },
                        { id: "p2_c", name: "ຄ" }
                    ]
                },
                {
                    name: "ປະຖົມ 3",
                    classes: [
                        { id: "p3_a", name: "ກ" },
                        { id: "p3_b", name: "ຂ" },
                        { id: "p3_c", name: "ຄ" }
                    ]
                },
                {
                    name: "ປະຖົມ 4",
                    classes: [
                        { id: "p4_a", name: "ກ" },
                        { id: "p4_b", name: "ຂ" },
                        { id: "p4_c", name: "ຄ" }
                    ]
                },
                {
                    name: "ປະຖົມ 5",
                    classes: [
                        { id: "p5_a", name: "ກ" },
                        { id: "p5_b", name: "ຂ" },
                        { id: "p5_c", name: "ຄ" }
                    ]
                }
            ]
        };
    }

    static getMockSearchData(searchValue) {
        // Mock student data for demo
        const mockStudents = [
            {
                id: "2025001",
                full_name: "ບຸນມີ ພັນທະວົງ",
                class_name: "ປະຖົມ 3/ກ",
                age: 9,
                gender: "M",
                full_address: "ບ້ານນາໄຜ່, ເມືອງຈັນທະບູລີ, ແຂວງວຽງຈັນ"
            },
            {
                id: "2025002", 
                full_name: "ນາງນ້ອຍ ສີລາວົງ",
                class_name: "ປະຖົມ 2/ຂ",
                age: 8,
                gender: "F",
                full_address: "ບ້ານດົງປາລານ, ເມືອງສີໂຄດຕະບອງ, ແຂວງວຽງຈັນ"
            }
        ];

        if (searchValue === "2025001" || searchValue.includes("ບຸນມີ")) {
            return {
                success: true,
                students: [mockStudents[0]]
            };
        } else if (searchValue === "2025002" || searchValue.includes("ນາງນ້ອຍ")) {
            return {
                success: true,
                students: [mockStudents[1]]
            };
        } else {
            return {
                success: false,
                message: "ບໍ່ພົບຂໍ້ມູນນັກຮຽນ (Demo Mode)"
            };
        }
    }

    static getMockScoresData(studentId) {
        if (studentId === "2025001") {
            return {
                success: true,
                student: {
                    id: "2025001",
                    full_name: "ບຸນມີ ພັນທະວົງ",
                    class_name: "ປະຖົມ 3/ກ"
                },
                scores: [
                    {
                        subject_name: "ພາສາລາວ",
                        score: 85,
                        month: "ມັງກອນ",
                        term_name: "ເທີມ 1",
                        year_name: "2025"
                    },
                    {
                        subject_name: "ຄະນິດສາດ",
                        score: 92,
                        month: "ມັງກອນ",
                        term_name: "ເທີມ 1",
                        year_name: "2025"
                    },
                    {
                        subject_name: "ວິທະຍາສາດ",
                        score: 78,
                        month: "ມັງກອນ",
                        term_name: "ເທີມ 1",
                        year_name: "2025"
                    },
                    {
                        subject_name: "ສັງຄົມສຶກສາ",
                        score: 88,
                        month: "ມັງກອນ",
                        term_name: "ເທີມ 1",
                        year_name: "2025"
                    }
                ],
                statistics: {
                    average_score: "85.8",
                    highest_score: "92",
                    total_subjects: "4"
                }
            };
        } else if (studentId === "2025002") {
            return {
                success: true,
                student: {
                    id: "2025002",
                    full_name: "ນາງນ້ອຍ ສີລາວົງ",
                    class_name: "ປະຖົມ 2/ຂ"
                },
                scores: [
                    {
                        subject_name: "ພາສາລາວ",
                        score: 90,
                        month: "ມັງກອນ",
                        term_name: "ເທີມ 1",
                        year_name: "2025"
                    },
                    {
                        subject_name: "ຄະນິດສາດ",
                        score: 87,
                        month: "ມັງກອນ",
                        term_name: "ເທີມ 1",
                        year_name: "2025"
                    },
                    {
                        subject_name: "ວິທະຍາສາດ",
                        score: 82,
                        month: "ມັງກອນ",
                        term_name: "ເທີມ 1",
                        year_name: "2025"
                    }
                ],
                statistics: {
                    average_score: "86.3",
                    highest_score: "90",
                    total_subjects: "3"
                }
            };
        } else {
            return {
                success: false,
                message: "ບໍ່ພົບຂໍ້ມູນຄະແນນ (Demo Mode)"
            };
        }
    }
}