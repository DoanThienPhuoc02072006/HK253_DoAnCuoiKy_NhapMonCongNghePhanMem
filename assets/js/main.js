/* =========================================================
   HRM SYSTEM - MAIN JAVASCRIPT
   ========================================================= */

/* ---------- Logout modal ---------- */
function openLogoutModal() {
    document.getElementById('logoutModal').classList.add('show');
}
function closeLogoutModal() {
    document.getElementById('logoutModal').classList.remove('show');
}

/* ---------- User dropdown ---------- */
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('userMenuBtn');
    var dd = document.getElementById('userDropdown');
    if (btn && dd) {
        btn.addEventListener('click', function (e) {
            dd.classList.toggle('show');
            e.stopPropagation();
        });
        document.addEventListener('click', function () {
            dd.classList.remove('show');
        });
    }

    var menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('open');
        });
    }

    // Auto-hide flash alerts after 4s
    document.querySelectorAll('.alert[data-autohide]').forEach(function (el) {
        setTimeout(function () { el.style.display = 'none'; }, 4000);
    });
});

/* ---------- Confirm delete (generic) ---------- */
function confirmDelete(message, callback) {
    if (confirm(message || 'Bạn có chắc chắn muốn xóa mục này?')) {
        callback();
    }
}

/* ---------- Approve / Reject request via AJAX ---------- */
function xuLyYeuCau(id, hanhDong, btn) {
    var msg = hanhDong === 'duyet' ? 'Phê duyệt yêu cầu này?' : 'Từ chối yêu cầu này?';
    if (!confirm(msg)) return;

    fetch('ajax/request_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id) + '&hanh_dong=' + encodeURIComponent(hanhDong)
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data.success) {
            var row = btn.closest('tr');
            if (row) {
                var badge = row.querySelector('.badge-status');
                if (badge) {
                    badge.className = 'badge-status ' + (hanhDong === 'duyet' ? 'approved' : 'rejected');
                    badge.textContent = hanhDong === 'duyet' ? 'Đã phê duyệt' : 'Từ chối';
                }
                var actionCell = row.querySelector('.action-icons');
                if (actionCell) {
                    actionCell.innerHTML = '<a class="icon-btn view" href="request_view.php?id=' + id + '"><i class="fa-regular fa-eye"></i></a>';
                }
            }
            refreshStatsIfPresent();
        } else {
            alert(data.message || 'Có lỗi xảy ra, vui lòng thử lại.');
        }
    })
    .catch(function () { alert('Không thể kết nối máy chủ.'); });
}

function refreshStatsIfPresent() {
    // Đơn giản: tải lại trang sau 700ms để cập nhật số liệu thống kê phía trên
    setTimeout(function () { location.reload(); }, 700);
}

/* ---------- Delete employee via AJAX ---------- */
function xoaNhanVien(id, btn) {
    if (!confirm('Bạn có chắc chắn muốn xóa nhân viên này? Hành động này không thể hoàn tác.')) return;
    fetch('ajax/employee_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data.success) {
            var row = btn.closest('tr');
            if (row) row.remove();
        } else {
            alert(data.message || 'Không thể xóa nhân viên.');
        }
    })
    .catch(function () { alert('Không thể kết nối máy chủ.'); });
}

/* ---------- Avatar preview (add/edit employee & profile) ---------- */
function previewAvatar(input, imgId) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        var maxBytes = 2 * 1024 * 1024; // 2MB - khớp với giới hạn khuyến nghị hiển thị trên form
        if (file.size > maxBytes) {
            alert('Ảnh bạn chọn có dung lượng ' + (file.size / 1024 / 1024).toFixed(1) + 'MB, vượt quá 2MB khuyến nghị. Ảnh vẫn có thể tải lên nếu dưới 10MB, nhưng nên chọn ảnh nhỏ hơn để tải nhanh hơn.');
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = document.getElementById(imgId);
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            // Ẩn icon + hướng dẫn mặc định khi đã có ảnh xem trước, để không bị chồng chữ lên ảnh
            var box = input.closest('.upload-box');
            if (box) {
                box.querySelectorAll('.big-ic, .upload-hint').forEach(function (el) { el.style.display = 'none'; });
            }
        };
        reader.readAsDataURL(file);
    }
}

/* ---------- Select all attendees ---------- */
function toggleAllAttendees(select) {
    document.querySelectorAll('.attendee-checkbox').forEach(function (cb) {
        cb.checked = select;
    });
}

/* ---------- Simple client-side search filter for tables ---------- */
function filterTable(inputEl, tableSelector) {
    var q = inputEl.value.toLowerCase();
    document.querySelectorAll(tableSelector + ' tbody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
    });
}
