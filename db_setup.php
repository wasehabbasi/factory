<?php

/**
 * db_setup.php
 * Run once to create tables + default admin
 * Database must already exist in cPanel.
 */

// DB connection config
$host = "localhost";      // cPanel usually 'localhost'
$user = "origamic_admin"; // MySQL username
$pass = "factory123@";    // MySQL password
$db   = "origamic_inventory_db";   // Existing database

// Connect to database
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

// SQL for tables (order fixed!)
$sql = <<<SQL
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) UNIQUE NOT NULL, 
  description VARCHAR(255),         
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL, -- short identifier e.g. 'sales', 'inventory'
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE,
  password_hash VARCHAR(255),
  role_id INT NOT NULL,  
  status ENUM('active','inactive') DEFAULT 'active',
  last_login TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    module_id INT NOT NULL,
    PRIMARY KEY (role_id, module_id),
    CONSTRAINT fk_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30),
  address VARCHAR(255),
  image_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS warehouses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(20) NOT NULL,
  address VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS factories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address VARCHAR(255),
  phone VARCHAR(30),
  image_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(64) UNIQUE,
  name VARCHAR(150) NOT NULL,
  unit VARCHAR(30) DEFAULT 'pcs',
  lot_number BIGINT,
  vendor_id INT,
  warehouse_id INT,
  measurement INT,
  cost DECIMAL(12,2) DEFAULT 0.00,
  price DECIMAL(12,2) DEFAULT 0.00,
  type VARCHAR(30),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_vendor 
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
  CONSTRAINT fk_products_warehouse 
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS product_lots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  lot_number BIGINT NOT NULL,
  expiry_date DATE DEFAULT NULL,
  vendor_id INT DEFAULT NULL,
  warehouse_id INT NOT NULL,
  qty DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  -- 🔗 Foreign Keys
  CONSTRAINT fk_lot_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lot_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_lot_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  -- 🔑 Unique & Indexes
  UNIQUE KEY uk_product_lot (product_id, lot_number, warehouse_id),
  INDEX idx_product_id (product_id),
  INDEX idx_lot_number (lot_number),
  INDEX idx_vendor_id (vendor_id),
  INDEX idx_warehouse_id (warehouse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS inventory_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  lot_number VARCHAR(64) DEFAULT NULL,
  movement_type ENUM('PURCHASE','SALE','TRANSFER','ADJUSTMENT','RETURN_IN','RETURN_OUT') NOT NULL,
  reference_id INT DEFAULT NULL,
  reference_type VARCHAR(50) DEFAULT NULL,
  qty DECIMAL(14,3) NOT NULL,
  unit_cost DECIMAL(12,2) DEFAULT 0.00,
  remarks VARCHAR(255) DEFAULT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mov_product FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_mov_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  CONSTRAINT fk_mov_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS send_inventories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL,
  factory_id INT NOT NULL,
  vendor_id INT NOT NULL,
  warehouse_id INT,
  lot_number BIGINT NOT NULL,
  quantity FLOAT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS receive_inventories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL,
  factory_id INT NOT NULL,
  vendor_id INT NOT NULL,
  lot_number BIGINT NOT NULL,
  send_quantity FLOAT NOT NULL,
  receive_quantity FLOAT NOT NULL,
  design_number INT DEFAULT 0,
  nag INT DEFAULT 0,
  shortage FLOAT DEFAULT 0,
  rejection FLOAT DEFAULT 0,
  warehouse_id INT DEFAULT NULL,
  l_kmi INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS shops (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address TEXT NOT NULL,
  phone_number VARCHAR(50) NOT NULL,
  image_url VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shop_inventories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shop_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  product_id INT NOT NULL,
  lot_number BIGINT,
  qty DECIMAL(12,3) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (shop_id) REFERENCES shops(id),
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS warehouse_to_shop (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL,
  warehouse_id INT NOT NULL,
  shop_id INT NOT NULL,
  product_id INT NOT NULL,
  lot_number BIGINT,
  qty DECIMAL(12,3) NOT NULL DEFAULT 0,
  design_number INT,
  nag INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shop_invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(50) UNIQUE,
  shop_id INT NOT NULL,
  customer_name VARCHAR(100),
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  paandi_name VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (shop_id) REFERENCES shops(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shop_sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  invoice_no VARCHAR(50),
  product_id INT NOT NULL,
  qty DECIMAL(12,3) NOT NULL,
  rate DECIMAL(12,2) NOT NULL,
  cutting DECIMAL(12,2),
  total DECIMAL(12,2) GENERATED ALWAYS AS (qty * rate) STORED,
  total_suits INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES shop_invoices(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  type ENUM('warehouse','factory','shop') NOT NULL,
  address VARCHAR(255),
  phone VARCHAR(30),
  image_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS buyers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address TEXT NOT NULL,
  phone VARCHAR(50) NOT NULL,
  image_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS buyer_invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(50) NOT NULL UNIQUE,
  buyer_id INT NOT NULL,
  product_id INT NULL,
  lot_number VARCHAR(100) NOT NULL,
  design_number INT NULL,
  warehouse_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  nag DECIMAL(10,2) NULL DEFAULT 0.00,
  amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_date DATE DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Foreign Keys
  FOREIGN KEY (buyer_id) REFERENCES buyers(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS buyer_ledger (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date date,
  invoice_no VARCHAR(50) NOT NULL UNIQUE,
  buyer_id INT NOT NULL,
  product_id INT NULL,
  lot_number VARCHAR(100) NOT NULL,
  design_number INT NULL,
  warehouse_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  nag DECIMAL(10,2) NULL DEFAULT 0.00,
  amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_date DATE DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Foreign Keys
  FOREIGN KEY (buyer_id) REFERENCES buyers(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE IF NOT EXISTS stock_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  warehouse_id INT NOT NULL,
  product_id INT NOT NULL,
  qty DECIMAL(14,3) DEFAULT 0,
  CONSTRAINT fk_stock_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
  CONSTRAINT fk_stock_prod FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_wh_prod (warehouse_id, product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase ( 
  id INT AUTO_INCREMENT PRIMARY KEY, 
  date DATE NOT NULL, 
  vendor_id INT NOT NULL, 
  rate DECIMAL(10,2), 
  lot_number VARCHAR(100), 
  measurement VARCHAR(50), 
  product_name VARCHAR(150), 
  width VARCHAR(50), 
  thaan INT, 
  issue_meter DECIMAL(10,2), 
  product_id INT, 
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, 
  CONSTRAINT purchase_ibfk_1 FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE 
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) UNIQUE,
  phone VARCHAR(30),
  address VARCHAR(255),
  designation VARCHAR(100),
  joining_date DATE,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT NOT NULL,
  product_id INT NOT NULL,
  qty DECIMAL(14,3) NOT NULL,
  cost DECIMAL(12,2) NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_pi_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  CONSTRAINT fk_pi_prod FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  category VARCHAR(100) NOT NULL,
  description VARCHAR(255),
  amount DECIMAL(12,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS salaries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  salary_month DATE NOT NULL,
  person_name VARCHAR(150) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  notes VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  factory_id INT NOT NULL,
  invoice_date DATE NOT NULL,
  warehouse_id INT NOT NULL,
  subtotal DECIMAL(12,2) DEFAULT 0.00,
  discount DECIMAL(12,2) DEFAULT 0.00,
  total DECIMAL(12,2) DEFAULT 0.00,
  notes VARCHAR(255),
  CONSTRAINT fk_inv_factory FOREIGN KEY (factory_id) REFERENCES factories(id),
  CONSTRAINT fk_inv_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vendor_invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  lot_number BIGINT NOT NULL,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  issue_meter DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  rejection DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  safi_meter DECIMAL(14,2) AS (issue_meter - rejection),
  rate DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(14,2) AS (total_amount - amount_paid) STORED,
  payment_date DATE DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoice_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vendor_ledger (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  lot_number BIGINT NOT NULL,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  balance DECIMAL(14,2) AS (total_amount - amount_paid) STORED,
  payment_date DATE DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoice_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('general','salary') NOT NULL DEFAULT 'general',
  date DATE NOT NULL,
  month VARCHAR(20) DEFAULT NULL,
  employee_id INT DEFAULT NULL,
  details TEXT NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_expense_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  product_id INT NOT NULL,
  qty DECIMAL(14,3) NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_ii_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  CONSTRAINT fk_ii_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(255),
    site_email VARCHAR(255),
    logo_url VARCHAR(255),
    favicon_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS balance_sheet (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lot_number BIGINT NOT NULL,
  l_kmi VARCHAR(100) DEFAULT NULL,
  remaining_meter DECIMAL(14,3) DEFAULT 0,
  final_remarks TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS factory_invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  factory_id INT NOT NULL,
  product_id INT NOT NULL,
  lot_number BIGINT NOT NULL,
  total_meter DECIMAL(12,2) NOT NULL,
  per_meter_rate DECIMAL(12,2) NOT NULL,
  total_amount DECIMAL(14,2) GENERATED ALWAYS AS (total_meter * per_meter_rate) STORED,
  rejection DECIMAL(12,2) DEFAULT 0.00,
  advance_adjusted DECIMAL(12,2) DEFAULT 0.00
  net_amount DECIMAL(14,2) GENERATED ALWAYS AS (total_amount - rejection) STORED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_factory_invoice_factory FOREIGN KEY (factory_id) REFERENCES factories(id) ON DELETE CASCADE,
  CONSTRAINT fk_factory_invoice_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS factory_ledger (
  id INT AUTO_INCREMENT PRIMARY KEY,
  factory_id INT NOT NULL,
  product_id INT NOT NULL,
  lot_number BIGINT NOT NULL,
  total_meter DECIMAL(12,2) NOT NULL,
  per_meter_rate DECIMAL(12,2) NOT NULL,
  total_amount DECIMAL(14,2) GENERATED ALWAYS AS (total_meter * per_meter_rate) STORED,
  rejection DECIMAL(12,2) DEFAULT 0.00,
  advance_adjusted DECIMAL(12,2) DEFAULT 0.00,
  net_amount DECIMAL(14,2) GENERATED ALWAYS AS (total_amount - rejection - advance_adjusted) STORED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_factory_ledger_factory FOREIGN KEY (factory_id)
      REFERENCES factories(id) ON DELETE CASCADE,
  CONSTRAINT fk_factory_ledger_product FOREIGN KEY (product_id)
      REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS temp_nag_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    shop_id INT,
    design_number VARCHAR(100),
    old_nag VARCHAR(100),
    new_nag VARCHAR(100),
    wts_id INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS temp_quantity_adjustments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  wts_id INT NOT NULL,
  product_id INT NOT NULL,
  shop_id INT NOT NULL,
  design_number VARCHAR(100),
  adjusted_qty DECIMAL(12,3) DEFAULT 0,  -- negative value for minus
  note VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS warehouse_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT NOT NULL,
    product_id INT NOT NULL,
    lot_number BIGINT NOT NULL,
    quantity DECIMAL(14,3) NOT NULL DEFAULT 0.000,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_wh_prod_lot (warehouse_id, product_id, lot_number)
);


SQL;

// Run multi-query
if (!mysqli_multi_query($conn, $sql)) {
  die("Error creating tables: " . mysqli_error($conn));
}

// Flush all results
do {
  if ($res = mysqli_store_result($conn)) {
    mysqli_free_result($res);
  }
} while (mysqli_next_result($conn));

// Insert default modules
$modules = [
  ['Vendors', 'vendors'],
  ['Warehouses', 'warehouses'],
  ['Factories', 'factories'],
  ['Inventory', 'inventory'],
  ['Shop', 'shop'],
  ['Buyer', 'buyer'],
  ['Purchases', 'purchases'],
  ['Expenses & Salaries', 'expenses_salaries'],
  ['Invoices (PDF)', 'invoices'],
  ['Reports', 'reports'],
  ['Balance Sheet', 'balance_sheet'],
  ['Site Settings', 'site_settings'],
  ['Profile Settings', 'profile_settings'],
  ['Roles', 'roles']
];

foreach ($modules as $m) {
  $name = $m[0];
  $slug = $m[1];
  $check = mysqli_query($conn, "SELECT id FROM modules WHERE slug='$slug' LIMIT 1");
  if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "INSERT INTO modules (name,slug) VALUES ('$name','$slug')");
  }
}

// Insert default admin role if not exists
$roleCheck = mysqli_query($conn, "SELECT id FROM roles WHERE name='Admin' LIMIT 1");
if (mysqli_num_rows($roleCheck) == 0) {
  mysqli_query($conn, "INSERT INTO roles (name, description) VALUES ('Admin', 'System Administrator')");
  $adminRoleId = mysqli_insert_id($conn);
} else {
  $row = mysqli_fetch_assoc($roleCheck);
  $adminRoleId = $row['id'];
}

// Insert default admin user if not exists
$adminEmail = "admin@factory.com";
$adminPass = password_hash("admin123", PASSWORD_DEFAULT);
$check = mysqli_query($conn, "SELECT id FROM users WHERE email='$adminEmail' LIMIT 1");

if (mysqli_num_rows($check) == 0) {
  $insert = mysqli_prepare($conn, "INSERT INTO users (name,email,password_hash,role_id) VALUES (?,?,?,?)");
  $name = "System Admin";
  mysqli_stmt_bind_param($insert, "sssi", $name, $adminEmail, $adminPass, $adminRoleId);
  mysqli_stmt_execute($insert);
  mysqli_stmt_close($insert);
  echo "Default admin created (Email: admin@factory.com | Password: admin123)<br>";
} else {
  // echo "Admin already exists.<br>";
}

mysqli_close($conn);
// echo "All tables created successfully!";
