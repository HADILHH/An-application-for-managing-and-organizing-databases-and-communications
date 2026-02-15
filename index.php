<?php
include 'config.php';

// إضافة زبون
if(isset($_POST['add'])){
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("INSERT INTO customers (full_name, phone, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $email);
    $stmt->execute();
}

// البحث
$search = "";
if(isset($_GET['search'])){
    $search = $_GET['search'];
    $result = $conn->query("SELECT * FROM customers 
                            WHERE full_name LIKE '%$search%' 
                            OR phone LIKE '%$search%' 
                            OR email LIKE '%$search%' 
                            ORDER BY id DESC");
}else{
    $result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Manager</title>
    <style>
        body { font-family: Arial; background:#f4f6f9; padding:20px; }
        table { width:100%; background:white; border-collapse:collapse; }
        th, td { padding:10px; border:1px solid #ddd; text-align:center; }
        th { background:#2c3e50; color:white; }
        form { margin-bottom:20px; }
        input { padding:8px; margin:5px; }
        button { padding:8px 12px; background:#3498db; color:white; border:none; }
        a { color:red; text-decoration:none; }
    </style>
</head>
<body>

<h2>📱 إدارة هواتف وإيميلات الزبائن</h2>

<form method="POST">
    <input type="text" name="name" placeholder="الاسم الكامل" required>
    <input type="text" name="phone" placeholder="رقم الهاتف" required>
    <input type="email" name="email" placeholder="البريد الإلكتروني" required>
    <button type="submit" name="add">إضافة</button>
</form>

<form method="GET">
    <input type="text" name="search" placeholder="بحث..." value="<?= $search ?>">
    <button type="submit">بحث</button>
</form>

<table>
    <tr>
        <th>ID</th>
        <th>الاسم</th>
        <th>الهاتف</th>
        <th>الإيميل</th>
        <th>إجراء</th>
    </tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['full_name'] ?></td>
    <td><?= $row['phone'] ?></td>
    <td><?= $row['email'] ?></td>
    <td>
        <a href="delete.php?id=<?= $row['id'] ?>">حذف</a>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
