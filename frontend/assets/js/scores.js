// Scores functionality

class ScoresManager {
    static async searchScores() {
        const scoreStudentIdInput = document.getElementById('scoreStudentId');
        const scoreResultsDiv = document.getElementById('scoreResults');
        const noScoreResultsDiv = document.getElementById('noScoreResults');
        const scoreStudentInfoDiv = document.getElementById('scoreStudentInfo');
        const scoreTableDiv = document.getElementById('scoreTable');

        if (!scoreStudentIdInput || !scoreResultsDiv || !noScoreResultsDiv || !scoreStudentInfoDiv || !scoreTableDiv) return;

        const studentId = scoreStudentIdInput.value.trim();
        
        if (!studentId) {
            alert('ກະລຸນາປ້ອນລະຫັດນັກຮຽນ');
            return;
        }

        try {
            const data = await API.getScores(studentId);
            
            if (data.success) {
                this.displayScoreResults(data);
            } else {
                scoreResultsDiv.classList.add('hidden');
                noScoreResultsDiv.classList.remove('hidden');
                noScoreResultsDiv.style.animation = 'fadeInUp 0.8s ease-out';
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert('ເກີດຂໍ້ຜິດພາດໃນການດຶງຂໍ້ມູນຄະແນນ');
        }
    }

    static displayScoreResults(data) {
        const student = data.student;
        const scores = data.scores;
        const stats = data.statistics;
        
        console.log('Received statistics:', stats); // Debug log
        
        const scoreStudentInfoDiv = document.getElementById('scoreStudentInfo');
        const scoreTableDiv = document.getElementById('scoreTable');
        const scoreResultsDiv = document.getElementById('scoreResults');
        const noScoreResultsDiv = document.getElementById('noScoreResults');

        // Enhanced student info display
        scoreStudentInfoDiv.innerHTML = `
            <div class="flex items-center space-x-6">
                <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-graduate text-white text-3xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">${student.full_name}</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white bg-opacity-50 p-3 rounded-lg">
                            <span class="text-gray-500 text-sm">ລະຫັດນັກຮຽນ</span>
                            <p class="text-gray-800 font-semibold">${student.id}</p>
                        </div>
                        <div class="bg-white bg-opacity-50 p-3 rounded-lg">
                            <span class="text-gray-500 text-sm">ຊັ້ນຮຽນ</span>
                            <p class="text-gray-800 font-semibold">${student.class_name || 'ບໍ່ມີຂໍ້ມູນ'}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (scores.length > 0) {
            // Group scores by term and year
            const groupedScores = scores.reduce((acc, score) => {
                const key = `${score.term_name}/${score.year_name}`;
                if (!acc[key]) {
                    acc[key] = [];
                }
                acc[key].push(score);
                return acc;
            }, {});

            let tableHTML = '';
            
            // Create a table for each term/year group
            Object.entries(groupedScores).forEach(([termYear, termScores]) => {
                tableHTML += `
                    <div class="mb-8 bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-4">
                            <h4 class="text-lg font-semibold text-white flex items-center">
                                <i class="fas fa-calendar-alt mr-2"></i>${termYear}
                            </h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">ວິຊາ</th>
                                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">ຄະແນນ</th>
                                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">ເດືອນ</th>
                                       
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                `;

                termScores.forEach((item, index) => {
                    const status = item.score >= 50 ? 'ຜ່ານ' : 'ບໍ່ຜ່ານ';
                    const statusColor = item.score >= 50 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    const scoreColor = item.score >= 50 ? 'text-green-600' : 'text-red-600';

                    tableHTML += `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-book text-blue-500"></i>
                                    </div>
                                    <span class="font-medium text-gray-900">${item.subject_name}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-2xl font-bold ${scoreColor}">${item.score}</span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-600">${item.month}</td>
                           
                        </tr>
                    `;
                });

                tableHTML += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            });

            scoreTableDiv.innerHTML = tableHTML;
            
            // Animate statistics
            setTimeout(() => {
                const avgScore = parseFloat(stats.average_score) || 0;
                const highestScore = parseFloat(stats.highest_score) || 0;
                const totalSubjects = parseInt(stats.total_subjects) || 0;
                
                console.log('Animating counters with:', {avgScore, highestScore, totalSubjects});
                
                animateCounterWithDecimal('avgScore', avgScore, 1000);
                animateCounter('highestScore', highestScore, 1000);
                animateCounter('totalSubjects', totalSubjects, 1000);
            }, 300);
            
        } else {
            scoreTableDiv.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-chart-line text-6xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-600">ບໍ່ມີຂໍ້ມູນຄະແນນ</h3>
                    <p class="text-gray-500">ນັກຮຽນຄົນນີ້ຍັງບໍ່ມີຂໍ້ມູນຄະແນນໃນລະບົບ</p>
                </div>
            `;
            
            setTimeout(() => {
                animateCounter('avgScore', 0, 500);
                animateCounter('highestScore', 0, 500);
                animateCounter('totalSubjects', 0, 500);
            }, 300);
        }

        scoreResultsDiv.classList.remove('hidden');
        scoreResultsDiv.style.animation = 'fadeInUp 0.8s ease-out';
        noScoreResultsDiv.classList.add('hidden');
    }
}

// Global function for compatibility
function searchScores() {
    ScoresManager.searchScores();
}

{/* <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">ສະຖານະ</th> */}

{/* <td class="px-6 py-4 text-center">
<span class="px-3 py-1 rounded-full text-sm font-medium ${statusColor}">
    ${status}
</span>
</td> */}