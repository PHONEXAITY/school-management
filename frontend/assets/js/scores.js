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

        scoreStudentInfoDiv.innerHTML = `
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
        `;

        if (scores.length > 0) {
            let tableHTML = `
                <table class="w-full bg-white rounded-2xl overflow-hidden shadow-lg">
                    <thead class="bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">ວິຊາ</th>
                            <th class="px-6 py-4 text-center">ຄະແນນ</th>
                            <th class="px-6 py-4 text-center">ເດືອນ</th>
                            <th class="px-6 py-4 text-center">ເທີມ/ປີ</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            scores.forEach((item, index) => {
                const rowClass = index % 2 === 0 ? 'bg-gray-50' : 'bg-white';

                tableHTML += `
                    <tr class="${rowClass}">
                        <td class="px-6 py-4 font-semibold">${item.subject_name}</td>
                        <td class="px-6 py-4 text-center text-2xl font-bold text-blue-600">${item.score}</td>
                        <td class="px-6 py-4 text-center">${item.month}</td>
                        <td class="px-6 py-4 text-center">${item.term_name}/${item.year_name}</td>
                    </tr>
                `;
            });

            tableHTML += `
                    </tbody>
                </table>
            `;

            scoreTableDiv.innerHTML = tableHTML;
            
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
                <div class="text-center py-8">
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