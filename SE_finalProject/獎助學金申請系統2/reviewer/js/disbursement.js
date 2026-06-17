// Reviewer disbursement module owned by Tsai Bo-Yu.
// Scope: award disbursement records, payout status updates, result export, and completion notices.
document.addEventListener("DOMContentLoaded", () => {
  loadDisbursements();
});

function loadDisbursements() {
  fetch("/api/admin/get_disbursements.php")
    .then(res => res.json())
    .then(data => {
      const table = document.getElementById("disbursementTable");
      table.innerHTML = "";

      data.forEach(item => {
        const row = document.createElement("tr");

        row.innerHTML = `
          <td>${item.student_name}</td>
          <td>${item.amount}</td>
          <td>${item.status}</td>
          <td>
            ${item.status === "pending"
              ? `<button onclick="approve(${item.id})">核准</button>`
              : "已處理"}
          </td>
        `;

        table.appendChild(row);
      });
    });
}

function approve(id) {
  fetch("/api/admin/approve_disbursement.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ id })
  })
  .then(res => res.json())
  .then(result => {
    alert(result.message || "完成");
    loadDisbursements();
  });
}