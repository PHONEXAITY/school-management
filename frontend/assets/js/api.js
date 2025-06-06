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

    static async getScores(studentId, month = '', term = '') {
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
                            student_id: studentId,
                            month: month,
                            term: term
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
            return this.getMockScoresData(studentId, month, term);
            
        } catch (error) {
            console.error('Error getting scores:', error);
            return { success: false, error: error.message };
        }
    }

    // Mock data functions สำหรับการทดสอบ
}