// Combobox
function filterByFaculty(facultyId) {
    let url = new URL(window.location.href);
    
    if (facultyId) {
        url.searchParams.set('faculty', facultyId);
    } else {
        url.searchParams.delete('faculty');
    }
    window.location.href = url.href;
}

const searchInput = document.querySelector('input[name="search"]');
if(searchInput) {
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            this.form.submit();
        }
    });
}

// Mở Modal Sửa và đổ dữ liệu
function openEditModal(data) {
    document.getElementById('editModal').style.display = 'block';
    
    document.getElementById('edit_id').value = data.account_id;
    document.getElementById('edit_fullname').value = data.fullname;
    document.getElementById('edit_dob').value = data.dob;
    document.getElementById('edit_gender').value = data.gender;
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_phone').value = data.phone || '';
    document.getElementById('edit_class').value = data.year || '';
    document.getElementById('edit_year').value = data.academic_year || '';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = "none";
    }
}