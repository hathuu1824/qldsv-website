window.chartInstances = {};

/**
 * Khởi tạo bộ 3 biểu đồ Dashboard
 * @param {Object} data - Đối tượng chứa: labels, credits, semCredits, gpaHistory, cpaHistory, totalCredits, totalGoal
 */
function initDashboardCharts(data) {
    
    // --- 1. PLUGIN: Vẽ chữ ở trung tâm Doughnut ---
    const centerTextPlugin = {
        id: 'centerText',
        afterDraw: (chart) => {
            if (chart.config.type !== 'doughnut') return;
            const { ctx, chartArea: { top, bottom, left, right } } = chart;
            ctx.save();
            
            const xCenter = (left + right) / 2;
            const yCenter = (top + bottom) / 2;

            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            // Nhãn phụ "TÍN CHỈ"
            ctx.font = 'bold 11px "Segoe UI"';
            ctx.fillStyle = '#95a5a6';
            ctx.fillText('TÍN CHỈ', xCenter, yCenter - 12);

            // Con số tổng tín chỉ đạt được
            ctx.font = 'bold 24px "Segoe UI"';
            ctx.fillStyle = '#2c3e50';
            ctx.fillText(data.totalCredits, xCenter, yCenter + 12);
            
            ctx.restore();
        }
    };

    // --- 2. BIỂU ĐỒ DOUGHNUT 2 LỚP (GPA & CPA) ---
    const ctxOverview = document.getElementById('overviewChart');
    if (ctxOverview) {
        if (window.chartInstances.overview) window.chartInstances.overview.destroy();
        window.chartInstances.overview = new Chart(ctxOverview, {
            type: 'doughnut',
            plugins: [centerTextPlugin],
            data: {
                labels: ['GPA', 'CPA'],
                datasets: [
                    {
                        label: 'GPA',
                        data: [data.currentGpa, 4 - data.currentGpa],
                        backgroundColor: ['#3498db', '#f1f2f6'],
                        borderWidth: 0,
                        weight: 2
                    },
                    {
                        label: 'CPA',
                        data: [data.cpa, 4 - data.cpa],
                        backgroundColor: ['#2ecc71', '#f1f2f6'],
                        borderWidth: 0,
                        weight: 1.5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%', 
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 }, padding: 10 }
                    },
                    tooltip: {
                        callbacks: {
                            label: (item) => item.dataIndex === 0 ? ` ${item.dataset.label}: ${item.raw.toFixed(2)}` : null
                        }
                    }
                }
            }
        });
    }

    // --- 3. BIỂU ĐỒ CỘT (SO SÁNH TÍN CHỈ KỲ & TÍCH LŨY) ---
    const ctxCredits = document.getElementById('creditsChart');
    if (ctxCredits) {
        if (window.chartInstances.credits) window.chartInstances.credits.destroy();
        window.chartInstances.credits = new Chart(ctxCredits, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    { 
                        label: 'TC tích lũy HK', 
                        data: data.semCredits,
                        backgroundColor: '#f39c12', 
                        borderRadius: 4,
                        barPercentage: 0.7,
                        categoryPercentage: 0.6
                    },
                    { 
                        label: 'Tổng TC tích lũy', 
                        data: data.credits,
                        backgroundColor: '#3498db', 
                        borderRadius: 4,
                        barPercentage: 0.7,
                        categoryPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        display: true, 
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 } }
                    } 
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: data.totalGoal > 0 ? data.totalGoal : 120, 
                        ticks: { stepSize: 20, font: { size: 10 } }
                    },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    // --- 4. BIỂU ĐỒ ĐƯỜNG (TIẾN ĐỘ GPA & CPA) ---
    const ctxGpaTrend = document.getElementById('gpaLineChart');
    if (ctxGpaTrend) {
        if (window.chartInstances.gpaTrend) window.chartInstances.gpaTrend.destroy();
        window.chartInstances.gpaTrend = new Chart(ctxGpaTrend, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    { 
                        label: 'TB học kỳ', 
                        data: data.gpaHistory, 
                        borderColor: '#e67e22', 
                        backgroundColor: '#e67e22',
                        tension: 0.4, 
                        fill: false,
                        pointRadius: 3
                    },
                    { 
                        label: 'TB tích lũy', 
                        data: data.cpaHistory, 
                        borderColor: '#2ecc71', 
                        backgroundColor: '#2ecc71',
                        tension: 0.4, 
                        fill: false,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        display: true, 
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 } }
                    } 
                },
                scales: {
                    y: { min: 0, max: 4, ticks: { stepSize: 1, font: { size: 10 } } },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    }
}