/**
 * Registration status check functionality
 */

// Utility function for showing notifications
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    } text-white`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${
                type === 'success' ? 'fa-check-circle' :
                type === 'error' ? 'fa-exclamation-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'
            } mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('opacity-0', 'transition-opacity', 'duration-300');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '';
    
    const parts = dateString.split('-');
    if (parts.length !== 3) return dateString;
    
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

// Format status label
function getStatusLabel(status, type = 'registration') {
    if (type === 'registration') {
        switch (status) {
            case 'approved':
                return 'ອະນຸມັດແລ້ວ';
            case 'rejected':
                return 'ປະຕິເສດ';
            case 'pending':
                return 'ລໍຖ້າການອະນຸມັດ';
            default:
                return status;
        }
    } else if (type === 'payment') {
        switch (status) {
            case 'paid':
                return 'ຈ່າຍແລ້ວ';
            case 'unpaid':
                return 'ຍັງບໍ່ໄດ້ຈ່າຍ';
            case 'partial':
                return 'ຈ່າຍບາງສ່ວນ';
            default:
                return status;
        }
    }
    
    return status;
}

// Handle registration status check
async function checkRegistrationStatus(searchType, searchValue) {
    try {
        const response = await fetch('api/check_registration_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                searchType,
                searchValue
            })
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Error checking registration status:', error);
        throw error;
    }
}

// Export functions for global use
window.schoolApp = window.schoolApp || {};
window.schoolApp.registration = {
    checkStatus: checkRegistrationStatus,
    formatDate,
    getStatusLabel,
    showNotification
};
