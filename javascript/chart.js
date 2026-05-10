window.chartInstances = {};

function initProgressCharts(data) {
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: {
                right: 25 
            }
        },
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 15,
                    font: { size: 12 }
                }
            }
        }
    };
    
    // Biểu đồ cột
    const ctxCredits = document.getElementById('creditsChart');
    if (ctxCredits) {
        if (window.chartInstances.credits) window.chartInstances.credits.destroy();
        window.chartInstances.credits = new Chart(ctxCredits, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Số TC tích lũy HK', data: data.passSem, backgroundColor: '#3498db', borderRadius: 5 },
                    { label: 'Tổng số TC tích lũy', data: data.passTotal, backgroundColor: '#2ecc71', borderRadius: 5 },
                    { label: 'Số TC chưa đạt HK', data: data.failSem, backgroundColor: '#dcf161', borderRadius: 5 },
                    { label: 'Tổng số TC chưa đạt', data: data.failTotal, backgroundColor: '#e74c3c', borderRadius: 5 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } },
                scales: { y: { beginAtZero: true, max: data.totalGoal } },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart',
                    y: { from: (ctx) => ctx.chart.scales.y.getPixelForValue(0) } // Mọc từ đáy
                }
            }
        });
    }

    // Biểu đồ đường
    const ctxGpa = document.getElementById('gpaChart');
    if (ctxGpa) {
        if (window.chartInstances.gpa) window.chartInstances.gpa.destroy();
        window.chartInstances.gpa = new Chart(ctxGpa, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'GPA Học kỳ', data: data.semGpa, borderColor: '#2ecc71', backgroundColor: '#2ecc71', tension: 0.4, fill: false },
                    { label: 'GPA Tổng kết', data: data.cumGpa, borderColor: '#e67e22', backgroundColor: '#e67e22', tension: 0.4, fill: false }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } },
                scales: { y: { min: 0, max: 4.0 } },
                animations: {
                    // Hiệu ứng chạy từ trái sang: ép trục X xuất phát từ điểm 0 (bên trái)
                    x: {
                        type: 'number',
                        duration: 1000,
                        easing: 'easeOutQuart',
                        from: 0 
                    },
                    // TRIỆT TIÊU hiệu ứng từ dưới lên: ép trục Y bắt đầu ngay tại vị trí của nó
                    y: {
                        duration: 0 
                    }
                }
            }
        });
    }
}

// Logic chuyển slide (Giữ nguyên)
let currentSlide = 0;
function changeSlide(direction) {
    const slides = document.querySelectorAll('.chart-slide');
    if (slides.length === 0) return;

    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide + direction + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');

    // Chạy lại animation khi slide xuất hiện
    setTimeout(() => {
        const activeChart = currentSlide === 0 ? window.chartInstances.credits : window.chartInstances.gpa;
        if (activeChart) {
            activeChart.stop();
            activeChart.reset();
            activeChart.update();
        }
    }, 200);
}