function selectForAppeal(subjectName, score, deadline) {
    const formSection = document.getElementById('form-section');
    const inputSubject = document.getElementById('selected-subject');
    const inputGrade = document.getElementById('current-grade');

    formSection.style.display = 'block';

    inputSubject.value = subjectName;
    inputGrade.value = score;
    
    formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    console.log("Hạn chót phúc khảo cho môn này là: " + deadline);
}

document.getElementById('pk-form').onsubmit = function(e) {
    const subject = document.getElementById('selected-subject').value;
    if (!subject) {
        e.preventDefault();
        alert('Vui lòng chọn một học phần từ danh sách bên trái trước khi gửi đơn!');
    }
};