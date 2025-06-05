// Registration Analytics JavaScript
class RegistrationAnalytics {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initCharts());
        } else {
            this.initCharts();
        }
    }

    initCharts() {
        this.initMonthlyChart();
        this.initStatusChart();
        this.initTypeChart();
    }

    initMonthlyChart() {
        const ctx = document.getElementById('monthlyChart');
        if (!ctx) return;

        const monthlyData = window.analyticsData?.monthly || [];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Prepare data arrays
        const labels = [];
        const totalData = [];
        const approvedData = [];
        const pendingData = [];
        const rejectedData = [];

        // Fill with zeros for all months
        for (let i = 1; i <= 12; i++) {
            labels.push(months[i - 1]);
            totalData.push(0);
            approvedData.push(0);
            pendingData.push(0);
            rejectedData.push(0);
        }

        // Fill actual data
        monthlyData.forEach(item => {
            const monthIndex = item.month - 1;
            totalData[monthIndex] = parseInt(item.count) || 0;
            approvedData[monthIndex] = parseInt(item.approved) || 0;
            pendingData[monthIndex] = parseInt(item.pending) || 0;
            rejectedData[monthIndex] = parseInt(item.rejected) || 0;
        });

        this.charts.monthly = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Registrations',
                        data: totalData,
                        borderColor: 'rgb(78, 115, 223)',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Approved',
                        data: approvedData,
                        borderColor: 'rgb(28, 200, 138)',
                        backgroundColor: 'rgba(28, 200, 138, 0.1)',
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'Pending',
                        data: pendingData,
                        borderColor: 'rgb(246, 194, 62)',
                        backgroundColor: 'rgba(246, 194, 62, 0.1)',
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'Rejected',
                        data: rejectedData,
                        borderColor: 'rgb(231, 74, 59)',
                        backgroundColor: 'rgba(231, 74, 59, 0.1)',
                        fill: false,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                interaction: {
                    intersect: false,
                },
                hover: {
                    animationDuration: 200
                }
            }
        });
    }

    initStatusChart() {
        const ctx = document.getElementById('statusChart');
        if (!ctx) return;

        const statusData = window.analyticsData?.status || {};
        const data = [
            statusData.approved || 0,
            statusData.pending || 0,
            statusData.rejected || 0
        ];

        this.charts.status = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgb(28, 200, 138)',
                        'rgb(246, 194, 62)',
                        'rgb(231, 74, 59)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '50%'
            }
        });
    }

    initTypeChart() {
        const ctx = document.getElementById('typeChart');
        if (!ctx) return;

        const typeData = window.analyticsData?.types || [];
        const labels = typeData.map(item => item.registration_type || 'Unknown');
        const data = typeData.map(item => parseInt(item.count) || 0);
        
        // Generate colors
        const colors = this.generateColors(labels.length);

        this.charts.type = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Registrations',
                    data: data,
                    backgroundColor: colors.background,
                    borderColor: colors.border,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${context.parsed.y} (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                }
            }
        });
    }

    generateColors(count) {
        const baseColors = [
            'rgb(78, 115, 223)',
            'rgb(28, 200, 138)',
            'rgb(246, 194, 62)',
            'rgb(231, 74, 59)',
            'rgb(54, 185, 204)',
            'rgb(133, 135, 150)'
        ];

        const background = [];
        const border = [];

        for (let i = 0; i < count; i++) {
            const color = baseColors[i % baseColors.length];
            background.push(color.replace('rgb', 'rgba').replace(')', ', 0.8)'));
            border.push(color);
        }

        return { background, border };
    }

    updateMonthlyChart() {
        const year = document.getElementById('yearSelect')?.value || new Date().getFullYear();
        
        // Make AJAX request to get data for selected year
        fetch(`admin_registration_management.php?action=get_monthly_data&year=${year}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && this.charts.monthly) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                   'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    
                    // Prepare data arrays
                    const totalData = new Array(12).fill(0);
                    const approvedData = new Array(12).fill(0);
                    const pendingData = new Array(12).fill(0);
                    const rejectedData = new Array(12).fill(0);

                    // Fill actual data
                    data.monthly.forEach(item => {
                        const monthIndex = item.month - 1;
                        totalData[monthIndex] = parseInt(item.count) || 0;
                        approvedData[monthIndex] = parseInt(item.approved) || 0;
                        pendingData[monthIndex] = parseInt(item.pending) || 0;
                        rejectedData[monthIndex] = parseInt(item.rejected) || 0;
                    });

                    // Update chart data
                    this.charts.monthly.data.datasets[0].data = totalData;
                    this.charts.monthly.data.datasets[1].data = approvedData;
                    this.charts.monthly.data.datasets[2].data = pendingData;
                    this.charts.monthly.data.datasets[3].data = rejectedData;
                    this.charts.monthly.update();
                }
            })
            .catch(error => {
                console.error('Error updating monthly chart:', error);
            });
    }

    exportAnalytics(format) {
        const formData = new FormData();
        formData.append('action', 'export_analytics');
        formData.append('format', format);

        fetch('admin_registration_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                return response.blob();
            }
            throw new Error('Export failed');
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `registration_analytics_${new Date().toISOString().split('T')[0]}.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Export error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: 'Failed to export analytics data. Please try again.'
            });
        });
    }

    refreshData() {
        // Refresh all charts with new data
        fetch('admin_registration_management.php?action=get_analytics_data')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.analyticsData = data.analytics;
                    this.initCharts();
                }
            })
            .catch(error => {
                console.error('Error refreshing data:', error);
            });
    }

    destroy() {
        // Clean up charts
        Object.values(this.charts).forEach(chart => {
            if (chart) {
                chart.destroy();
            }
        });
        this.charts = {};
    }
}

// Global functions for onclick handlers
function updateMonthlyChart() {
    if (window.registrationAnalytics) {
        window.registrationAnalytics.updateMonthlyChart();
    }
}

function exportAnalytics(format) {
    if (window.registrationAnalytics) {
        window.registrationAnalytics.exportAnalytics(format);
    }
}

// Initialize analytics when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.registrationAnalytics = new RegistrationAnalytics();
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (window.registrationAnalytics) {
        window.registrationAnalytics.destroy();
    }
});