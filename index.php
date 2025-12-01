<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// echo '<h1>' . $_SESSION['role_id'] . '</h1>';exit;

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/topbar.php';
?>

<section id="view" class="p-3 p-md-4">
    <div class="row g-3">
        <!-- Vendors -->
        <div class="col-md-3">
            <div class="card p-3 shadow-sm rounded-3">
                <div class="small text-secondary">Vendors</div>
                <div id="statVendors" class="h3">—</div>
            </div>
        </div>

        <!-- Warehouses -->
        <div class="col-md-3">
            <div class="card p-3 shadow-sm rounded-3">
                <div class="small text-secondary">Warehouses</div>
                <div id="statWarehouses" class="h3">—</div>
            </div>
        </div>

        <!-- Products -->
        <div class="col-md-3">
            <div class="card p-3 shadow-sm rounded-3">
                <div class="small text-secondary">Products</div>
                <div id="statProducts" class="h3">—</div>
            </div>
        </div>

        <!-- Factories -->
        <div class="col-md-3">
            <div class="card p-3 shadow-sm rounded-3">
                <div class="small text-secondary">Factories</div>
                <div id="statFactories" class="h3">—</div>
            </div>
        </div>

        <!-- Shops -->
        <div class="col-md-3">
            <div class="card p-3 shadow-sm rounded-3">
                <div class="small text-secondary">Shops</div>
                <div id="statShops" class="h3">—</div>
            </div>
        </div>

        <!-- Buyers -->
        <div class="col-md-3">
            <div class="card p-3 shadow-sm rounded-3">
                <div class="small text-secondary">Buyers</div>
                <div id="statBuyers" class="h3">—</div>
            </div>
        </div>

        <!-- Users -->
        <div class="col-md-3">
            <div class="card p-3 shadow-sm rounded-3">
                <div class="small text-secondary">Users</div>
                <div id="statUsers" class="h3">—</div>
            </div>
        </div>

        <!-- Employees -->
        <div class="col-md-3">
            <div class="card p-3 shadow-sm rounded-3">
                <div class="small text-secondary">Employees</div>
                <div id="statEmployees" class="h3">—</div>
            </div>
        </div>
    </div>
</section>

<section class="mt-4">
    <div class="card p-3 shadow-sm rounded-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h5 class="mb-2 mb-md-0">Shop Sales Overview</h5>
            <div class="d-flex gap-2">
                <select id="shopFilter" class="btn btn-primary-light">
                    <option value="all">All Shops</option>
                </select>
                <select id="timeFilter" class="btn btn-secondary-light">
                    <option value="all">All Time</option>
                    <option value="daily">Today</option>
                    <option value="monthly">This Month</option>
                    <option value="yearly">This Year</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table id="salesTable" class="table text-nowrap table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Shop</th>
                        <th>Total Quantity (m)</th>
                        <th>Total Suits</th>
                        <th>Total Sale (PKR)</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="d-flex gap-2 mb-3">
            <label class="small text-secondary align-self-center">Chart Type:</label>
            <select id="chartType" class="btn btn-primary">
                <option value="bar">Bar</option>
                <option value="line">Line</option>
                <option value="radar">Radar</option>
                <!-- <option value="pie">Pie</option>
                <option value="doughnut">Doughnut</option> -->
            </select>
        </div>
        <canvas id="salesChart" height="100"></canvas>
    </div>
</section>


<main class="content">
    <div id="view"></div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        fetch('dashboard_stats.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('statVendors').textContent = data.vendors;
                    document.getElementById('statWarehouses').textContent = data.warehouses;
                    document.getElementById('statProducts').textContent = data.products;
                    // document.getElementById('statInvoices').textContent = data.invoices;
                    document.getElementById('statFactories').textContent = data.factories;
                    document.getElementById('statShops').textContent = data.shops;
                    document.getElementById('statBuyers').textContent = data.buyers;
                    document.getElementById('statUsers').textContent = data.users;
                    document.getElementById('statEmployees').textContent = data.employees;
                }
            })
            .catch(err => console.error(err));
    });
</script>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    async function loadShops() {
        const res = await fetch('shops.php?action=list'); // Adjust if your endpoint differs
        const d = await res.json();
        if (d.success) {
            const select = document.getElementById("shopFilter");
            d.data.forEach(shop => {
                const opt = document.createElement("option");
                opt.value = shop.id;
                opt.textContent = shop.name;
                select.appendChild(opt);
            });
        }
    }

    async function loadDashboardSales() {
        const shopId = document.getElementById("shopFilter").value;
        const filter = document.getElementById("timeFilter").value;

        const res = await fetch(`dashboard_sales.php?shop_id=${shopId}&filter=${filter}`);
        const d = await res.json();

        const tbody = document.querySelector("#salesTable tbody");
        tbody.innerHTML = "";

        if (!d.success || !d.data.length) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center">No records found</td></tr>`;
            return;
        }

        let rows = "",
            chartLabels = [],
            chartSales = [];
        d.data.forEach((r, i) => {
            const qty = parseFloat(r.total_quantity || 0).toFixed(2);
            const sale = parseFloat(r.total_sale || 0).toFixed(2);
            const total_suits = parseFloat(r.total_suits || 0).toFixed(2);
            rows += `<tr>
      <td>${i + 1}</td>
      <td>${r.shop_name}</td>
      <td>${qty}</td>
      <td>${total_suits}</td>
      <td>${sale}</td>
    </tr>`;
            chartLabels.push(r.shop_name);
            chartSales.push(parseFloat(r.total_sale || 0));
        });

        tbody.innerHTML = rows;

        // Chart
        renderSalesChart(chartLabels, chartSales);
    }

    let salesChart;

    function renderSalesChart(labels, data) {
        const ctx = document.getElementById("salesChart").getContext("2d");
        const chartType = document.getElementById("chartType").value; // ✅ get selected chart type

        if (salesChart) salesChart.destroy();

        salesChart = new Chart(ctx, {
            type: chartType,
            data: {
                labels,
                datasets: [{
                    label: "Total Sale (PKR)",
                    data,
                    backgroundColor: chartType === 'line' ? 'rgba(255,77,77,0.2)' : '#ff4d4d', // fill for line, solid for bar
                    borderColor: "#ff4d4d",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: "top"
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return "PKR " + context.formattedValue;
                            }
                        }
                    }
                },
                scales: chartType === 'bar' || chartType === 'line' ? {
                    x: {
                        ticks: {
                            color: "#fff"
                        },
                        grid: {
                            color: "rgba(0,0,0,0.1)"
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => "PKR " + v,
                            color: "#fff"
                        },
                        grid: {
                            color: "rgba(0,0,0,0.1)"
                        }
                    }
                } : {}
            }
        });
    }

    // Update chart when chart type is changed
    document.getElementById("chartType").addEventListener("change", loadDashboardSales);


    document.addEventListener("DOMContentLoaded", () => {
        loadShops();
        loadDashboardSales();
        document.getElementById("shopFilter").addEventListener("change", loadDashboardSales);
        document.getElementById("timeFilter").addEventListener("change", loadDashboardSales);
    });
</script>