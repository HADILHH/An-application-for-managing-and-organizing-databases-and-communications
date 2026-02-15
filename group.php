<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

// =====================
// Vérifier l'ID du groupe
// =====================
if(!isset($_GET['id']) || empty($_GET['id'])){
    die("Erreur : aucun groupe spécifié. <a href='admin.php'>Retour</a>");
}

$group_id = intval($_GET['id']);
$search = "";
if(isset($_GET['search'])) $search = $conn->real_escape_string($_GET['search']);

// =====================
// Récupérer les infos du groupe
// =====================
$group_result = $conn->query("SELECT * FROM groups WHERE id=$group_id");
if($group_result->num_rows == 0){
    die("Erreur : ce groupe n'existe pas. <a href='admin.php'>Retour</a>");
}
$group = $group_result->fetch_assoc();

// =====================
// Ajouter un membre
// =====================
if(isset($_POST['add_customer'])){
    $name = htmlspecialchars($_POST['name']);
    $institution = htmlspecialchars($_POST['institution']);
    $notes = htmlspecialchars($_POST['notes']);
    $stmt = $conn->prepare("INSERT INTO customers (full_name, institution, notes) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $institution, $notes);
    $stmt->execute();
    $customer_id = $stmt->insert_id;
    $conn->query("INSERT INTO customer_group (customer_id, group_id) VALUES ($customer_id, $group_id)");

    // Ajouter téléphones
    if(isset($_POST['phones']) && is_array($_POST['phones'])){
        foreach($_POST['phones'] as $phone){
            $phone = htmlspecialchars($phone);
            if(!empty($phone)){
                $stmt = $conn->prepare("INSERT INTO phones (customer_id, phone) VALUES (?, ?)");
                $stmt->bind_param("is", $customer_id, $phone);
                $stmt->execute();
            }
        }
    }

    // Ajouter emails
    if(isset($_POST['emails']) && is_array($_POST['emails'])){
        foreach($_POST['emails'] as $email){
            $email = htmlspecialchars($email);
            if(!empty($email)){
                $stmt = $conn->prepare("INSERT INTO emails (customer_id, email) VALUES (?, ?)");
                $stmt->bind_param("is", $customer_id, $email);
                $stmt->execute();
            }
        }
    }

    header("Location: group.php?id=$group_id");
    exit;
}

// =====================
// Supprimer membre
// =====================
if(isset($_GET['delete_customer'])){
    $id = intval($_GET['delete_customer']);
    $conn->query("DELETE FROM customers WHERE id=$id");
    $conn->query("DELETE FROM customer_group WHERE customer_id=$id");
    $conn->query("DELETE FROM phones WHERE customer_id=$id");
    $conn->query("DELETE FROM emails WHERE customer_id=$id");
    header("Location: group.php?id=$group_id");
    exit;
}

// =====================
// Modifier membre
// =====================
if(isset($_POST['edit_customer'])){
    $id = intval($_POST['customer_id']);
    $name = htmlspecialchars($_POST['name']);
    $institution = htmlspecialchars($_POST['institution']);
    $notes = htmlspecialchars($_POST['notes']);
    $stmt = $conn->prepare("UPDATE customers SET full_name=?, institution=?, notes=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $institution, $notes, $id);
    $stmt->execute();

    // Supprimer anciens téléphones et emails
    $conn->query("DELETE FROM phones WHERE customer_id=$id");
    $conn->query("DELETE FROM emails WHERE customer_id=$id");

    // Ajouter nouveaux téléphones
    if(isset($_POST['phones']) && is_array($_POST['phones'])){
        foreach($_POST['phones'] as $phone){
            $phone = htmlspecialchars($phone);
            if(!empty($phone)){
                $stmt = $conn->prepare("INSERT INTO phones (customer_id, phone) VALUES (?, ?)");
                $stmt->bind_param("is", $id, $phone);
                $stmt->execute();
            }
        }
    }

    // Ajouter nouveaux emails
    if(isset($_POST['emails']) && is_array($_POST['emails'])){
        foreach($_POST['emails'] as $email){
            $email = htmlspecialchars($email);
            if(!empty($email)){
                $stmt = $conn->prepare("INSERT INTO emails (customer_id, email) VALUES (?, ?)");
                $stmt->bind_param("is", $id, $email);
                $stmt->execute();
            }
        }
    }

    header("Location: group.php?id=$group_id");
    exit;
}

// =====================
// Récupérer les membres
// =====================
$customers = $conn->query("
SELECT c.id, c.full_name, c.institution, c.notes,
GROUP_CONCAT(DISTINCT p.phone SEPARATOR ', ') AS phones,
GROUP_CONCAT(DISTINCT e.email SEPARATOR ', ') AS emails
FROM customers c
LEFT JOIN phones p ON c.id=p.customer_id
LEFT JOIN emails e ON c.id=e.customer_id
JOIN customer_group cg ON c.id=cg.customer_id
WHERE cg.group_id=$group_id
AND (c.full_name LIKE '%$search%' 
OR c.institution LIKE '%$search%'
OR c.notes LIKE '%$search%'
OR p.phone LIKE '%$search%'
OR e.email LIKE '%$search%')
GROUP BY c.id
ORDER BY c.id DESC
");

// =====================
// Récupérer tous les groupes pour le sidebar
// =====================
$all_groups = $conn->query("SELECT id, name FROM groups ORDER BY name");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Groupe : <?= htmlspecialchars($group['name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background:#f0f2f5; margin:0; color:#333; }
.sidebar { min-width:220px; max-width:220px; height:100vh; position:fixed; top:0; left:0; background:#1f2937; color:white; overflow-y:auto; padding-bottom:20px; }
.sidebar h4 { padding:15px; text-align:center; border-bottom:1px solid #111827; margin:0; font-weight:600; }
.sidebar a { display:block; padding:12px 20px; color:white; text-decoration:none; border-radius:8px; margin:5px 10px; transition:0.3s; }
.sidebar a:hover { background:#374151; }
.main { margin-left:220px; padding:25px; }
.card { border-radius:12px; box-shadow:0 6px 15px rgba(0,0,0,0.08); background:white; margin-bottom:20px; padding:20px; }
.table th { background:#1f2937; color:white; }
.table td, .table th { vertical-align:middle; }
input.form-control, select.form-select { border-radius:6px; }
.input-group-text { background:#3b82f6; color:white; border:none; border-radius:6px 0 0 6px; }
.btn-primary { background:#3b82f6; border:none; }
.btn-primary:hover { background:#2563eb; }
.btn-secondary { background:#6b7280; border:none; color:white; }
.btn-secondary:hover { background:#4b5563; }
.btn-danger { background:#ef4444; border:none; }
.btn-danger:hover { background:#dc2626; }
.btn-success { background:#10b981; border:none; }
.btn-success:hover { background:#059669; }
@media(max-width:768px){
    .sidebar { position:relative; width:100%; height:auto; }
    .main { margin-left:0; padding:15px; }
    .table { font-size:14px; }
    .input-group { flex-direction:column; }
    .input-group input, .input-group button { width:100%; margin-bottom:5px; }
}
</style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
        <h4>Groupes</h4>
        <a href="admin.php">Tous</a>
        <?php while($g = $all_groups->fetch_assoc()): ?>
            <a href="group.php?id=<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></a>
        <?php endwhile; ?>
    </div>

    <!-- Main -->
    <div class="main">
        <h3>👥 Membres du groupe : <?= htmlspecialchars($group['name']) ?></h3>

        <!-- Barre de recherche -->
        <form method="GET" class="mb-3">
            <input type="hidden" name="id" value="<?= $group_id ?>">
            <div class="input-group mb-3">
                <span class="input-group-text">🔍</span>
                <input type="text" name="search" class="form-control" placeholder="Rechercher par nom, téléphone, email..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary">Rechercher</button>
            </div>
        </form>

        <!-- Bouton Ajouter -->
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">➕ Ajouter un membre</button>

        <!-- Tableau des membres -->
        <div class="card table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Entreprise</th>
                    <th>Adresse</th>
                    <th>Téléphone(s)</th>
                    <th>Email(s)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($customers->num_rows > 0): ?>
                    <?php while($row = $customers->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['institution']) ?></td>
                            <td><?= htmlspecialchars($row['notes']) ?></td>
                            <td><?= $row['phones'] ?: '-' ?></td>
                            <td><?= $row['emails'] ?: '-' ?></td>
                            <td>
                                <!-- Modifier -->
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">✏️ Modifier</button>
                                <!-- Supprimer -->
                                <a href="group.php?id=<?= $group_id ?>&delete_customer=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Voulez-vous vraiment supprimer ce membre ?')">🗑️ Supprimer</a>
                            </td>
                        </tr>

                        <!-- Modal Modifier -->
                        <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog">
                            <div class="modal-content">
                              <form method="POST">
                              <div class="modal-header">
                                <h5 class="modal-title">Modifier le membre</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                  <input type="hidden" name="customer_id" value="<?= $row['id'] ?>">
                                  <div class="mb-3">
                                      <label>Nom complet</label>
                                      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['full_name']) ?>" required>
                                  </div>
                                  <div class="mb-3">
                                      <label>Entreprise</label>
                                      <input type="text" name="institution" class="form-control" value="<?= htmlspecialchars($row['institution']) ?>">
                                  </div>
                                  <div class="mb-3">
                                      <label>Adresse</label>
                                      <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($row['notes']) ?>">
                                  </div>
                                  <div class="mb-3">
                                      <label>Téléphone(s)</label>
                                      <?php
                                      $phones = $conn->query("SELECT phone FROM phones WHERE customer_id=".$row['id']);
                                      while($p = $phones->fetch_assoc()):
                                      ?>
                                      <input type="text" name="phones[]" class="form-control mb-1" value="<?= htmlspecialchars($p['phone']) ?>">
                                      <?php endwhile; ?>
                                      <input type="text" name="phones[]" class="form-control mb-1" placeholder="Ajouter un téléphone">
                                  </div>
                                  <div class="mb-3">
                                      <label>Email(s)</label>
                                      <?php
                                      $emails = $conn->query("SELECT email FROM emails WHERE customer_id=".$row['id']);
                                      while($e = $emails->fetch_assoc()):
                                      ?>
                                      <input type="email" name="emails[]" class="form-control mb-1" value="<?= htmlspecialchars($e['email']) ?>">
                                      <?php endwhile; ?>
                                      <input type="email" name="emails[]" class="form-control mb-1" placeholder="Ajouter un email">
                                  </div>
                              </div>
                              <div class="modal-footer">
                                <button type="submit" name="edit_customer" class="btn btn-primary">Enregistrer</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                              </div>
                              </form>
                            </div>
                          </div>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">Aucun membre dans ce groupe.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <a href="admin.php" class="btn btn-secondary mt-3">🔙 Retour à tous les clients</a>
    </div>
</div>

<!-- Modal Ajouter -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
      <div class="modal-header">
        <h5 class="modal-title">Ajouter un membre</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label>Nom complet</label>
              <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
              <label>Entreprise</label>
              <input type="text" name="institution" class="form-control">
          </div>
          <div class="mb-3">
              <label>Adresse</label>
              <input type="text" name="notes" class="form-control">
          </div>
          <div class="mb-3">
              <label>Téléphone(s)</label>
              <input type="text" name="phones[]" class="form-control mb-1" placeholder="Ajouter un téléphone">
              <input type="text" name="phones[]" class="form-control mb-1" placeholder="Ajouter un téléphone">
          </div>
          <div class="mb-3">
              <label>Email(s)</label>
              <input type="email" name="emails[]" class="form-control mb-1" placeholder="Ajouter un email">
              <input type="email" name="emails[]" class="form-control mb-1" placeholder="Ajouter un email">
          </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_customer" class="btn btn-success">Ajouter</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
