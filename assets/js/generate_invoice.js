document.addEventListener("DOMContentLoaded", () => {
    const invoiceForm = document.getElementById("invoiceForm");
    const grandTotal = document.getElementById("grandTotal");

    // 🔹 Calculate per-row total dynamically
    document.querySelectorAll(".qty, .rate").forEach(input => {
        input.addEventListener("input", () => {
            const row = input.closest("tr");
            const qty = parseFloat(row.querySelector(".qty").value) || 0;
            const rate = parseFloat(row.querySelector(".rate").value) || 0;
            const total = qty * rate;
            row.querySelector(".total").textContent = total.toFixed(2);
            updateGrandTotal();
        });
    });

    // 🔹 Remove row
    document.querySelectorAll(".removeRow").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.target.closest("tr").remove();
            updateGrandTotal();
        });
    });

    // 🔹 Update Grand Total
    function updateGrandTotal() {
        let sum = 0;
        document.querySelectorAll(".total").forEach(t => {
            sum += parseFloat(t.textContent) || 0;
        });
        grandTotal.textContent = sum.toFixed(2);
    }

    // 🔹 Submit form
    invoiceForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(invoiceForm);

        const res = await fetch("save_invoice.php", {
            method: "POST",
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            alert("✅ Invoice created successfully!");
            window.location.href = "manage_shop.php";
        } else {
            alert("❌ " + data.message);
        }
    });
});
