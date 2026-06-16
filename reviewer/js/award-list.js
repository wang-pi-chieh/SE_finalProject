// Reviewer award-list module owned by Tsai Bo-Yu.
// Scope: review scoring, result integration, ranking, and final award list generation.
document.addEventListener("DOMContentLoaded", () => {
    loadApplications();
});

function loadApplications() {
    fetch("../api/reviewer/get_applications.php")
        .then(res => res.json())
        .then(data => {
            renderTable(data);
        })
        .catch(err => {
            console.error(err);
        });
}

function renderTable(list) {
    const tbody = document.getElementById("dashboard-table-body");
    tbody.innerHTML = "";

    list.forEach(app => {
        const tr = document.createElement("tr");

        tr.innerHTML = `
            <td>${app.id}</td>
            <td>${app.student_name || "-"}</td>
            <td>${app.scholarship_name || "-"}</td>

            <td>
                <input id="score_${app.id}" type="number" min="0" max="100">
            </td>

            <td>
                <textarea id="comment_${app.id}"></textarea>
            </td>

            <td>
                <button onclick="submitReview(${app.id})">
                    送出
                </button>
            </td>
        `;

        tbody.appendChild(tr);
    });
}