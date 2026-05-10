document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) { 
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
});

// Hàm lọc theo khoa
function filterByFaculty(facultyId) {
    if (!facultyId) return;
    let url = new URL(window.location.href);
    url.searchParams.set('faculty', facultyId);
    url.searchParams.delete('search'); 
    window.location.href = url.href;
}

// Hàm mở Modal thêm (Dùng chung cho cả 'addModal' và 'addScheduleModal')
function openAddModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'block';
    }
}

// Hàm đóng Modal
function closeModal(id) { 
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Hàm mở Modal sửa CHUYÊN NGÀNH
function openEditModal(data) {
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.major_name;
}

// --- HÀM MỚI: Mở Modal sửa LỊCH ĐĂNG KÝ ---
function openEditScheduleModal(data) {
    const modal = document.getElementById('editScheduleModal');
    if (modal) {
        modal.style.display = 'block';
        // Gán dữ liệu vào các input trong Modal sửa lịch
        if(document.getElementById('edit_sched_id')) document.getElementById('edit_sched_id').value = data.id;
        if(document.getElementById('edit_sched_major')) document.getElementById('edit_sched_major').value = data.major_id;
        
        // Chuyển định dạng từ MySQL (YYYY-MM-DD HH:MM:SS) sang HTML (YYYY-MM-DDTHH:MM)
        if(data.date_start) {
            document.getElementById('edit_sched_start').value = data.date_start.replace(" ", "T").substring(0, 16);
        }
        if(data.date_end) {
            document.getElementById('edit_sched_end').value = data.date_end.replace(" ", "T").substring(0, 16);
        }
    }
}

// Đóng modal khi click ra ngoài vùng xám
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = "none";
    }
}