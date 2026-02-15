<?php
include 'config.php';

// =====================
// Ajouter Client
// =====================
if(isset($_POST['add_customer'])){
    $name = htmlspecialchars($_POST['name']);
    $institution = htmlspecialchars($_POST['institution']);
    $notes = htmlspecialchars($_POST['notes']);
    $stmt = $conn->prepare("INSERT INTO customers (full_name, institution, notes) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $institution, $notes);
    $stmt->execute();
}

// =====================
// Ajouter Téléphone
// =====================
if(isset($_POST['add_phone'])){
    $customer_id = intval($_POST['customer_id']);
    $phone = htmlspecialchars($_POST['phone']);
    $stmt = $conn->prepare("INSERT INTO phones (customer_id, phone) VALUES (?, ?)");
    $stmt->bind_param("is", $customer_id, $phone);
    $stmt->execute();
}

// =====================
// Ajouter Email
// =====================
if(isset($_POST['add_email'])){
    $customer_id = intval($_POST['customer_id']);
    $email = htmlspecialchars($_POST['email']);
    $stmt = $conn->prepare("INSERT INTO emails (customer_id, email) VALUES (?, ?)");
    $stmt->bind_param("is", $customer_id, $email);
    $stmt->execute();
}

// =====================
// Créer Groupe
// =====================
if(isset($_POST['add_group'])){
    $name = htmlspecialchars($_POST['group_name']);
    $description = htmlspecialchars($_POST['group_desc']);
    $stmt = $conn->prepare("INSERT INTO groups (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $description);
    $stmt->execute();
}

// =====================
// Ajouter Client au Groupe
// =====================
if(isset($_POST['add_to_group'])){
    $customer_id = intval($_POST['customer_id']);
    $group_id = intval($_POST['group_id']);
    $check = $conn->query("SELECT * FROM customer_group WHERE customer_id=$customer_id AND group_id=$group_id");
    if($check->num_rows == 0){
        $conn->query("INSERT INTO customer_group (customer_id, group_id) VALUES ($customer_id, $group_id)");
    }
}

// =====================
// Supprimer Client / Téléphone / Email
// =====================
if(isset($_GET['delete_customer'])){
    $id = intval($_GET['delete_customer']);
    $conn->query("DELETE FROM customers WHERE id=$id");
}
if(isset($_GET['delete_phone'])){
    $id = intval($_GET['delete_phone']);
    $conn->query("DELETE FROM phones WHERE id=$id");
}
if(isset($_GET['delete_email'])){
    $id = intval($_GET['delete_email']);
    $conn->query("DELETE FROM emails WHERE id=$id");
}

// =====================
// Supprimer Client d'un Groupe
// =====================
if(isset($_GET['remove_group']) && isset($_GET['customer_id'])){
    $group_id = intval($_GET['remove_group']);
    $customer_id = intval($_GET['customer_id']);
    $conn->query("DELETE FROM customer_group WHERE customer_id=$customer_id AND group_id=$group_id");
}

// =====================
// Filtrer par Groupe
// =====================
$selected_group = "";
if(isset($_GET['group']) && !empty($_GET['group'])){
    $selected_group = intval($_GET['group']);
}

// =====================
// Recherche
// =====================
$search = "";
if(isset($_GET['search'])){
    $search = $conn->real_escape_string($_GET['search']);
}

// =====================
// Récupérer tous les Groupes
// =====================
$all_groups = $conn->query("SELECT id, name FROM groups ORDER BY name");

// =====================
// Récupérer les Clients
// =====================
$customers_sql = "
SELECT c.id, c.full_name, c.institution, c.notes,
GROUP_CONCAT(DISTINCT p.phone SEPARATOR ', ') AS phones,
GROUP_CONCAT(DISTINCT e.email SEPARATOR ', ') AS emails,
GROUP_CONCAT(DISTINCT g.id, ':', g.name SEPARATOR ',') AS groups
FROM customers c
LEFT JOIN phones p ON c.id=p.customer_id
LEFT JOIN emails e ON c.id=e.customer_id
LEFT JOIN customer_group cg ON c.id=cg.customer_id
LEFT JOIN groups g ON cg.group_id=g.id
WHERE (c.full_name LIKE '%$search%' OR c.institution LIKE '%$search%' 
OR c.notes LIKE '%$search%' OR p.phone LIKE '%$search%' 
OR e.email LIKE '%$search%' OR g.name LIKE '%$search%')
";

if($selected_group){
    $customers_sql .= " AND cg.group_id = $selected_group";
}

$customers_sql .= " GROUP BY c.id ORDER BY c.id DESC";
$customers = $conn->query($customers_sql);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tableau de Bord Clients</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background:#f0f2f5; margin:0; color:#333; }
.sidebar { position: fixed; top:0; left:0; width:220px; height:100vh; background:#1f2937; color:white; overflow-y:auto; padding-bottom:20px; }
.sidebar h4 { padding:15px; margin:0; font-weight:600; border-bottom:1px solid #111827; text-align:center; }
.sidebar a { display:block; padding:12px 20px; color:white; text-decoration:none; border-radius:6px; margin:5px 10px; transition:0.3s; }
.sidebar a:hover { background:#374151; }
.main { margin-left:220px; padding:25px; min-width:0; }
.card { border-radius:12px; box-shadow:0 6px 15px rgba(0,0,0,0.08); margin-bottom:20px; background:white; padding:20px; }
.card h5 { font-weight:500; margin-bottom:15px; color:#111827; }
input.form-control, select.form-select { border-radius:6px; }
.input-group { width:100%; }
.input-group-text { background:#3b82f6; color:white; border:none; }
.table th { background:#1f2937; color:white; white-space:nowrap; }
.table td, .table th { vertical-align:middle; }
.table-responsive { overflow-x:auto; }
.btn-primary { background:#3b82f6; border:none; }
.btn-primary:hover { background:#2563eb; }
.btn-success { background:#10b981; border:none; }
.btn-success:hover { background:#059669; }
.btn-danger { background:#ef4444; border:none; }
.btn-danger:hover { background:#dc2626; }
.badge-group { display:inline-flex; align-items:center; padding:5px 8px; border-radius:8px; background:#3b82f6; color:white; margin:2px; }
.badge-group a { color:white; margin-left:5px; text-decoration:none; }
@media (max-width:768px){ .sidebar { width:180px; } .main { margin-left:180px; padding:15px; } .input-group { flex-direction:column; } .input-group input, .input-group button { width:100%; margin-bottom:5px; } table { font-size:14px; } }
</style>
</head>
<body>

<div class="sidebar">
    <h4>Groupes</h4>
    <a href="admin.php">Tous</a>
    <?php while($g = $all_groups->fetch_assoc()): ?>
        <a href="group.php?id=<?= $g['id'] ?>"><?= $g['name'] ?></a>
    <?php endwhile; ?>
</div>

<div class="main">
    <h3>🛠 Tableau de Bord Clients</h3>

    <form method="GET" class="mb-3">
        <?php if($selected_group): ?>
            <input type="hidden" name="group" value="<?= $selected_group ?>">
        <?php endif; ?>
        <div class="input-group mb-3">
            <span class="input-group-text">🔍</span>
            <input type="text" class="form-control" name="search" placeholder="Recherche..." value="<?= $search ?>">
            <button class="btn btn-primary">Rechercher</button>
        </div>
    </form>

    <div class="card">
    <h5>➕ Ajouter un nouveau client</h5>
    <form method="POST" class="row g-3">
        <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Nom complet" required></div>
        <div class="col-md-4"><input type="text" name="institution" class="form-control" placeholder="Entreprise"></div>
        <div class="col-md-4"><input type="text" name="notes" class="form-control" placeholder="Adresse"></div>
        <div class="col-12"><button type="submit" name="add_customer" class="btn btn-success">Ajouter</button></div>
    </form>
    </div>

    <div class="card">
    <h5>➕ Créer un nouveau groupe</h5>
    <form method="POST" class="row g-3">
        <div class="col-md-6"><input type="text" name="group_name" class="form-control" placeholder="Nom du groupe" required></div>
        <div class="col-md-6"><input type="text" name="group_desc" class="form-control" placeholder="Description"></div>
        <div class="col-12"><button type="submit" name="add_group" class="btn btn-success">Créer le groupe</button></div>
    </form>
    </div>

    <div class="card table-responsive">
    <table class="table table-hover align-middle">
    <thead>
    <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Entreprise</th>
        <th>Adresse</th>
        <th>Téléphone</th>
        <th>Email</th>
        <th>Groupes</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php while($row = $customers->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['full_name'] ?></td>
        <td><?= $row['institution'] ?></td>
        <td><?= $row['notes'] ?></td>
        <td>
            <?= $row['phones'] ?: '-' ?>
            <form method="POST" class="d-inline mt-1">
                <input type="hidden" name="customer_id" value="<?= $row['id'] ?>">
                <input type="text" name="phone" class="form-control form-control-sm d-inline w-auto" placeholder="📞 Ajouter téléphone" required>
                <button type="submit" name="add_phone" class="btn btn-primary btn-sm mt-1">Ajouter</button>
            </form>
        </td>
        <td>
            <?= $row['emails'] ?: '-' ?>
            <form method="POST" class="d-inline mt-1">
                <input type="hidden" name="customer_id" value="<?= $row['id'] ?>">
                <input type="email" name="email" class="form-control form-control-sm d-inline w-auto" placeholder="✉️ Ajouter email" required>
                <button type="submit" name="add_email" class="btn btn-primary btn-sm mt-1">Ajouter</button>
            </form>
        </td>
        <td>
            <?php
            if($row['groups']){
                $groups_arr = explode(',', $row['groups']);
                foreach($groups_arr as $g){
                    list($group_id, $group_name) = explode(':', $g);
                    echo '<span class="badge-group">';
                    echo htmlspecialchars($group_name);
                    echo ' <a href="edit_group.php?group_id='.$group_id.'&customer_id='.$row['id'].'" title="Modifier">&#9998;</a>';
                    echo ' <a href="?remove_group='.$group_id.'&customer_id='.$row['id'].'" title="Supprimer">&#10006;</a>';
                    echo '</span>';
                }
            } else {
                echo '-';
            }
            ?>
            <form method="POST" class="d-inline mt-1">
                <input type="hidden" name="customer_id" value="<?= $row['id'] ?>">
                <select name="group_id" class="form-select form-select-sm d-inline w-auto" required>
                    <option value="">👥 Choisir un groupe</option>
                    <?php
                    $all_groups->data_seek(0);
                    while($g = $all_groups->fetch_assoc()){
                        echo "<option value='".$g['id']."'>".$g['name']."</option>";
                    }
                    ?>
                </select>
                <button type="submit" name="add_to_group" class="btn btn-primary btn-sm mt-1">Ajouter</button>
            </form>
        </td>
        <td>
            <a href="admin.php?delete_customer=<?= $row['id'] ?>" class="btn btn-danger btn-sm mb-1">Supprimer</a>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
    </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
